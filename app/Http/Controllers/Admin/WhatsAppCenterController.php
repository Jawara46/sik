<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SchoolProfileService;
use App\Services\WaBlastService;
use App\Services\WaSessionService;
use App\Services\WaGatewayService;
use App\Models\WaMessageLog;
use App\Models\WaMessageTemplate;
use App\Models\WaAutoRespondLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\WaBlastBatch;
use App\Jobs\SendWaBlastJob;

class WhatsAppCenterController extends Controller
{
    public function __construct(
        private readonly SchoolProfileService $schoolProfileService,
        private readonly WaSessionService $waSessionService,
        private readonly WaBlastService $waBlastService,
    ) {
    }

    public function connection(): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $status = $this->waSessionService->getStatus($school);

        return view('admin.whatsapp.connection', [
            'school' => $school,
            'waStatus' => $status,
        ]);
    }

    public function status(): JsonResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        return response()->json($this->waSessionService->getStatus($school));
    }

    public function qr(): JsonResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        return response()->json($this->waSessionService->refreshQr($school));
    }

    public function testSend(Request $request): JsonResponse
    {
        $request->validate([
            'number' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        $success = app(WaGatewayService::class)->sendMessage(
            $request->input('number'),
            $request->input('message')
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Pesan berhasil dikirim!' : 'Gagal mengirim pesan. Pastikan WA terhubung.'
        ]);
    }

    public function disconnect(): JsonResponse
    {
        $success = app(WaGatewayService::class)->logout();
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Berhasil memutuskan koneksi WhatsApp.' : 'Gagal memutuskan koneksi. Silakan coba lagi.'
        ]);
    }

    public function blast(Request $request): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $filters = [
            'major_id' => $request->string('major_id')->toString(),
            'status' => $request->string('status')->toString(),
            'access' => $request->string('access')->toString(),
            'q' => $request->string('q')->toString(),
        ];
        $recipients = $this->waBlastService->recipients($school, $filters);
        $templates = WaMessageTemplate::where('school_id', $school->id)->orderBy('name')->get();

        return view('admin.whatsapp.blast', [
            'school' => $school,
            'majors' => $this->waBlastService->majorOptions($school),
            'filters' => $filters,
            'recipients' => $recipients,
            'templates' => $templates,
            'stats' => $this->waBlastService->dashboardStats($school),
            'defaultMessage' => trim(implode("\n", [
                'Yth. {nama_siswa},',
                'Pengumuman kelulusan di {nama_sekolah} telah tersedia.',
                'Status Anda: {status_kelulusan}.',
                'Silakan login ke portal siswa menggunakan NISN untuk melihat detail dan mengunduh dokumen.',
                'Waktu rilis: {tanggal_rilis}.',
            ])),
        ]);
    }

    public function sendBlast(Request $request): JsonResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'major_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:Lulus,Tidak Lulus,Pending'],
            'access' => ['nullable', 'in:open,locked'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var \App\Models\User $admin */
        $admin = auth('admin')->user();
        
        $batch = $this->waBlastService->createBatch($school, $admin, (string) $validated['message'], $validated);
        
        SendWaBlastJob::dispatch($batch);

        return response()->json([
            'success' => true,
            'message' => 'Blast dimulai.',
            'batch_id' => $batch->id
        ]);
    }

    public function blastStatus(WaBlastBatch $batch): JsonResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        if ($batch->school_id !== $school->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'status' => $batch->status,
            'total' => $batch->total_count,
            'processed' => $batch->processed_count,
            'sent' => $batch->sent_count,
            'failed' => $batch->failed_count,
            'percent' => $batch->total_count > 0 ? round(($batch->processed_count / $batch->total_count) * 100) : 0,
        ]);
    }

    public function history(Request $request): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $filters = [
            'major_id' => $request->string('major_id')->toString(),
            'status' => $request->string('status')->toString(),
            'q' => $request->string('q')->toString(),
        ];

        return view('admin.whatsapp.history', [
            'school' => $school,
            'majors' => $this->waBlastService->majorOptions($school),
            'filters' => $filters,
            'stats' => $this->waBlastService->dashboardStats($school),
            'logs' => $this->waBlastService->history($school, $filters),
        ]);
    }

    public function retryMessage(WaMessageLog $log): RedirectResponse
    {
        if ($log->status === 'sent') {
            return redirect()->back()->with('error', 'Pesan sudah terkirim sebelumnya.');
        }

        $success = app(WaGatewayService::class)->sendMessage(
            $log->recipient_number,
            $log->message
        );

        if ($success) {
            $log->update([
                'status' => 'sent',
                'sent_at' => now()
            ]);
            return redirect()->back()->with('status', 'Pesan berhasil dikirim ulang.');
        }

        return redirect()->back()->with('error', 'Gagal mengirim ulang pesan. Pastikan WA terhubung.');
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $template = WaMessageTemplate::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'content' => $validated['content'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template berhasil disimpan.',
            'template' => $template
        ]);
    }

    public function deleteTemplate(WaMessageTemplate $template): JsonResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        if ($template->school_id !== $school->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template berhasil dihapus.'
        ]);
    }

    public function sendIndividual(Request $request): JsonResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $student = \App\Models\Student::findOrFail($validated['student_id']);
        if ($student->school_id !== $school->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (!$student->nomor_wa) {
            return response()->json(['success' => false, 'message' => 'Siswa tidak memiliki nomor WA.'], 400);
        }

        $message = $this->waBlastService->renderTemplate($validated['message'], $school, $student);
        $isSent = app(WaGatewayService::class)->sendMessage((string) $student->nomor_wa, $message);


        /** @var \App\Models\User $admin */
        $admin = auth('admin')->user();

        WaMessageLog::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'admin_user_id' => $admin->id,
            'recipient_name' => $student->name,
            'recipient_number' => (string) $student->nomor_wa,
            'message_type' => 'individual',
            'status' => $isSent ? 'sent' : 'failed',
            'message' => $validated['message'],
            'sent_at' => $isSent ? now() : null,
        ]);

        return response()->json([
            'success' => $isSent,
            'message' => $isSent ? 'Pesan berhasil dikirim.' : 'Gagal mengirim pesan. Pastikan WA terhubung.'
        ]);
    }

    public function autoRespond(Request $request): View
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $logs = WaAutoRespondLog::where('school_id', $school->id)
            ->with('student')
            ->latest()
            ->paginate(15);

        return view('admin.whatsapp.auto-respond', [
            'school' => $school,
            'logs' => $logs,
        ]);
    }

    public function toggleAutoRespond(Request $request): JsonResponse
    {
        $school = $this->schoolProfileService->getCurrentSchool();
        $enabled = $request->boolean('enabled');
        
        $school->update(['enable_wa_auto_respond' => $enabled]);

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'Auto-respond diaktifkan.' : 'Auto-respond dinonaktifkan.',
        ]);
    }
}
