@extends('layouts.app')

@section('title', 'Gestión de Horarios y Disponibilidad - Clínica Dental SJL')

@section('content')
<div class="space-y-6" x-data="dentistScheduleApp()">

    <!-- Cabecera del Módulo -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold mb-2">
                <span>⏱️ Turnos Clínicos</span>
                <span>•</span>
                <span>Configuración de Especialistas</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-800 tracking-tight">
                Horarios y Disponibilidad de Odontólogos
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Configure los turnos laborales semanales para cada cirujano dentista. Las citas solo podrán agendarse dentro de sus turnos activos.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button @click="copyMondayToWeek()"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition shadow-xs">
                <span>📋 Copiar Lunes a Vie</span>
            </button>
            <button @click="saveSchedules()" :disabled="saving"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-blue-600/25 disabled:opacity-50">
                <span x-show="!saving">💾 Guardar Horarios</span>
                <span x-show="saving">Guardando...</span>
            </button>
        </div>
    </div>

    <!-- Alertas reactivas -->
    <template x-if="alertMessage">
        <div :class="alertSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'"
             class="p-4 rounded-2xl border text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span x-text="alertSuccess ? '✅' : '⚠️'"></span>
                <span x-text="alertMessage"></span>
            </div>
            <button @click="alertMessage = ''" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Selector de Odontólogos (Columna Izquierda) -->
        <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 px-2">Especialistas</h3>

            <div class="space-y-1.5">
                <template x-for="d in dentists" :key="d.id">
                    <button type="button" @click="selectDentist(d.id)"
                        :class="selectedDentistId === d.id ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-slate-50 hover:bg-slate-100 text-slate-700'"
                        class="w-full text-left p-3.5 rounded-2xl transition flex items-center justify-between group">
                        <div class="space-y-0.5">
                            <p class="text-xs font-bold" x-text="d.user?.name || 'Dr. ' + d.id"></p>
                            <p :class="selectedDentistId === d.id ? 'text-blue-100' : 'text-slate-400'"
                               class="text-[11px]" x-text="d.specialty || 'Cirujano Dentista'"></p>
                        </div>
                        <span :class="selectedDentistId === d.id ? 'text-white' : 'text-slate-400'" class="text-xs">➔</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Grilla de Horarios Semanales (Columna Derecha) -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm lg:col-span-3 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-bold text-slate-800" x-text="'Horario Semanal: ' + (currentDentist?.name || 'Cargando...')"></h2>
                    <p class="text-xs text-slate-400">Colegiatura: <span class="font-semibold text-slate-600" x-text="currentDentist?.cmp_number || 'N/A'"></span></p>
                </div>
                <span class="text-xs font-bold px-3 py-1 bg-blue-50 text-blue-700 rounded-full border border-blue-200/60">
                    Sede San Juan de Lurigancho
                </span>
            </div>

            <!-- Tabla de Días y Turnos -->
            <div class="space-y-3">
                <template x-for="(sch, idx) in schedules" :key="sch.day_of_week">
                    <div :class="sch.is_active ? 'bg-slate-50/70 border-slate-200/80' : 'bg-slate-100/40 border-slate-200/40 opacity-70'"
                         class="p-4 rounded-2xl border transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">

                        <!-- Nombre del Día y Switch Activo/Inactivo -->
                        <div class="flex items-center gap-4 min-w-[160px]">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="sch.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>

                            <div>
                                <h4 class="text-xs font-extrabold text-slate-800" x-text="sch.day_name"></h4>
                                <span :class="sch.is_active ? 'text-emerald-600 font-semibold' : 'text-slate-400'"
                                      class="text-[11px]" x-text="sch.is_active ? 'Turno Activo' : 'No labora'"></span>
                            </div>
                        </div>

                        <!-- Selector de Horas (Inicio y Fin) -->
                        <div class="flex items-center gap-3" x-show="sch.is_active">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400 font-medium">De:</span>
                                <input type="time" x-model="sch.start_time" required
                                    class="border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <span class="text-slate-300">—</span>

                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400 font-medium">Hasta:</span>
                                <input type="time" x-model="sch.end_time" required
                                    class="border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Mensaje si está inactivo -->
                        <div x-show="!sch.is_active" class="text-xs text-slate-400 italic">
                            Día de descanso o sin atención clínica programada
                        </div>
                    </div>
                </template>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <p class="text-xs text-slate-400">
                    💡 Las citas de recepción que caigan fuera del turno configurado serán bloqueadas automáticamente.
                </p>
                <button @click="saveSchedules()" :disabled="saving"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs shadow-md transition shadow-blue-600/25 disabled:opacity-50">
                    Guardar Cambios
                </button>
            </div>

        </div>

    </div>

</div>

<!-- SCRIPT ALPINE.JS DE HORARIOS -->
<script>
    function dentistScheduleApp() {
        return {
            dentists: @json($dentists ?? []),
            selectedDentistId: {{ $dentists->first()->id ?? 'null' }},
            currentDentist: null,
            schedules: [],
            saving: false,
            alertMessage: '',
            alertSuccess: true,

            async init() {
                if (this.selectedDentistId) {
                    await this.loadDentistSchedule(this.selectedDentistId);
                }
            },

            async selectDentist(id) {
                this.selectedDentistId = id;
                await this.loadDentistSchedule(id);
            },

            async loadDentistSchedule(id) {
                try {
                    const res = await window.dentalApi.get('/dashboard/dentist-schedules/' + id);
                    const data = await res.json();
                    if (res.ok) {
                        this.currentDentist = data.dentist;
                        this.schedules = data.schedules;
                    }
                } catch (e) {
                    this.alertSuccess = false;
                    this.alertMessage = 'Error al cargar los turnos del especialista.';
                }
            },

            copyMondayToWeek() {
                const monday = this.schedules.find(s => s.day_of_week === 1);
                if (!monday) return;

                // Copiar horario a Martes (2), Miércoles (3), Jueves (4), Viernes (5)
                this.schedules.forEach(s => {
                    if (s.day_of_week >= 2 && s.day_of_week <= 5) {
                        s.start_time = monday.start_time;
                        s.end_time = monday.end_time;
                        s.is_active = monday.is_active;
                    }
                });

                this.alertSuccess = true;
                this.alertMessage = 'Horario de Lunes replicado a Martes, Miércoles, Jueves y Viernes.';
            },

            async saveSchedules() {
                this.saving = true;
                this.alertMessage = '';
                try {
                    const res = await window.dentalApi.post('/dashboard/dentist-schedules/' + this.selectedDentistId, {
                        schedules: this.schedules
                    });
                    const data = await res.json();

                    if (res.ok) {
                        this.alertSuccess = true;
                        this.alertMessage = '¡Horarios del especialista guardados con éxito!';
                    } else {
                        this.alertSuccess = false;
                        this.alertMessage = data.message || 'Error al actualizar horarios.';
                    }
                } catch (e) {
                    this.alertSuccess = false;
                    this.alertMessage = 'Error de conexión con el servidor.';
                } finally {
                    this.saving = false;
                }
            }
        };
    }
</script>
@endsection

