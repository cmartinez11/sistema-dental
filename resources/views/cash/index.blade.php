@extends('layouts.app')

@section('title', 'Caja & Cobranzas - Clínica Dental SJL')

@section('content')
<div x-data="cashModuleApp()" x-init="init()" class="space-y-6">

    <!-- CABECERA DE MÓDULO -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Caja Diaria & Control de Cobranzas</h1>
            <p class="text-xs text-slate-500">Gestión de ingresos en mostrador y amortización de cuotas de ortodoncia</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="refreshAll()" class="px-3 py-2 text-xs font-bold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition flex items-center gap-1.5">
                <span>🔄</span> Actualizar Datos
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

    <!-- ESTADO DE LA CAJA ACTIVA (BANNER SUPERIOR) -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 overflow-hidden relative">
        <template x-if="hasOpenRegister">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                        💰
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Turno de Caja Abierto
                            </span>
                            <span class="text-xs text-slate-400 font-medium" x-text="'Caja #' + currentRegister.id"></span>
                        </div>
                        <p class="text-xs text-slate-600 mt-1 font-medium">
                            Responsable: <span class="font-bold text-slate-800" x-text="currentRegister.user?.name || 'Recepcionista'"></span> •
                            Apertura: <span x-text="formatDate(currentRegister.opened_at)"></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Monto Inicial (Sencillo)</p>
                        <p class="text-base font-extrabold text-slate-800" x-text="'S/. ' + formatMoney(currentRegister.opening_amount)"></p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Recaudado Hoy</p>
                        <p class="text-lg font-extrabold text-emerald-600" x-text="'S/. ' + formatMoney(totalCollected)"></p>
                    </div>
                    <button @click="showCloseModal = true"
                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                        Arqueo y Cierre
                    </button>
                </div>
            </div>
        </template>

        <template x-if="!hasOpenRegister">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">No hay turno de caja abierto actualmente</h3>
                        <p class="text-xs text-slate-500">Debe realizar la apertura con el monto inicial para habilitar el cobro en mostrador.</p>
                    </div>
                </div>

                <form @submit.prevent="openRegister" class="flex items-center gap-2">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">S/.</span>
                        <input type="number" step="0.01" min="0" x-model="openingAmountInput" required placeholder="0.00"
                            class="pl-8 pr-3 py-2 w-32 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <button type="submit" :disabled="openingRegister"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-xl text-xs transition shadow-md shadow-emerald-600/20">
                        Abrir Turno
                    </button>
                </form>
            </div>
        </template>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ========================================================================= -->
        <!-- FORMULARIO DE COBRO Y AMORTIZACIÓN DE CUOTAS                              -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm lg:col-span-1">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                    Registrar Cobro / Transacción
                </h2>
                <span class="text-[11px] text-emerald-700 font-semibold bg-emerald-50 px-2.5 py-1 rounded-full">Recibo</span>
            </div>

            <form @submit.prevent="submitPayment" class="space-y-4">
                <!-- 1. Seleccionar Paciente -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Paciente:</label>
                    <select x-model="form.patient_id" @change="onPatientSelected()" required
                        class="w-full border border-slate-200 bg-white rounded-xl px-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">Seleccione el paciente emisor...</option>
                        <template x-for="p in patients" :key="p.id">
                            <option :value="p.id" x-text="p.first_name + ' ' + p.last_name + ' (DNI: ' + p.dni + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- 2. Cuotas Pendientes del Paciente (Ortodoncia / Fraccionado) -->
                <div x-show="pendingInstallments.length > 0" class="p-3 bg-blue-50/70 border border-blue-100 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-blue-800">Cuotas Pendientes de Ortodoncia:</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 bg-blue-200 text-blue-800 rounded" x-text="pendingInstallments.length"></span>
                    </div>

                    <div class="space-y-1.5 max-h-36 overflow-y-auto">
                        <template x-for="inst in pendingInstallments" :key="inst.id">
                            <div @click="selectInstallment(inst)"
                                :class="form.installment_id === inst.id ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 hover:bg-blue-100/50'"
                                class="p-2 rounded-lg text-[11px] flex items-center justify-between cursor-pointer border border-blue-100 transition">
                                <div>
                                    <span x-text="'Cuota #' + inst.installment_number"></span>
                                    <span class="text-[10px] block opacity-80" x-text="'Vence: ' + inst.due_date"></span>
                                </div>
                                <div class="text-right">
                                    <span x-text="'S/. ' + formatMoney(inst.amount)"></span>
                                    <span class="block text-[9px] uppercase font-bold"
                                        :class="form.installment_id === inst.id ? 'text-white' : (inst.status === 'overdue' ? 'text-red-500' : 'text-amber-500')"
                                        x-text="inst.status === 'overdue' ? 'Vencida' : 'Pendiente'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 3. Monto a Cobrar -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Monto a Cobrar (S/.):</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">S/.</span>
                        <input type="number" step="0.01" min="0.50" x-model="form.amount" required placeholder="0.00"
                            class="w-full pl-9 pr-3 py-2.5 border border-slate-200 rounded-xl text-xs font-extrabold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <!-- 4. Método de Pago -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Método de Pago:</label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button type="button" @click="form.payment_method = 'cash'"
                            :class="form.payment_method === 'cash' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-50 text-slate-700 border border-slate-200'"
                            class="py-2 px-2.5 rounded-xl transition text-center flex items-center justify-center gap-1.5">
                            💵 Efectivo
                        </button>
                        <button type="button" @click="form.payment_method = 'yape_plin'"
                            :class="form.payment_method === 'yape_plin' ? 'bg-purple-600 text-white font-bold' : 'bg-slate-50 text-slate-700 border border-slate-200'"
                            class="py-2 px-2.5 rounded-xl transition text-center flex items-center justify-center gap-1.5">
                            📱 Yape / Plin
                        </button>
                        <button type="button" @click="form.payment_method = 'card'"
                            :class="form.payment_method === 'card' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-50 text-slate-700 border border-slate-200'"
                            class="py-2 px-2.5 rounded-xl transition text-center flex items-center justify-center gap-1.5">
                            💳 Tarjeta POS
                        </button>
                        <button type="button" @click="form.payment_method = 'transfer'"
                            :class="form.payment_method === 'transfer' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-50 text-slate-700 border border-slate-200'"
                            class="py-2 px-2.5 rounded-xl transition text-center flex items-center justify-center gap-1.5">
                            🏦 BCP / BBVA
                        </button>
                    </div>
                </div>

                <!-- 5. Código de Operación o Voucher -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nro. de Operación / Voucher (Opcional):</label>
                    <input type="text" x-model="form.reference_code" placeholder="Ej: OP-984215 o Nro. Voucher"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <button type="submit" :disabled="!hasOpenRegister || processingPayment"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-emerald-600/25 transition transform active:scale-95 text-xs flex items-center justify-center gap-2 disabled:opacity-50">
                    <span x-show="!processingPayment">🧾 Procesar Ingreso a Caja</span>
                    <span x-show="processingPayment">Registrando transacción...</span>
                </button>
            </form>
        </div>

        <!-- ========================================================================= -->
        <!-- TABLA DE INGRESOS Y TRANSACCIONES RECIENTES EN CAJA                       -->
        <!-- ========================================================================= -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Transacciones & Pagos Registrados</h2>
                    <p class="text-xs text-slate-400">Historial reciente de ingresos vinculados a los turnos de recepción</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg" x-text="transactions.length + ' Pagos'"></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-3">Fecha y Hora</th>
                            <th class="py-3 px-3">Paciente</th>
                            <th class="py-3 px-3">Método</th>
                            <th class="py-3 px-3">Referencia</th>
                            <th class="py-3 px-3">Concepto</th>
                            <th class="py-3 px-3 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-slate-700">
                        <template x-for="trx in transactions" :key="trx.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3 px-3 text-slate-500 whitespace-nowrap" x-text="formatDate(trx.created_at)"></td>
                                <td class="py-3 px-3 font-semibold text-slate-800">
                                    <div x-text="trx.patient?.first_name + ' ' + trx.patient?.last_name"></div>
                                    <div class="text-[10px] text-slate-400" x-text="'DNI: ' + (trx.patient?.dni || '')"></div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200': trx.payment_method === 'cash',
                                            'bg-purple-50 text-purple-700 border border-purple-200': trx.payment_method === 'yape_plin',
                                            'bg-blue-50 text-blue-700 border border-blue-200': trx.payment_method === 'card',
                                            'bg-indigo-50 text-indigo-700 border border-indigo-200': trx.payment_method === 'transfer'
                                        }"
                                        x-text="formatMethod(trx.payment_method)">
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-slate-500" x-text="trx.reference_code || '—'"></td>
                                <td class="py-3 px-3">
                                    <template x-if="trx.installment_id">
                                        <span class="text-blue-600 font-bold" x-text="'Cuota #' + (trx.installment?.installment_number || trx.installment_id)"></span>
                                    </template>
                                    <template x-if="!trx.installment_id">
                                        <span class="text-slate-500">Servicio / Consulta</span>
                                    </template>
                                </td>
                                <td class="py-3 px-3 text-right font-extrabold text-emerald-600 whitespace-nowrap"
                                    x-text="'S/. ' + formatMoney(trx.amount)"></td>
                            </tr>
                        </template>

                        <template x-if="transactions.length === 0">
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400 text-xs">
                                    No se han registrado transacciones el día de hoy.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL DE CIERRE DE CAJA (ARQUEO) -->
    <div x-show="showCloseModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="showCloseModal = false" class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 text-xs">
            <h3 class="text-sm font-bold text-slate-800 mb-2">Arqueo y Cierre de Caja</h3>
            <p class="text-slate-500 mb-4">Ingrese el conteo total de efectivo físico en gaveta para cerrar el turno.</p>

            <form @submit.prevent="closeRegister" class="space-y-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Monto en Cierre (S/.):</label>
                    <input type="number" step="0.01" min="0" x-model="closingAmountInput" required
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="flex items-center gap-2 justify-end">
                    <button type="button" @click="showCloseModal = false" class="px-3 py-2 text-slate-500 font-bold">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700">Confirmar Cierre</button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- SCRIPT ALPINE.JS INTEGRADO CON SANCTUM -->
