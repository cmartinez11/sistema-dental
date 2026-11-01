<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Dental - Módulo de Citas</title>
    <!-- Tailwind CSS CDN para desarrollo rápido -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js para interactividad reactiva -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-100 font-sans antialiased">

    <div class="min-h-screen flex flex-col">
        <!-- Barra de Navegación Superior -->
        <header class="bg-blue-600 text-white shadow">
            <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                <h1 class="text-xl font-bold">Clínica Dental - Panel de Recepción</h1>
                <span class="text-sm bg-blue-700 px-3 py-1 rounded-full">Rol: Recepcionista</span>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="max-w-7xl mx-auto px-4 py-8 flex-grow w-full" x-data="appointmentApp()">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Formulario para Agendar Cita -->
                <div class="bg-white p-6 rounded-lg shadow-md md:col-span-1">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Agendar Nueva Cita</h2>

                    <!-- Alerta de éxito o error -->
                    <template x-if="message">
                        <div class="mb-4 p-3 rounded text-sm"
                            :class="success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            x-text="message"></div>
                    </template>

                    <form @submit.prevent="submitAppointment">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ID Paciente:</label>
                            <input type="number" x-model="form.patient_id"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ID Dentista:</label>
                            <input type="number" x-model="form.dentist_id"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Fecha y Hora de Inicio:</label>
                            <input type="datetime-local" x-model="form.start_time"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Fecha y Hora de Fin:</label>
                            <input type="datetime-local" x-model="form.end_time"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>

                        <button type="submit"
                            class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition">
                            Guardar Cita
                        </button>
                    </form>
                </div>

                <!-- Listado / Agenda del Día -->
                <div class="bg-white p-6 rounded-lg shadow-md md:col-span-2">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Agenda y Próximas Citas</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Paciente</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Dentista</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Horario</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">Juan Pérez</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Dr. House</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Mañana, 10:00 AM</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Programada</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Script de Alpine.js conectado a la API -->
    <script>
        function appointmentApp() {
            return {
                form: {
                    patient_id: '',
                    dentist_id: '',
                    start_time: '',
                    end_time: ''
                },
                message: '',
                success: false,
                async submitAppointment() {
                    try {
                        let response = await fetch('/api/appointments', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                // Aquí se incluiría el token de Sanctum en producción
                            },
                            body: JSON.stringify(this.form)
                        });

                        let data = await response.json();

                        if (response.ok) {
                            this.success = true;
                            this.message = '¡Cita agendada exitosamente!';
                            this.form = { patient_id: '', dentist_id: '', start_time: '', end_time: '' };
                        } else {
                            this.success = false;
                            this.message = data.message || 'Error al procesar la cita (posible cruce de horario).';
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