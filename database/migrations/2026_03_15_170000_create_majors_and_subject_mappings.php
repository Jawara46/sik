<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('majors')) {
            Schema::create('majors', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->string('name', 150);
                $table->string('code', 20);
                $table->timestamps();

                $table->unique(['school_id', 'code']);
            });
        }

        if (!Schema::hasColumn('students', 'major_id')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->foreignId('major_id')
                    ->nullable()
                    ->after('school_id')
                    ->constrained('majors')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('major_subject')) {
            Schema::create('major_subject', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('major_id')->constrained('majors')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['major_id', 'subject_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('major_subject')) {
            Schema::drop('major_subject');
        }

        if (Schema::hasColumn('students', 'major_id')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('major_id');
            });
        }

        if (Schema::hasTable('majors')) {
            Schema::drop('majors');
        }
    }
};

