@extends('layouts.app')

@section('title', 'Gestión de Citas - Clínica Dental SJL')

@section('content')
<div x-data="appointmentApp()" x-init="init()" class="space-y-6">

    <!-- CABECERA DE MÓDULO -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Agenda & Citas Odontológicas</h1>
            <p class="text-xs text-slate-500">Programación clínica por sillón dental y especialista en San Juan de Lurigancho</p>
        </div>
        <div class="flex items-center gap-2">
            <input type="date" x-model="selectedDate" @change="fetchAppointments()"
                class="px-3.5 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-slate-50 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            <button @click="setToday()" class="px-3 py-2 text-xs font-bold rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 transition">
                Hoy
            </button>
        </div>
    </div>

    <!-- ALERTAS GLOBALES -->
    <template x-if="alertMessage">
        <div x-transition class="p-4 rounded-2xl text-xs font-semibold flex items-center justify-between shadow-xs"
            :class="alertSuccess ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'">
            <div class="flex items-center gap-2">
                <span x-text="alertSuccess ? '✅' : '⚠️'"></span>
                <span x-text="alertMessage"></span>
            </div>
            <button @click="alertMessage = ''" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ========================================================================= -->
        <!-- FORMULARIO DE AGENDAMIENTO CON PROTECCIÓN DE CRUCE                        -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm lg:col-span-1">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    Agendar Nueva Consulta
                </h2>
                <span class="text-[11px] text-blue-600 font-semibold bg-blue-50 px-2.5 py-1 rounded-full">Mostrador</span>
            </div>

            <form @submit.prevent="submitAppointment" class="space-y-4">
                <!-- 1. Selección de Paciente -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Paciente:</label>
                    <select x-model="form.patient_id" required
                        class="w-full border border-slate-200 bg-white rounded-xl px-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <option value="">Seleccione un paciente registrado...</option>
                        <template x-for="p in patients" :key="p.id">
                            <option :value="p.id" x-text="p.first_name + ' ' + p.last_name + ' (DNI: ' + p.dni + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- 2. Selección de Especialista Odontólogo -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Cirujano Dentista / Especialista:</label>
                    <select x-model="form.dentist_id" required
                        class="w-full border border-slate-200 bg-white rounded-xl px-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <option value="">Seleccione el odontólogo tratante...</option>
                        <template x-for="d in dentists" :key="d.id">
                            <option :value="d.id" x-text="(d.user?.name || 'Dr. ' + d.id) + ' — ' + d.specialty + ' (CMP: ' + d.cmp_number + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- 3. Tratamiento / Servicio -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tratamiento Planificado:</label>
                    <select x-model="form.treatment_id"
                        class="w-full border border-slate-200 bg-white rounded-xl px-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <option value="">Evaluación general / Consulta inicial</option>
                        <template x-for="t in treatments" :key="t.id">
                            <option :value="t.id" x-text="t.name + ' (S/. ' + t.base_price + ' - ' + t.estimated_duration_minutes + ' min)'"></option>
                        </template>
                    </select>
                </div>

                <!-- 4. Sillón Clínico (Exclusión Física) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Sillón Odontológico Asignado:</label>
                    <div class="grid grid-cols-4 gap-2">
                        <template x-for="chair in [1, 2, 3, 4]" :key="chair">
                            <button type="button" @click="form.dental_chair = chair"
                                :class="form.dental_chair === chair ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-500/30' : 'bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100'"
                                class="py-2 rounded-xl text-xs transition text-center">
                                Sillón <span x-text="chair"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 5. Tiempos de Consulta -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Hora Inicio:</label>
                        <input type="datetime-local" x-model="form.start_time" required
                            class="w-full border border-slate-200 rounded-xl px-2.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Hora Fin:</label>
                        <input type="datetime-local" x-model="form.end_time" required
                            class="w-full border border-slate-200 rounded-xl px-2.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                </div>

                <!-- 6. Notas Clínicas de Recepción -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Observaciones / Motivo:</label>
                    <textarea x-model="form.notes" rows="2" placeholder="Ej: Paciente refiere dolor leve, primera sesión de ortodoncia..."
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"></textarea>
                </div>

                <button type="submit" :disabled="submitting"
                    class="w-full bg-[#1c71fe] hover:bg-blue-600 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-blue-500/25 transition transform active:scale-95 text-xs flex items-center justify-center gap-2 disabled:opacity-50">
                    <span x-show="!submitting">📅 Confirmar Cita en Sillón</span>
                    <span x-show="submitting">Validando disponibilidad...</span>
                </button>
            </form>
        </div>

        <!-- ========================================================================= -->
        <!-- TABLA EN VIVO DE CITAS ODONTOLÓGICAS DEL DÍA                              -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm lg:col-span-2">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-100 gap-2 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Citas Programadas para <span class="text-blue-600" x-text="selectedDate"></span></h2>
                    <p class="text-xs text-slate-400">Total de pacientes agendados en los sillones clínicos</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg" x-text="appointments.length + ' Citas'"></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-3">Horario</th>
                            <th class="py-3 px-3">Paciente</th>
                            <th class="py-3 px-3">Dentista</th>
                            <th class="py-3 px-3">Sillón</th>
                            <th class="py-3 px-3">Tratamiento</th>
                            <th class="py-3 px-3">Estado</th>
                            <th class="py-3 px-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-slate-700">
                        <template x-for="apt in appointments" :key="apt.id">
                            <tr class="hover:bg-slate-50/80 transition group">
                                <!-- Horario -->
                                <td class="py-3 px-3 whitespace-nowrap font-bold text-slate-800">
                                    <span x-text="formatTime(apt.start_time) + ' - ' + formatTime(apt.end_time)"></span>
                                </td>

                                <!-- Paciente -->
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-800" x-text="apt.patient?.first_name + ' ' + apt.patient?.last_name"></div>
                                    <div class="text-[10px] text-slate-400" x-text="'DNI: ' + (apt.patient?.dni || 'N/A')"></div>
                                </td>

                                <!-- Dentista -->
                                <td class="py-3 px-3">
                                    <div class="font-medium text-slate-800" x-text="apt.dentist?.user?.name || 'Dr. ' + apt.dentist_id"></div>
                                </td>

                                <!-- Sillón Clínico -->
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200/60"
                                        x-text="'Sillón #' + (apt.dental_chair || 1)"></span>
                                </td>

                                <!-- Tratamiento -->
                                <td class="py-3 px-3 text-slate-600">
                                    <span x-text="apt.treatment?.name || 'Consulta General'"></span>
                                </td>

                                <!-- Estado -->
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="{
                                            'bg-amber-50 text-amber-700 border border-amber-200': apt.status === 'scheduled',
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200': apt.status === 'confirmed' || apt.status === 'completed',
                                            'bg-red-50 text-red-700 border border-red-200': apt.status === 'cancelled'
                                        }"
                                        x-text="apt.status === 'scheduled' ? 'Agendada' : (apt.status === 'completed' ? 'Atendida' : apt.status)">
                                    </span>
                                </td>

                                <!-- Acción -->
                                <td class="py-3 px-3 text-right">
                                    <template x-if="apt.status !== 'cancelled'">
                                        <button @click="cancelAppointment(apt.id)"
                                            class="text-[11px] text-red-500 hover:text-red-700 font-bold hover:underline">
                                            Cancelar
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </template>

                        <template x-if="appointments.length === 0">
                            <tr>
                                <td colspan="7" class="py-10 text-center text-slate-400 text-xs">
                                    No hay citas odontológicas agendadas para esta fecha.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- SCRIPT ALPINE.JS CONECTADO CON SANCTUM -->
