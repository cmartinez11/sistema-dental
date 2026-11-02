@extends('layouts.app')

@section('title', 'Historias Clínicas & Evolución - Clínica Dental SJL')

@section('content')
<div x-data="medicalRecordApp()" x-init="init()" class="space-y-6">

    <!-- CABECERA DE MÓDULO -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Consultorio Clínico & Historias Médicas</h1>
            <p class="text-xs text-slate-500">Registro de evolución, diagnóstico odontológico y prescripción médica con PDF</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="fetchRecords()" class="px-3 py-2 text-xs font-bold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition flex items-center gap-1.5">
                <span>🔄</span> Actualizar Fichas
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
                <template x-if="lastSavedRecordId">
                    <a :href="'/medical-records/' + lastSavedRecordId + '/pdf'" target="_blank"
                        class="ml-2 px-2.5 py-1 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition">
                        🖨️ Descargar Receta PDF
                    </a>
                </template>
            </div>
            <button @click="alertMessage = ''" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ========================================================================= -->
        <!-- FORMULARIO DE REGISTRO CLÍNICO Y NOTA DE EVOLUCIÓN                        -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm lg:col-span-1">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                    Nueva Nota de Evolución
                </h2>
                <span class="text-[11px] text-indigo-700 font-semibold bg-indigo-50 px-2.5 py-1 rounded-full">Consultorio</span>
            </div>

            <form @submit.prevent="submitRecord" class="space-y-4">
                <!-- 1. Selección de Paciente -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Paciente en Sillón:</label>
                    <select x-model="form.patient_id" @change="onPatientSelected()" required
                        class="w-full border border-slate-200 bg-white rounded-xl px-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccione el paciente atendido...</option>
                        <template x-for="p in patients" :key="p.id">
                            <option :value="p.id" x-text="p.first_name + ' ' + p.last_name + ' (DNI: ' + p.dni + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- ALERTA CRÍTICA DE ALERGIAS Y ANTECEDENTES DEL PACIENTE -->
                <div x-show="selectedPatientAllergies" class="p-3 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-start gap-2">
                        <span class="text-base">🚨</span>
                        <div>
                            <span class="text-xs font-extrabold text-red-800">Alerta Médica / Alergias:</span>
                            <p class="text-xs text-red-700 mt-0.5" x-text="selectedPatientAllergies"></p>
                        </div>
                    </div>
                </div>

                <!-- 2. Odontólogo Tratante -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Especialista / Dentista:</label>
                    <select x-model="form.dentist_id" required
                        class="w-full border border-slate-200 bg-white rounded-xl px-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccione el odontólogo tratante...</option>
                        <template x-for="d in dentists" :key="d.id">
                            <option :value="d.id" x-text="(d.user?.name || 'Dr. ' + d.id) + ' — ' + d.specialty"></option>
                        </template>
                    </select>
                </div>

                <!-- 3. Diagnóstico Odontológico -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Diagnóstico Clínico:</label>
                    <textarea x-model="form.diagnosis" rows="2" required placeholder="Ej: Pulpitis irreversible en pieza 46 / Maloclusión Clase II"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <!-- 4. Notas de Evolución -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Evolución & Procedimiento Realizado:</label>
                    <textarea x-model="form.evolution_notes" rows="3" required placeholder="Detalle clínico: Biopulpectomía, anestesia infiltrativa, curación con ionómero..."
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <!-- 5. Receta Médica / Prescripción Oficial -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Receta Médica & Posología:</label>
                    <textarea x-model="form.prescription" rows="3" placeholder="Ej: Amoxicilina 500mg: 1 cápsula cada 8 horas por 5 días.&#10;Ibuprofeno 400mg: 1 tableta cada 8 horas en caso de dolor."
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"></textarea>
                </div>

                <button type="submit" :disabled="savingRecord"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-indigo-600/25 transition transform active:scale-95 text-xs flex items-center justify-center gap-2 disabled:opacity-50">
                    <span x-show="!savingRecord">📋 Guardar Ficha y Emitir Receta</span>
                    <span x-show="savingRecord">Guardando nota clínica...</span>
                </button>
            </form>
        </div>

        <!-- ========================================================================= -->
        <!-- HISTORIAL CLÍNICO Y CONSULTAS RECIENTES                                   -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Evolución Clínica & Recetas Emitidas</h2>
                    <p class="text-xs text-slate-400">Historial odontológico de atenciones con opción de reimpresión de recetas</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg" x-text="records.length + ' Fichas'"></span>
            </div>

            <div class="space-y-4">
                <template x-for="rec in records" :key="rec.id">
                    <div class="border border-slate-100 rounded-2xl p-4 bg-slate-50/60 hover:bg-slate-50 transition space-y-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-2 border-b border-slate-200/60">
                            <div class="flex items-center gap-2">
                                <span class="text-sm">🦷</span>
                                <span class="font-bold text-slate-800 text-xs" x-text="rec.patient?.first_name + ' ' + rec.patient?.last_name"></span>
                                <span class="text-[10px] text-slate-400" x-text="'(DNI: ' + (rec.patient?.dni || 'N/A') + ')'"></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-[11px] text-slate-500 font-medium" x-text="formatDate(rec.created_at)"></span>
                                <a :href="'/medical-records/' + rec.id + '/pdf'" target="_blank"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200/60 rounded-lg text-[11px] font-bold transition">
                                    <span>🖨️</span> PDF Receta
                                </a>
                            </div>
                        </div>

                        <div class="text-xs space-y-1">
                            <p class="text-slate-800"><strong class="text-slate-600">Diagnóstico:</strong> <span x-text="rec.diagnosis"></span></p>
                            <p class="text-slate-700"><strong class="text-slate-600">Evolución:</strong> <span x-text="rec.evolution_notes"></span></p>

                            <template x-if="rec.prescription">
                                <div class="mt-2 p-2.5 bg-indigo-50/50 rounded-xl border border-indigo-100/70 text-indigo-900 text-[11px]">
                                    <strong class="text-indigo-800 block mb-0.5">💊 Indicaciones & Medicación:</strong>
                                    <span class="whitespace-pre-line" x-text="rec.prescription"></span>
                                </div>
                            </template>

                            <div class="pt-1 text-[10px] text-slate-400">
                                Atendido por: <span class="font-semibold text-slate-600" x-text="rec.dentist?.user?.name || 'Cirujano Dentista'"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="records.length === 0">
                    <div class="py-12 text-center text-slate-400 text-xs">
                        No hay registros de historias clínicas cargados recientemente.
                    </div>
                </template>
            </div>
        </div>

    </div>

