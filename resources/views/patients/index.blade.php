@extends('layouts.app')

@section('title', 'Gestión de Pacientes - Clínica Dental')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="patientApp()">

        <!-- Formulario de Registro de Paciente -->
        <div class="bg-white p-6 rounded-lg shadow-md lg:col-span-1">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Registrar Nuevo Paciente</h2>

            <!-- Alertas -->
            <template x-if="message">
                <div class="mb-4 p-3 rounded text-sm"
                    :class="success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" x-text="message"></div>
            </template>

            <form @submit.prevent="submitPatient">
                <div class="mb-3">
                    <label class="block text-gray-700 text-xs font-bold mb-1">Nombres:</label>
                    <input type="text" x-model="form.first_name"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 text-xs font-bold mb-1">Apellidos:</label>
                    <input type="text" x-model="form.last_name"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 text-xs font-bold mb-1">DNI:</label>
                    <input type="text" maxlength="15" x-model="form.dni"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 text-xs font-bold mb-1">Teléfono / Celular:</label>
                    <input type="text" x-model="form.phone"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-3">
                    <label class="block text-gray-700 text-xs font-bold mb-1">Correo Electrónico:</label>
                    <input type="email" x-model="form.email"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-xs font-bold mb-1">Dirección:</label>
                    <textarea x-model="form.address" rows="2"
                        class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm hover:bg-blue-700 transition">
                    Guardar Paciente
                </button>
            </form>
        </div>

        <!-- Listado y Búsqueda en Vivo -->
        <div class="bg-white p-6 rounded-lg shadow-md lg:col-span-2">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                <h2 class="text-lg font-semibold text-gray-800">Directorio de Pacientes</h2>
                <!-- Input de Búsqueda en Vivo -->
                <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchPatients()"
                    placeholder="Buscar por DNI, nombre o apellido..."
                    class="border rounded px-3 py-2 text-sm w-full sm:w-72 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">DNI</th>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Teléfono</th>
                            <th class="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="patient in patients" :key="patient.id">
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900" x-text="patient.dni"></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900"
                                        x-text="patient.first_name + ' ' + patient.last_name"></div>
                                    <div class="text-xs text-gray-500" x-text="patient.email || 'Sin correo'"></div>
                                </td>
                                <td class="px-4 py-3 text-gray-600" x-text="patient.phone || 'N/A'"></td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs">
                                    <span class="text-blue-600 hover:text-blue-900 font-medium cursor-pointer">Ver
                                        Historial</span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="patients.length === 0">
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500 text-sm">No se encontraron
                                    pacientes registrados.</td>
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
                form: {
                    first_name: '',
                    last_name: '',
                    dni: '',
                    phone: '',
                    email: '',
                    address: ''
                },
                message: '',
                success: false,
                async init() {
                    await this.fetchPatients();
                },
                async fetchPatients() {
                    try {
                        let response = await fetch(`/api/patients?search=${this.searchQuery}`, {
                            headers: {
                                'Accept': 'application/json',
                                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
                            }
                        });
                        let data = await response.json();
                        this.patients = data.data || [];
                    } catch (error) {
                        console.error('Error al cargar pacientes:', error);
                    }
                },
                async submitPatient() {
                    try {
                        let response = await fetch('/api/patients', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
                            },
                            body: JSON.stringify(this.form)
                        });

                        let data = await response.json();

                        if (response.ok) {
                            this.success = true;
                            this.message = data.message;
                            this.form = { first_name: '', last_name: '', dni: '', phone: '', email: '', address: '' };
                            await this.fetchPatients();
                        } else {
                            this.success = false;
                            this.message = data.message || 'Error al registrar el paciente.';
                        }
                    } catch (error) {
                        this.success = false;
                        this.message = 'Error de conexión con el servidor.';
                    }
                }
            }
        }
    </script>
@endsection