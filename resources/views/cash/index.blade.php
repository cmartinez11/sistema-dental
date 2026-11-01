<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Dental - Módulo de Caja y Pagos</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js para interactividad reactiva -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-100 font-sans antialiased">

    <div class="min-h-screen flex flex-col">
        <!-- Barra de Navegación Superior -->
        <header class="bg-emerald-600 text-white shadow">
            <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                <h1 class="text-xl font-bold">Clínica Dental - Caja y Transacciones</h1>
                <span class="text-sm bg-emerald-700 px-3 py-1 rounded-full">Rol: Recepcionista / Caja</span>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="max-w-7xl mx-auto px-4 py-8 flex-grow w-full" x-data="cashApp()">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Formulario de Registro de Pago -->
                <div class="bg-white p-6 rounded-lg shadow-md md:col-span-1">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Registrar Nuevo Pago</h2>

                    <!-- Alerta de éxito o error -->
                    <template x-if="message">
                        <div class="mb-4 p-3 rounded text-sm"
                            :class="success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            x-text="message"></div>
                    </template>

                    <form @submit.prevent="submitTransaction">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ID de Caja Abierta:</label>
                            <input type="number" x-model="form.cash_register_id"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ID del Paciente:</label>
                            <input type="number" x-model="form.patient_id"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">ID de Cuota (Opcional):</label>
                            <input type="number" x-model="form.installment_id"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                placeholder="Ej. Cuota de Ortodoncia">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Monto (S/.):</label>
                            <input type="number" step="0.01" x-model="form.amount"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Método de Pago:</label>
                            <select x-model="form.payment_method"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                required>
                                <option value="">Seleccione método...</option>
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="yape_plin">Yape / Plin</option>
                                <option value="transfer">Transferencia Bancaria</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Código de Operación /
                                Voucher:</label>
                            <input type="text" x-model="form.reference_code"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                placeholder="Opcional">
                        </div>

                        <button type="submit"
                            class="w-full bg-emerald-600 text-white font-bold py-2 px-4 rounded hover:bg-emerald-700 transition">
                            Procesar Pago
                        </button>
                    </form>
                </div>

                <!-- Panel de Resumen / Últimos Ingresos -->
                <div class="bg-white p-6 rounded-lg shadow-md md:col-span-2">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Transacciones de Caja Recientes</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Método</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Referencia</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Monto</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado de Cuota</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">Yape / Plin</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">OP-987654321</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-emerald-600 font-bold">S/. 150.00</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Pagado
                                            (Actualizado)</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Script Reactivo con Alpine.js -->
    <script>
        function cashApp() {
            return {
                form: {
                    cash_register_id: '',
                    patient_id: '',
                    installment_id: '',
                    amount: '',
                    payment_method: '',
                    reference_code: ''
                },
                message: '',
                success: false,
                async submitTransaction() {
                    try {
                        let response = await fetch('/api/transactions', {
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
                            this.message = '¡Pago registrado e ingresado a caja exitosamente!';
                            this.form = {
                                cash_register_id: this.form.cash_register_id, // Mantiene la caja activa
                                patient_id: '',
                                installment_id: '',
                                amount: '',
                                payment_method: '',
                                reference_code: ''
                            };
                        } else {
                            this.success = false;
                            this.message = data.message || 'Error al procesar la transacción.';
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