<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Major;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WaMessageLog;
use App\Models\WaBlastBatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WaBlastService
{
    public function __construct(
        private readonly WaGatewayService $waGatewayService,
    ) {
    }

    /**
     * @param array{major_id?:mixed,status?:mixed,q?:mixed} $filters
     * @return LengthAwarePaginator<int, WaMessageLog>
     */
    public function history(School $school, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return WaMessageLog::query()
            ->with(['student.major', 'adminUser'])
            ->where('school_id', $school->id)
            ->when(($filters['major_id'] ?? null) !== null && $filters['major_id'] !== '', function (Builder $query) use ($filters): void {
                $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('major_id', (int) $filters['major_id']));
            })
            ->when(($filters['status'] ?? null) !== null && $filters['status'] !== '', function (Builder $query) use ($filters): void {
                $query->where('status', (string) $filters['status']);
            })
            ->when(($filters['q'] ?? null) !== null && trim((string) $filters['q']) !== '', function (Builder $query) use ($filters): void {
                $keyword = trim((string) $filters['q']);
                $query->where(function (Builder $inner) use ($keyword): void {
                    $inner->where('recipient_name', 'like', "%{$keyword}%")
                        ->orWhere('recipient_number', 'like', "%{$keyword}%")
                        ->orWhere('message', 'like', "%{$keyword}%");
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param array{major_id?:mixed,status?:mixed,access?:mixed,q?:mixed} $filters
     * @return Collection<int, Student>
     */
    public function recipients(School $school, array $filters): Collection
    {
        return Student::query()
            ->with('major')
            ->where('school_id', $school->id)
            ->whereNotNull('nomor_wa')
            ->where('nomor_wa', '!=', '')
            ->when(($filters['major_id'] ?? null) !== null && $filters['major_id'] !== '', fn (Builder $query) => $query->where('major_id', (int) $filters['major_id']))
            ->when(($filters['status'] ?? null) !== null && $filters['status'] !== '', fn (Builder $query) => $query->where('status', (string) $filters['status']))
            ->when(($filters['access'] ?? null) === 'open', fn (Builder $query) => $query->where('access_locked', false))
            ->when(($filters['access'] ?? null) === 'locked', fn (Builder $query) => $query->where('access_locked', true))
            ->when(($filters['q'] ?? null) !== null && trim((string) $filters['q']) !== '', function (Builder $query) use ($filters): void {
                $keyword = trim((string) $filters['q']);
                $query->where(function (Builder $inner) use ($keyword): void {
                    $inner->where('name', 'like', "%{$keyword}%")
                        ->orWhere('nisn', 'like', "%{$keyword}%")
                        ->orWhere('nomor_wa', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array{major_id?:mixed,status?:mixed,access?:mixed,q?:mixed} $filters
     * @return array{sent:int,failed:int,total:int,logs:Collection<int,WaMessageLog>}
     */
    public function sendBlast(School $school, User $admin, string $template, array $filters): array
    {
        $students = $this->recipients($school, $filters);
        $sent = 0;
        $failed = 0;
        $logs = collect();

        foreach ($students as $student) {
            $message = $this->renderTemplate($template, $school, $student);
            $isSent = $this->waGatewayService->sendMessage((string) $student->nomor_wa, $message);

            $log = WaMessageLog::query()->create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'admin_user_id' => $admin->id,
                'recipient_name' => $student->name,
                'recipient_number' => (string) $student->nomor_wa,
                'message_type' => 'blast',
                'status' => $isSent ? 'sent' : 'failed',
                'message' => $message,
                'meta' => [
                    'major_id' => $student->major_id,
                    'major_name' => $student->major?->name,
                    'student_status' => $student->status,
                ],
                'sent_at' => $isSent ? now() : null,
            ]);

            $logs->push($log);
            $isSent ? $sent++ : $failed++;
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'total' => $students->count(),
            'logs' => $logs,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function dashboardStats(School $school): array
    {
        $base = WaMessageLog::query()->where('school_id', $school->id);

        return [
            'total_logs' => (clone $base)->count(),
            'sent' => (clone $base)->where('status', 'sent')->count(),
            'failed' => (clone $base)->where('status', 'failed')->count(),
            'today' => (clone $base)->whereDate('created_at', today())->count(),
        ];
    }

    /**
     * @return Collection<int, Major>
     */
    public function majorOptions(School $school): Collection
    {
        return Major::query()
            ->where('school_id', $school->id)
            ->orderBy('name')
            ->get();
    }

    public function createBatch(School $school, User $admin, string $template, array $filters): WaBlastBatch
    {
        $students = $this->recipients($school, $filters);

        return WaBlastBatch::create([
            'school_id' => $school->id,
            'admin_user_id' => $admin->id,
            'status' => 'pending',
            'total_count' => $students->count(),
            'filters' => $filters,
            'content' => $template,
        ]);
    }

    public function renderTemplate(string $template, School $school, Student $student): string
    {
        $replacements = [
            '{nama_siswa}' => $student->name,
            '{nisn}' => $student->nisn,
            '{jurusan}' => $student->major?->name ?? '-',
            '{kode_jurusan}' => $student->major?->code ?? '-',
            '{nama_sekolah}' => $school->nama_sekolah ?? $school->name,
            '{tanggal_rilis}' => optional(app(AnnouncementService::class)->getAnnouncementAt())?->translatedFormat('d F Y H:i') ?? '-',
            '{status_kelulusan}' => $student->status ?? 'Pending',
        ];

        $rendered = str_replace(array_keys($replacements), array_values($replacements), $template);

        return trim(preg_replace("/\r\n|\r|\n/", "\n", $rendered) ?? $template);
    }
}
