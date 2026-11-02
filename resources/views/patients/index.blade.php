@extends('layouts.app')

@section('title', 'Directorio de Pacientes - Clínica Dental SJL')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="patientApp()" x-init="init()">

    <!-- Formulario de Registro de Paciente -->
    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm lg:col-span-1">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                Registrar Nuevo Paciente
            </h2>
            <span class="text-[11px] text-blue-700 font-semibold bg-blue-50 px-2.5 py-1 rounded-full">Admisión</span>
        </div>

        <!-- Alertas -->
        <template x-if="message">
            <div class="mb-4 p-3 rounded-xl text-xs font-semibold flex items-center justify-between"
                :class="success ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'">
                <span x-text="message"></span>
                <button @click="message = ''" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
        </template>

        <form @submit.prevent="submitPatient" class="space-y-3.5">
            <div>
                <label class="block text-slate-700 text-xs font-bold mb-1">Nombres:</label>
                <input type="text" x-model="form.first_name" required placeholder="Ej: Roberto Carlos"
                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-slate-700 text-xs font-bold mb-1">Apellidos:</label>
                <input type="text" x-model="form.last_name" required placeholder="Ej: Paredes Quispe"
                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-slate-700 text-xs font-bold mb-1">DNI / Carné de Extranjería:</label>
                <input type="text" maxlength="15" x-model="form.dni" required placeholder="8 dígitos numéricos"
                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-slate-700 text-xs font-bold mb-1">Teléfono:</label>
                    <input type="text" x-model="form.phone" placeholder="987654321"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-700 text-xs font-bold mb-1">Correo Electrónico:</label>
                    <input type="email" x-model="form.email" placeholder="paciente@correo.com"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none">
                </div>
            </div>

            <!-- ALERGIAS Y ANTECEDENTES MÉDICOS CRÍTICOS -->
            <div>
                <label class="block text-slate-700 text-xs font-bold mb-1">Alergias o Antecedentes Médicos:</label>
                <textarea x-model="form.allergies" rows="2" placeholder="Ej: Alérgico a penicilina, hipertenso, diabético..."
                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none bg-amber-50/30"></textarea>
            </div>

            <div>
                <label class="block text-slate-700 text-xs font-bold mb-1">Dirección de Domicilio:</label>
                <input type="text" x-model="form.address" placeholder="Ej: Av. Próceres de la Independencia 1420, SJL"
                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none">
            </div>

            <button type="submit" :disabled="submitting"
                class="w-full bg-[#1c71fe] hover:bg-blue-600 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition shadow-md shadow-blue-500/20 transform active:scale-95 disabled:opacity-50">
                <span x-show="!submitting">Guardar Ficha del Paciente</span>
                <span x-show="submitting">Registrando en base de datos...</span>
            </button>
        </form>
    </div>

    <!-- Listado y Búsqueda en Vivo -->
    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm lg:col-span-2">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4 pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-sm font-bold text-slate-800">Directorio de Pacientes</h2>
                <p class="text-xs text-slate-400">Padrón de afiliados y pacientes atendidos en clínica</p>
            </div>
            <!-- Input de Búsqueda en Vivo -->
            <div class="relative w-full sm:w-72">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchPatients()"
                    placeholder="Buscar por DNI o nombres..."
                    class="border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs w-full focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <th class="py-3 px-3">DNI</th>
                        <th class="py-3 px-3">Paciente</th>
                        <th class="py-3 px-3">Contacto</th>
                        <th class="py-3 px-3">Alertas Médicas</th>
                        <th class="py-3 px-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-slate-700">
                    <template x-for="patient in patients" :key="patient.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-3 font-bold text-slate-800" x-text="patient.dni"></td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-800" x-text="patient.first_name + ' ' + patient.last_name"></div>
                                <div class="text-[10px] text-slate-400" x-text="patient.address || 'San Juan de Lurigancho'"></div>
                            </td>
                            <td class="py-3 px-3">
                                <div x-text="patient.phone || 'S/N'"></div>
                                <div class="text-[10px] text-slate-400" x-text="patient.email || ''"></div>
                            </td>
                            <td class="py-3 px-3">
                                <template x-if="patient.allergies">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-50 text-red-700 border border-red-200"
                                        x-text="'⚠️ ' + patient.allergies"></span>
                                </template>
                                <template x-if="!patient.allergies">
                                    <span class="text-slate-400 text-[11px]">Sin alergias</span>
                                </template>
                            </td>
                            <td class="py-3 px-3 text-right whitespace-nowrap">
                                <a :href="'/dashboard/appointments'"
                                    class="text-blue-600 hover:text-blue-800 font-bold hover:underline">
                                    Citar
                                </a>
                            </td>
                        </tr>
                    </template>
                    <template x-if="patients.length === 0">
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                No se encontraron pacientes registrados con ese criterio.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    function patientApp() {
        return {
            patients: [],
            searchQuery: '',
            submitting: false,
            message: '',
            success: false,

            form: {
                first_name: '',
                last_name: '',
                dni: '',
                phone: '',
                email: '',
                address: '',
                allergies: ''
            },

            async init() {
                await this.fetchPatients();
            },

            async fetchPatients() {
                try {
                    const res = await window.dentalApi.get('/api/patients', { search: this.searchQuery });
                    if (res && res.ok) {
                        const data = await res.json();
                        this.patients = data.data || [];
                    }
                } catch (error) {
                    console.error('Error al cargar pacientes:', error);
                }
            },

            async submitPatient() {
                this.submitting = true;
                this.message = '';

                try {
                    const res = await window.dentalApi.post('/api/patients', this.form);
                    const data = await res.json();

                    if (res.ok) {
                        this.success = true;
                        this.message = '¡Paciente registrado con éxito!';
                        this.form = { first_name: '', last_name: '', dni: '', phone: '', email: '', address: '', allergies: '' };
                        await this.fetchPatients();
                    } else {
                        this.success = false;
                        this.message = data.message || 'Error al registrar el paciente.';
                    }
                } catch (error) {
                    this.success = false;
                    this.message = 'Error de conexión con el servidor.';
                } finally {
                    this.submitting = false;
                }
            }
        };
    }
</script>
@endsection
