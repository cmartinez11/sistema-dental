<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Dental - Módulo de Historias Clínicas</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js para interactividad reactiva -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-100 font-sans antialiased">

    <div class="min-h-screen flex flex-col">
        <!-- Barra de Navegación Superior -->
        <header class="bg-indigo-600 text-white shadow">
            <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                <h1 class="text-xl font-bold">Clínica Dental - Panel Clínico (Odontología)</h1>
                <span class="text-sm bg-indigo-700 px-3 py-1 rounded-full">Rol: Dentista / Especialista</span>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="max-w-7xl mx-auto px-4 py-8 flex-grow w-full" x-data="medicalRecordApp()">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Formulario de Registro Clínico -->
                <div class="bg-white p-6 rounded-lg shadow-md md:col-span-1">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Nueva Nota de Evolución</h2>

                    <!-- Alerta de éxito o error -->
                    <template x-if="message">
                        <div class="mb-4 p-3 rounded text-sm"
                            :class="success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            x-text="message"></div>
                    </template>

                    <form @submit.prevent="submitMedicalRecord">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ID del Paciente:</label>
                            <input type="number" x-model="form.patient_id"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ID del Dentista:</label>
                            <input type="number" x-model="form.dentist_id"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ID de Cita (Opcional):</label>
                            <input type="number" x-model="form.appointment_id"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Diagnóstico:</label>
                            <textarea x-model="form.diagnosis" rows="3"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Ej. Caries profunda en pieza 36" required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Notas de Evolución:</label>
                            <textarea x-model="form.evolution_notes" rows="3"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Detalle del procedimiento realizado..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Receta Médica:</label>
                            <textarea x-model="form.prescription" rows="2"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Medicamentos y dosis..."></textarea>
                        </div>

                        <button type="submit"
                            class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700 transition">
                            Guardar Historia Clínica
                        </button>
                    </form>
                </div>

                <!-- Panel de Resumen / Historial del Paciente -->
                <div class="bg-white p-6 rounded-lg shadow-md md:col-span-2">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Historial y Notas Recientes</h2>
                    <div class="space-y-4">
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-bold text-gray-900">Paciente: Ana Torres</span>
                                <span class="text-xs text-gray-500">Fecha: Hoy, 11:30 AM</span>
                            </div>
                            <p class="text-sm text-gray-700 mb-1"><strong>Diagnóstico:</strong> Caries profunda en pieza
                                36</p>
                            <p class="text-sm text-gray-700 mb-1"><strong>Evolución:</strong> Se realizó exodoncia sin
                                complicaciones.</p>
                            <p class="text-sm text-indigo-600"><strong>Receta:</strong> Amoxicilina 500mg cada 8 horas
                                por 5 días.</p>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Script Reactivo con Alpine.js -->
    <script>
        function medicalRecordApp() {
            return {
                form: {
                    patient_id: '',
                    dentist_id: '',
                    appointment_id: '',
                    diagnosis: '',
                    evolution_notes: '',
                    prescription: ''
                },
                message: '',
                success: false,
                async submitMedicalRecord() {
                    try {
                        let response = await fetch('/api/medical-records', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(this.form)
                        });

                        let data = await response.json();

                        if (response.ok) {
                            this.success = true;
                            this.message = '¡Historia clínica y notas de evolución guardadas con éxito!';
                            this.form = {
                                patient_id: '',
                                dentist_id: this.form.dentist_id, // Mantiene el dentista activo
                                appointment_id: '',
                                diagnosis: '',
                                evolution_notes: '',
                                prescription: ''
                            };
                        } else {
                            this.success = false;
                            this.message = data.message || 'Acceso denegado o error al registrar.';
                        }
                    } catch (error) {
                        this.success = false;
                        this.message = 'Error de conexión con el servidor.';
                    }
                }
            }
        }
    </script>
</body>

</html>