<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistema de Gestión Dental')</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-100 font-sans antialiased">

    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">

        <!-- SIDEBAR LATERAL FIJO -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transition-transform duration-300 ease-in-out md:translate-x-0 md:sticky md:top-0 h-screen flex flex-col shadow-lg shrink-0">

            <!-- Logo / Título de la Clínica -->
            <div class="flex items-center justify-center h-16 bg-gray-950 px-4 shrink-0">
                <span class="text-lg font-bold tracking-wider text-blue-400">🦷 Clínica Dental SJL</span>
            </div>

            <!-- Menú de Navegación por Roles -->
            <nav class="flex-grow px-4 py-6 space-y-2 text-sm overflow-y-auto">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Menú Principal</p>

                <!-- Opciones para Recepción y Admin -->
                @if(auth()->check() && (auth()->user()->role === 'receptionist' || auth()->user()->role === 'Recepcionista' || auth()->user()->role === 'admin'))
                    <a href="/dashboard/appointments"
                        class="flex items-center px-3 py-2.5 rounded-lg hover:bg-gray-800 transition text-gray-300 hover:text-white">
                        <span class="mr-3">📅</span> Gestión de Citas
                    </a>
                    <a href="/dashboard/cash"
                        class="flex items-center px-3 py-2.5 rounded-lg hover:bg-gray-800 transition text-gray-300 hover:text-white">
                        <span class="mr-3">💰</span> Caja y Pagos
                    </a>
                @endif

                <!-- Opciones para Dentistas y Admin -->
                @if(auth()->check() && (auth()->user()->role === 'dentist' || auth()->user()->role === 'Dentista' || auth()->user()->role === 'admin'))
                    <a href="/dashboard/medical-records"
                        class="flex items-center px-3 py-2.5 rounded-lg hover:bg-gray-800 transition text-gray-300 hover:text-white">
                        <span class="mr-3">📋</span> Historias Clínicas
                    </a>
                @endif
            </nav>

            <!-- Sección de Usuario y Cerrar Sesión (Siempre visible abajo en el sidebar) -->
            <div class="p-4 bg-gray-950 border-t border-gray-800 shrink-0">
                <div class="flex items-center mb-3">
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name ?? 'Usuario' }}</p>
                        <p class="text-xs text-blue-400 uppercase">{{ auth()->user()->role ?? 'Invitado' }}</p>
                    </div>
                </div>
                <form action="/logout" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-2 px-3 rounded transition text-center">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Barra Superior Responsive -->
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 sticky top-0 z-40">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h2 class="text-lg font-semibold text-gray-800">Panel de Control General</h2>
                <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-1 rounded-full">San Juan de
                    Lurigancho</span>
            </header>

            <!-- Área Dinámica de Contenido -->
            <main class="p-6 flex-grow">
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>