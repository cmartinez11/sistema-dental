<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabla de Pacientes
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Opcional si usa el portal web
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('dni', 15)->unique();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('allergies')->nullable(); // Antecedentes médicos críticos
            $table->timestamps();
        });

        // 2. Tabla de Dentistas (Especialistas)
        Schema::create('dentists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('cmp_number', 20)->unique(); // Colegio Médico / Odontológico
            $table->string('specialty', 100); // Ortodoncia, Endodoncia, General, etc.
            $table->decimal('commission_percentage', 5, 2)->default(0.00);
            $table->timestamps();

            // Llave foránea hacia la tabla users por defecto de Laravel
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 3. Catálogo de Tratamientos / Servicios
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->integer('estimated_duration_minutes')->default(30);
            $table->timestamps();
        });

        // 4. Tabla de Citas (Agenda)
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('dentist_id');
            $table->unsignedBigInteger('treatment_id')->nullable();
            $table->unsignedTinyInteger('dental_chair')->default(1); // Número de sillón clínico (1, 2, 3...)
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->enum('status', ['scheduled', 'confirmed', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('dentist_id')->references('id')->on('dentists')->onDelete('cascade');
            $table->foreign('treatment_id')->references('id')->on('treatments')->onDelete('set null');

            // Índices compuestos para optimizar búsquedas de solapamiento de horarios en InnoDB
            $table->index(['dentist_id', 'start_time', 'end_time'], 'idx_dentist_schedule');
            $table->index(['dental_chair', 'start_time', 'end_time'], 'idx_chair_schedule');
        });

        // 5. Historias Clínicas y Notas de Evolución por Sesión
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('dentist_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->text('diagnosis');
            $table->text('evolution_notes');
            $table->text('prescription')->nullable(); // Receta médica
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('dentist_id')->references('id')->on('dentists')->onDelete('cascade');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
        });

        // 6. Planes de Tratamiento / Presupuestos (Cabecera)
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('dentist_id');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('final_amount', 10, 2);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('dentist_id')->references('id')->on('dentists')->onDelete('cascade');
        });

        // 7. Cuotas de Tratamiento (Ortodoncia / Letras)
        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_plan_id');
            $table->integer('installment_number'); // Cuota 1, 2, 3...
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('payment_plan_id')->references('id')->on('payment_plans')->onDelete('cascade');
        });

        // 8. Control de Caja Diaria (Turnos de Recepción)
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Recepcionista
            $table->decimal('opening_amount', 10, 2); // Monto inicial (sencillo)
            $table->decimal('closing_amount', 10, 2)->nullable(); // Monto final al arqueo
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->dateTime('opened_at')->useCurrent();
            $table->dateTime('closed_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
        });

        // 9. Transacciones / Ingresos de Dinero
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_register_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('installment_id')->nullable(); // Enlace a cuota de ortodoncia
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'card', 'yape_plin', 'transfer']);
            $table->string('reference_code', 100)->nullable(); // Nro de operación o voucher
            $table->timestamps();

            $table->foreign('cash_register_id')->references('id')->on('cash_registers');
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('installment_id')->references('id')->on('payment_installments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Se eliminan en orden inverso para evitar conflictos de llaves foráneas
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('cash_registers');
        Schema::dropIfExists('payment_installments');
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('dentists');
        Schema::dropIfExists('patients');
    }
};