</div>

<!-- SCRIPT ALPINE.JS INTEGRADO CON SANCTUM -->
<script>
    function medicalRecordApp() {
        return {
            patients: [],
            dentists: [],
            records: [],
            selectedPatientAllergies: '',
            savingRecord: false,
            alertMessage: '',
            alertSuccess: true,
            lastSavedRecordId: null,

            form: {
                patient_id: '',
                dentist_id: '',
                appointment_id: null,
                diagnosis: '',
                evolution_notes: '',
                prescription: ''
            },

            async init() {
                await Promise.all([
                    this.fetchPatients(),
                    this.fetchDentists(),
                    this.fetchRecords()
                ]);
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

                        // Pre-seleccionar si hay dentistas
                        if (this.dentists.length > 0 && !this.form.dentist_id) {
                            this.form.dentist_id = this.dentists[0].id;
                        }
                    }
                } catch (e) {
                    console.error('Error cargando dentistas:', e);
                }
            },

            async fetchRecords() {
                try {
                    const res = await window.dentalApi.get('/api/medical-records?limit=20');
                    if (res && res.ok) {
                        const json = await res.json();
                        this.records = json.data || [];
                    }
                } catch (e) {
                    console.error('Error cargando historias clínicas:', e);
                }
            },

            onPatientSelected() {
                this.selectedPatientAllergies = '';
                if (!this.form.patient_id) return;

                const patient = this.patients.find(p => p.id == this.form.patient_id);
                if (patient && patient.allergies) {
                    this.selectedPatientAllergies = patient.allergies;
                }
            },

            async submitRecord() {
                this.savingRecord = true;
                this.alertMessage = '';
                this.lastSavedRecordId = null;

                try {
                    const res = await window.dentalApi.post('/api/medical-records', this.form);
                    const json = await res.json();

                    if (res.ok) {
                        this.alertSuccess = true;
                        this.alertMessage = '¡Historia clínica guardada exitosamente!';
                        this.lastSavedRecordId = json.data?.id;

                        this.form.diagnosis = '';
                        this.form.evolution_notes = '';
                        this.form.prescription = '';

                        await this.fetchRecords();
                    } else {
                        this.alertSuccess = false;
                        this.alertMessage = json.message || 'Error al guardar la historia clínica.';
                    }
                } catch (e) {
                    this.alertSuccess = false;
                    this.alertMessage = 'Error de conexión con el servidor.';
                } finally {
                    this.savingRecord = false;
                }
            },

            formatDate(str) {
                if (!str) return '';
                return str.substring(0, 16).replace('T', ' ');
            }
        };
    }
</script>
@endsection
