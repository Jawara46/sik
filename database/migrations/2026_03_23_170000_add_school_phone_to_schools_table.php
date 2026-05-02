<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (!Schema::hasColumn('schools', 'telepon_sekolah')) {
                $table->string('telepon_sekolah', 30)->nullable()->after('email_sekolah');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (Schema::hasColumn('schools', 'telepon_sekolah')) {
                $table->dropColumn('telepon_sekolah');
            }
        });
    }
};
