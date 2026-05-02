<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('grades', 'semester')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->unsignedTinyInteger('semester')->default(6)->after('subject_id');
            });
        }

        $indexes = $this->getIndexes();

        if (!in_array('grades_student_id_index', $indexes, true)) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->index('student_id', 'grades_student_id_index');
            });
        }

        if (in_array('grades_student_id_subject_id_unique', $indexes, true)) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->dropUnique(['student_id', 'subject_id']);
            });
        }

        $indexes = $this->getIndexes();

        if (!in_array('grades_student_subject_semester_unique', $indexes, true)) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->unique(['student_id', 'subject_id', 'semester'], 'grades_student_subject_semester_unique');
            });
        }

        if (!in_array('grades_student_semester_index', $indexes, true)) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->index(['student_id', 'semester'], 'grades_student_semester_index');
            });
        }
    }

    public function down(): void
    {
        $indexes = $this->getIndexes();

        Schema::table('grades', function (Blueprint $table) use ($indexes): void {
            if (in_array('grades_student_subject_semester_unique', $indexes, true)) {
                $table->dropUnique('grades_student_subject_semester_unique');
            }

            if (in_array('grades_student_semester_index', $indexes, true)) {
                $table->dropIndex('grades_student_semester_index');
            }

            if (in_array('grades_student_id_index', $indexes, true)) {
                $table->dropIndex('grades_student_id_index');
            }
        });

        if (Schema::hasColumn('grades', 'semester')) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->dropColumn('semester');
            });
        }

        $indexes = $this->getIndexes();

        if (!in_array('grades_student_id_subject_id_unique', $indexes, true)) {
            Schema::table('grades', function (Blueprint $table): void {
                $table->unique(['student_id', 'subject_id']);
            });
        }
    }

    /**
     * @return array<int, string>
     */
    private function getIndexes(): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('grades')"))
                ->pluck('name')
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return collect(DB::select('SHOW INDEX FROM grades'))
            ->pluck('Key_name')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
};
