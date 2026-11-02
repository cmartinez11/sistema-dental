<?php

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
        if (!Schema::hasTable('dentist_schedules')) {
            Schema::create('dentist_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dentist_id')->constrained('dentists')->cascadeOnDelete();
                $table->unsignedTinyInteger('day_of_week'); // 1 = Lunes, ..., 6 = Sábado, 7 = Domingo (ISO-8601)
                $table->time('start_time')->default('09:00:00');
                $table->time('end_time')->default('18:00:00');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['dentist_id', 'day_of_week'], 'idx_dentist_day_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dentist_schedules');
    }
};

