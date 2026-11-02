@extends('layouts.app')

@section('title', 'Panel General - Clínica Dental San Juan de Lurigancho')

@section('content')
<div class="space-y-6" x-data="{
    filterDate: '{{ now()->format('Y-m-d') }}',
    refreshing: false,
    async refreshData() {
        this.refreshing = true;
        window.location.reload();
    }
}">

    <!-- ========================================================================= -->
    <!-- 1. SECCIÓN SUPERIOR DE BIENVENIDA Y PERFIL                                -->
    <!-- ========================================================================= -->
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-700 via-indigo-700 to-blue-800 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-blue-900/10 border border-blue-600/30">
        <!-- Decoraciones de fondo abstracto -->
        <div class="absolute -right-12 -top-12 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute right-36 -bottom-16 w-56 h-56 bg-cyan-400/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-xs font-medium text-blue-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Sede San Juan de Lurigancho • Lima, Perú</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    ¡Bienvenido(a), {{ auth()->user()->name ?? 'Doctor(a)' }}! 👋
                </h1>
                <p class="text-blue-100/90 text-sm max-w-2xl leading-relaxed">
                    Panel de control general de la clínica. Visualiza el estado operativo de los consultorios, agenda de sillones y balance de caja en tiempo real.
                </p>
            </div>

            <!-- Rol del Usuario y Fecha -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-3">
                <div class="bg-white/10 backdrop-blur-md border border-white/15 px-4 py-3 rounded-2xl text-left">
                    <span class="block text-[11px] uppercase tracking-wider text-blue-200 font-semibold">Rol Asignado</span>
                    <span class="text-sm font-bold text-white capitalize">
                        {{ auth()->user()->role instanceof \App\Enums\UserRole ? auth()->user()->role->label() : (auth()->user()->role ?? 'Administrador') }}
                    </span>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/15 px-4 py-3 rounded-2xl text-left">
                    <span class="block text-[11px] uppercase tracking-wider text-blue-200 font-semibold">Fecha Actual</span>
                    <span class="text-sm font-bold text-white">
                        {{ \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM, YYYY') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. TARJETAS DE INDICADORES CLAVE (KPIS)                                   -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <!-- KPI 1: Pacientes Registrados -->
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex items-center justify-between group">
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Pacientes</p>
                <h3 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                    {{ number_format($totalPatients ?? 0) }}
                </h3>
                <p class="text-[11px] font-medium text-emerald-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    <span>Historiales clínicos activos</span>
                </p>
            </div>
            <div class="w-13 h-13 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center p-3.5 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>

        <!-- KPI 2: Citas Programadas Hoy -->
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex items-center justify-between group">
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Citas para Hoy</p>
                <h3 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                    {{ number_format($todayAppointments ?? 0) }}
                </h3>
                <p class="text-[11px] font-medium text-blue-600 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    <span>Sillones clínicos 1 a 4</span>
                </p>
            </div>
            <div class="w-13 h-13 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center p-3.5 group-hover:bg-cyan-600 group-hover:text-white transition-colors duration-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>

        <!-- KPI 3: Dentistas Activos -->
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex items-center justify-between group">
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Dentistas Activos</p>
                <h3 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                    {{ number_format($totalDentists ?? 0) }}
                </h3>
                <p class="text-[11px] font-medium text-violet-600 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                    <span>Cirujanos y especialistas COP</span>
                </p>
            </div>
            <div class="w-13 h-13 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center p-3.5 group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
        </div>

        <!-- KPI 4: Ingresos de Caja del Día -->
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 flex items-center justify-between group">
            <div class="space-y-1">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ingresos del Día</p>
                <h3 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                    S/ {{ number_format($todayIncome ?? 0, 2) }}
                </h3>
                <p class="text-[11px] font-medium text-emerald-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Caja diaria y cuotas recibidas</span>
                </p>
            </div>
            <div class="w-13 h-13 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center p-3.5 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 3. ACCESOS RÁPIDOS PRINCIPALES (QUICK ACCESS CARDS)                       -->
    <!-- ========================================================================= -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Accesos Rápidos de Operación</h2>
                <p class="text-xs text-slate-400">Acciones directas para agilizar la atención de recepción, consultorio y caja</p>
            </div>
            <button @click="refreshData()" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-blue-600 bg-white hover:bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl transition shadow-xs">
                <svg class="w-3.5 h-3.5" :class="refreshing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Actualizar Datos</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            <!-- ACCESO 1: Registrar Paciente -->
            <a href="/dashboard/patients"
               class="group relative bg-white p-6 rounded-3xl border border-slate-100 hover:border-blue-200 shadow-sm hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-300 flex flex-col justify-between overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-blue-50/70 rounded-full group-hover:scale-125 transition-transform duration-500 pointer-events-none"></div>

                <div class="space-y-4 relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-600/30 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-800 group-hover:text-blue-600 transition-colors">
                            Registrar Paciente
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Alta de nuevos pacientes, validación de DNI, registro de antecedentes médicos y control de alergias.
                        </p>
                    </div>
                </div>

                <div class="pt-5 mt-4 border-t border-slate-100/80 flex items-center justify-between text-xs font-bold text-blue-600 relative z-10">
                    <span>Ir al Directorio de Pacientes</span>
                    <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </div>
            </a>

            <!-- ACCESO 2: Programar Cita -->
            <a href="/dashboard/appointments"
               class="group relative bg-white p-6 rounded-3xl border border-slate-100 hover:border-cyan-200 shadow-sm hover:shadow-xl hover:shadow-cyan-900/5 transition-all duration-300 flex flex-col justify-between overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-cyan-50/70 rounded-full group-hover:scale-125 transition-transform duration-500 pointer-events-none"></div>

                <div class="space-y-4 relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-cyan-600 text-white flex items-center justify-center shadow-md shadow-cyan-600/30 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-800 group-hover:text-cyan-600 transition-colors">
                            Programar Cita
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Asignación de citas odontológicas, selección de sillones (1 al 4) y prevención automática de cruces.
                        </p>
                    </div>
                </div>

                <div class="pt-5 mt-4 border-t border-slate-100/80 flex items-center justify-between text-xs font-bold text-cyan-600 relative z-10">
                    <span>Abrir Agenda de Citas</span>
                    <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </div>
            </a>

            <!-- ACCESO 3: Registrar Pago -->
            <a href="/dashboard/cash"
               class="group relative bg-white p-6 rounded-3xl border border-slate-100 hover:border-emerald-200 shadow-sm hover:shadow-xl hover:shadow-emerald-900/5 transition-all duration-300 flex flex-col justify-between overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-emerald-50/70 rounded-full group-hover:scale-125 transition-transform duration-500 pointer-events-none"></div>

                <div class="space-y-4 relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-600/30 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-800 group-hover:text-emerald-600 transition-colors">
                            Registrar Pago
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Control de caja chica, liquidación de cuotas de ortodoncia, pagos vía Yape, Plin, Tarjeta POS o Efectivo.
                        </p>
                    </div>
                </div>

                <div class="pt-5 mt-4 border-t border-slate-100/80 flex items-center justify-between text-xs font-bold text-emerald-600 relative z-10">
                    <span>Ir a Caja & Cobranzas</span>
                    <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </div>
            </a>

        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. SECCIÓN COMPLEMENTARIA: CONSULTORIO & SILLONES ODONTOLÓGICOS          -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Banner de Módulo Clínico / Recetas PDF -->
        <div class="bg-gradient-to-br from-indigo-900 to-slate-900 rounded-3xl p-6 text-white flex flex-col justify-between shadow-sm relative overflow-hidden lg:col-span-1">
            <div class="absolute -right-8 -top-8 w-40 h-40 bg-indigo-500/20 rounded-full blur-xl pointer-events-none"></div>

            <div class="space-y-3 relative z-10">
                <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-indigo-300 bg-white/10 px-3 py-1 rounded-full border border-white/10">
                    Consultorio Dental
                </span>
                <h3 class="text-xl font-extrabold tracking-tight">Fichas de Evolución & Recetas Oficiales</h3>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Accede a la estación del odontólogo para ingresar el diagnóstico, registrar procedimientos realizados y emitir la receta médica oficial en PDF con un clic.
                </p>
            </div>

            <div class="pt-6 relative z-10">
                <a href="/dashboard/medical-records"
                   class="inline-flex items-center justify-center gap-2 w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 px-4 rounded-xl text-xs transition shadow-lg shadow-indigo-600/30">
                    <span>🩺 Abrir Historias Clínicas</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Estado de Sillones Clínicos (1 a 4) -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Disponibilidad de Sillones Clínicos</h3>
                    <p class="text-xs text-slate-400">Distribución de unidades odontológicas en sede San Juan de Lurigancho</p>
                </div>
                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200/60">
                    Operación Normal
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                <!-- Sillón 1 -->
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 text-center space-y-1.5 hover:border-blue-200 transition">
                    <span class="inline-block p-2 rounded-xl bg-blue-100 text-blue-700 font-bold text-xs">Sillón #1</span>
                    <p class="text-xs font-bold text-slate-700">Ortodoncia</p>
                    <span class="inline-flex items-center gap-1 text-[10px] text-emerald-600 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Disponible
                    </span>
                </div>

                <!-- Sillón 2 -->
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 text-center space-y-1.5 hover:border-cyan-200 transition">
                    <span class="inline-block p-2 rounded-xl bg-cyan-100 text-cyan-700 font-bold text-xs">Sillón #2</span>
                    <p class="text-xs font-bold text-slate-700">Endodoncia</p>
                    <span class="inline-flex items-center gap-1 text-[10px] text-emerald-600 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Disponible
                    </span>
                </div>

                <!-- Sillón 3 -->
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 text-center space-y-1.5 hover:border-indigo-200 transition">
                    <span class="inline-block p-2 rounded-xl bg-indigo-100 text-indigo-700 font-bold text-xs">Sillón #3</span>
                    <p class="text-xs font-bold text-slate-700">Rehabilitación</p>
                    <span class="inline-flex items-center gap-1 text-[10px] text-emerald-600 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Disponible
                    </span>
                </div>

                <!-- Sillón 4 -->
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/70 text-center space-y-1.5 hover:border-purple-200 transition">
                    <span class="inline-block p-2 rounded-xl bg-purple-100 text-purple-700 font-bold text-xs">Sillón #4</span>
                    <p class="text-xs font-bold text-slate-700">Cirugía / Odontoped.</p>
                    <span class="inline-flex items-center gap-1 text-[10px] text-emerald-600 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Disponible
                    </span>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
