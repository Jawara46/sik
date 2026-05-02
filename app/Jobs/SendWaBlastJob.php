<?php

namespace App\Jobs;

use App\Models\WaBlastBatch;
use App\Models\WaMessageLog;
use App\Services\WaBlastService;
use App\Services\WaGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWaBlastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WaBlastBatch $batch
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(WaBlastService $waBlastService, WaGatewayService $waGatewayService): void
    {
        $this->batch->update(['status' => 'processing']);

        $school = $this->batch->school;
        $admin = $this->batch->adminUser;
        $template = $this->batch->content;
        $filters = $this->batch->filters;

        $students = $waBlastService->recipients($school, $filters);

        foreach ($students as $student) {
            // Check if job should be cancelled (optional, can be implemented later)
            
            $message = $waBlastService->renderTemplate($template, $school, $student);
            $isSent = $waGatewayService->sendMessage((string) $student->nomor_wa, $message);

            WaMessageLog::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'admin_user_id' => $admin->id,
                'recipient_name' => $student->name,
                'recipient_number' => (string) $student->nomor_wa,
                'message_type' => 'blast',
                'status' => $isSent ? 'sent' : 'failed',
                'message' => $message,
                'sent_at' => $isSent ? now() : null,
            ]);

            $this->batch->increment('processed_count');
            if ($isSent) {
                $this->batch->increment('sent_count');
            } else {
                $this->batch->increment('failed_count');
            }
        }

        $this->batch->update(['status' => 'completed']);
    }
}
