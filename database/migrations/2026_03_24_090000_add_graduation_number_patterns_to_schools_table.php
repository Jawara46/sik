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
            $table->string('skl_number_pattern', 150)->nullable()->after('use_digital_stamp');
            $table->string('transcript_number_pattern', 150)->nullable()->after('skl_number_pattern');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn([
                'skl_number_pattern',
                'transcript_number_pattern',
            ]);
        });
    }
};
