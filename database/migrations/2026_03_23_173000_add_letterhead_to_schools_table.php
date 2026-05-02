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
            if (!Schema::hasColumn('schools', 'kop_surat')) {
                $table->string('kop_surat')->nullable()->after('logo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (Schema::hasColumn('schools', 'kop_surat')) {
                $table->dropColumn('kop_surat');
            }
        });
    }
};
