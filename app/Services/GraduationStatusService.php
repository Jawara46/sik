<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;

class GraduationStatusService
{
    /**
     * Set the graduation status for a student.
     * 
     * @param Student $student
     * @param string $status 'Lulus', 'Tidak Lulus', 'Pending'
     * @return bool
     */
    public function updateStatus(Student $student, string $status): bool
    {
        $student->status = $status;
        return $student->save();
    }

    /**
     * Lock or unlock a student's access to download SKL/Transcript.
     * 
     * @param Student $student
     * @param bool $isLocked
     * @return bool
     */
    public function toggleAccessLock(Student $student, bool $isLocked): bool
    {
        $student->access_locked = $isLocked;
        $student->status_administrasi = !$isLocked;
        return $student->save();
    }

    /**
     * Determine if a student can download their document.
     * 
     * @param Student $student
     * @return bool
     */
    public function canDownloadDocument(Student $student): bool
    {
        // Can only download if status is finalized and administration is cleared.
        return (bool) $student->status_administrasi && $student->status !== 'Pending';
    }
    
    /**
     * Bulk update graduation status for multiple students
     * 
     * @param array<int> $studentIds
     * @param string $status
     * @return int Number of students updated
     */
    public function bulkUpdateStatus(array $studentIds, string $status): int
    {
        return Student::whereIn('id', $studentIds)->update(['status' => $status]);
    }

    /**
     * Bulk lock or unlock document access for multiple students.
     *
     * @param array<int> $studentIds
     */
    public function bulkToggleAccess(array $studentIds, bool $isLocked): int
    {
        return Student::whereIn('id', $studentIds)->update([
            'access_locked' => $isLocked,
            'status_administrasi' => !$isLocked,
        ]);
    }
}