<script>
    function cashModuleApp() {
        return {
            hasOpenRegister: false,
            currentRegister: {},
            totalCollected: 0.00,
            openingAmountInput: '200.00',
            closingAmountInput: '',
            openingRegister: false,
            showCloseModal: false,

            patients: [],
            pendingInstallments: [],
            transactions: [],
            processingPayment: false,
            alertMessage: '',
            alertSuccess: true,

            form: {
                cash_register_id: null,
                patient_id: '',
                installment_id: null,
                amount: '',
                payment_method: 'cash',
                reference_code: ''
            },

            async init() {
                await Promise.all([
                    this.fetchCurrentRegister(),
                    this.fetchPatients(),
                    this.fetchTransactions()
                ]);
            },

            async refreshAll() {
                await Promise.all([
                    this.fetchCurrentRegister(),
                    this.fetchTransactions()
                ]);
            },

            async fetchCurrentRegister() {
                try {
                    const res = await window.dentalApi.get('/api/cash-registers/current');
                    if (res && res.ok) {
                        const json = await res.json();
                        this.hasOpenRegister = json.has_open_register;
                        this.currentRegister = json.register || {};
                        this.totalCollected = json.total_collected || 0.00;

                        if (this.currentRegister.id) {
                            this.form.cash_register_id = this.currentRegister.id;
                        }
                    }
                } catch (e) {
                    console.error('Error cargando caja actual:', e);
                }
            },

            async openRegister() {
                this.openingRegister = true;
                this.alertMessage = '';
                try {
                    const res = await window.dentalApi.post('/api/cash-registers/open', {
                        opening_amount: parseFloat(this.openingAmountInput)
                    });
                    const json = await res.json();

                    if (res.ok) {
                        this.alertSuccess = true;
                        this.alertMessage = json.message || 'Caja abierta con éxito.';
                        await this.fetchCurrentRegister();
                    } else {
                        this.alertSuccess = false;
                        this.alertMessage = json.message || 'No se pudo abrir la caja.';
                    }
                } catch (e) {
                    this.alertSuccess = false;
                    this.alertMessage = 'Error de conexión con el servidor.';
                } finally {
                    this.openingRegister = false;
                }
            },

            async closeRegister() {
                if (!this.currentRegister.id) return;
                try {
                    const res = await window.dentalApi.post(`/api/cash-registers/${this.currentRegister.id}/close`, {
                        closing_amount: parseFloat(this.closingAmountInput)
                    });
                    const json = await res.json();

                    if (res.ok) {
                        this.alertSuccess = true;
                        this.alertMessage = json.message || 'Turno cerrado con éxito.';
                        this.showCloseModal = false;
                        await this.fetchCurrentRegister();
                    } else {
                        this.alertSuccess = false;
                        this.alertMessage = json.message || 'Error al cerrar caja.';
                    }
                } catch (e) {
                    this.alertSuccess = false;
                    this.alertMessage = 'Error al cerrar caja.';
                }
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

            async fetchTransactions() {
                try {
                    const res = await window.dentalApi.get('/api/transactions?limit=25');
                    if (res && res.ok) {
                        const json = await res.json();
                        this.transactions = json.data || [];
                    }
                } catch (e) {
                    console.error('Error cargando transacciones:', e);
                }
            },

            async onPatientSelected() {
                this.form.installment_id = null;
                this.pendingInstallments = [];

                if (!this.form.patient_id) return;

                try {
                    const res = await window.dentalApi.get(`/api/patients/${this.form.patient_id}/installments`);
                    if (res && res.ok) {
                        const json = await res.json();
                        this.pendingInstallments = json.data || [];
                    }
                } catch (e) {
                    console.error('Error cargando cuotas:', e);
                }
            },

            selectInstallment(inst) {
                if (this.form.installment_id === inst.id) {
                    this.form.installment_id = null;
                    this.form.amount = '';
                } else {
                    this.form.installment_id = inst.id;
                    this.form.amount = inst.amount;
                }
            },

            async submitPayment() {
                if (!this.form.cash_register_id) {
                    this.alertSuccess = false;
                    this.alertMessage = 'Debe abrir una caja antes de procesar cobros.';
                    return;
                }

                this.processingPayment = true;
                this.alertMessage = '';

                try {
                    const res = await window.dentalApi.post('/api/transactions', this.form);
                    const json = await res.json();

                    if (res.ok) {
                        this.alertSuccess = true;
                        this.alertMessage = '¡Pago registrado exitosamente por S/. ' + this.formatMoney(this.form.amount) + '!';
                        this.form.amount = '';
                        this.form.installment_id = null;
                        this.form.reference_code = '';
                        await Promise.all([
                            this.fetchCurrentRegister(),
                            this.fetchTransactions(),
                            this.onPatientSelected()
                        ]);
                    } else {
                        this.alertSuccess = false;
                        this.alertMessage = json.message || 'Error al procesar el pago.';
                    }
                } catch (e) {
                    this.alertSuccess = false;
                    this.alertMessage = 'Error de conexión con el servidor.';
                } finally {
                    this.processingPayment = false;
                }
            },

            formatMoney(val) {
                if (!val) return '0.00';
                return parseFloat(val).toFixed(2);
            },

            formatDate(str) {
                if (!str) return '';
                return str.substring(0, 16).replace('T', ' ');
            },

            formatMethod(m) {
                const map = {
                    cash: 'Efectivo',
                    yape_plin: 'Yape / Plin',
                    card: 'Tarjeta POS',
                    transfer: 'Transferencia'
                };
                return map[m] || m;
            }
        };
    }
</script>
@endsection