<script>
    function appointmentApp() {
        return {
            selectedDate: new Date().toISOString().split('T')[0],
            patients: [],
            dentists: [],
            treatments: [],
            appointments: [],
            submitting: false,
            alertMessage: '',
            alertSuccess: true,

            form: {
                patient_id: '',
                dentist_id: '',
                treatment_id: '',
                dental_chair: 1,
                start_time: '',
                end_time: '',
                notes: ''
            },

            async init() {
                this.setInitialTimes();
                await Promise.all([
                    this.fetchPatients(),
                    this.fetchDentists(),
                    this.fetchTreatments(),
                    this.fetchAppointments()
                ]);
            },

            setToday() {
                this.selectedDate = new Date().toISOString().split('T')[0];
                this.setInitialTimes();
                this.fetchAppointments();
            },

            setInitialTimes() {
                const now = new Date();
                now.setMinutes(0);
                now.setSeconds(0);
                now.setHours(now.getHours() + 1);

                const nextHour = new Date(now.getTime() + 45 * 60000);

                const formatLocal = (d) => {
                    const pad = (n) => String(n).padStart(2, '0');
                    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                };

                this.form.start_time = formatLocal(now);
                this.form.end_time = formatLocal(nextHour);
            },

            async fetchPatients() {
                try {
                    const res = await window.dentalApi.get('/api/patients?all=1');
                    if (res && res.ok) {
                        const json = await res.json();
                        this.patients = json.data || json || [];
                    }
                } catch (e) {
                    console.error('Error cargando pacientes:', e);
                }
            },

            async fetchDentists() {
                try {
                    const res = await window.dentalApi.get('/api/dentists');
                    if (res && res.ok) {
                        const json = await res.json();
                        this.dentists = json.data || [];
                    }
                } catch (e) {
                    console.error('Error cargando dentistas:', e);
                }
            },

            async fetchTreatments() {
                try {
                    const res = await window.dentalApi.get('/api/treatments');
                    if (res && res.ok) {
                        const json = await res.json();
                        this.treatments = json.data || [];
                    }
                } catch (e) {
                    console.error('Error cargando tratamientos:', e);
                }
            },

            async fetchAppointments() {
                try {
                    const res = await window.dentalApi.get('/api/appointments', { date: this.selectedDate });
                    if (res && res.ok) {
                        const json = await res.json();
                        this.appointments = json.data || [];
                    }
                } catch (e) {
                    console.error('Error cargando citas:', e);
                }
            },

            async submitAppointment() {
                this.submitting = true;
                this.alertMessage = '';

                try {
                    const res = await window.dentalApi.post('/api/appointments', this.form);
                    const json = await res.json();

                    if (res.ok) {
                        this.alertSuccess = true;
                        this.alertMessage = '¡Cita clínica agendada con éxito en el Sillón #' + this.form.dental_chair + '!';
                        this.form.notes = '';
                        this.setInitialTimes();
                        await this.fetchAppointments();
                    } else {
                        this.alertSuccess = false;
                        if (json.errors) {
                            const firstKey = Object.keys(json.errors)[0];
                            this.alertMessage = json.errors[firstKey][0];
                        } else {
                            this.alertMessage = json.message || 'Error al validar conflicto de horarios.';
                        }
                    }
                } catch (error) {
                    this.alertSuccess = false;
                    this.alertMessage = 'Error de conexión con el servidor.';
                } finally {
                    this.submitting = false;
                }
            },

            async cancelAppointment(id) {
                if (!confirm('¿Confirma que desea cancelar esta cita y liberar el sillón dental?')) return;

                try {
                    const res = await window.dentalApi.request(`/appointments/${id}`, {
                        method: 'DELETE'
                    });

                    if (res.ok) {
                        this.alertSuccess = true;
                        this.alertMessage = 'La cita ha sido cancelada y el horario fue liberado.';
                        await this.fetchAppointments();
                    }
                } catch (e) {
                    this.alertSuccess = false;
                    this.alertMessage = 'No se pudo cancelar la cita.';
                }
            },

            formatTime(datetimeStr) {
                if (!datetimeStr) return '';
                const parts = datetimeStr.split('T');
                if (parts.length > 1) {
                    return parts[1].substring(0, 5);
                }
                const spaceParts = datetimeStr.split(' ');
                if (spaceParts.length > 1) {
                    return spaceParts[1].substring(0, 5);
                }
                return datetimeStr;
            }
        };
    }
</script>
@endsection
