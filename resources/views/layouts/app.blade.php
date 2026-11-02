<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Gestión Dental - San Juan de Lurigancho')</title>
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f6ff',
                            100: '#e0edfe',
                            500: '#1d72fe',
                            600: '#1561e6',
                            700: '#0f4dc4',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- CLIENTE GLOBAL SANCTUM API PARA ALPINE.JS -->
    <script>
        window.dentalApi = {
            token() {
                return localStorage.getItem('auth_token') || localStorage.getItem('dental_token') || '';
            },
            headers(extra = {}) {
                const token = this.token();
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                return {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    ...extra
                };
            },
            async request(url, options = {}) {
                options.headers = this.headers(options.headers || {});
                try {
                    const response = await fetch(url, options);

                    if (response.status === 401) {
                        localStorage.removeItem('auth_token');
                        localStorage.removeItem('dental_token');
                        localStorage.removeItem('user_role');
                        window.location.href = '/login?session_expired=1';
                        return response;
                    }

                    return response;
                } catch (error) {
                    console.error('Network / API Exception:', error);
                    throw error;
                }
            },
            async get(url, params = null) {
                let fullUrl = url;
                if (params) {
                    const query = new URLSearchParams(params).toString();
                    fullUrl += (url.includes('?') ? '&' : '?') + query;
                }
                return this.request(fullUrl, { method: 'GET' });
            },
            async post(url, data = {}) {
                return this.request(url, {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
            },
            logout() {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('dental_token');
                localStorage.removeItem('user_role');
                const form = document.getElementById('global-logout-form');
                if (form) {
                    form.submit();
                } else {
                    window.location.href = '/login';
                }
            }
        };
    </script>
</head>

<body class="bg-[#f0f4f9] font-sans antialiased text-gray-800">

    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">

        <!-- SIDEBAR LATERAL FIJO -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transition-transform duration-300 ease-in-out md:translate-x-0 md:sticky md:top-0 h-screen flex flex-col shadow-xl shrink-0">

            <!-- Logo / Título de la Clínica -->
            <div class="flex items-center justify-between h-16 bg-gray-950 px-5 shrink-0 border-b border-gray-800/80">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <span class="text-xl">🦷</span>
                    <div>
                        <span class="text-sm font-bold tracking-wide text-blue-400 block leading-tight">Clínica Dental SJL</span>
                        <span class="text-[10px] text-gray-400 block tracking-wider uppercase">San Juan de Lurigancho</span>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="md:hidden text-gray-400 hover:text-white">✕</button>
            </div>

            <!-- Menú de Navegación por Roles (RBAC) -->
            <nav class="flex-grow px-4 py-5 space-y-1.5 text-sm overflow-y-auto">
                <p class="px-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Menú Principal</p>

                <!-- Panel General / Dashboard: Solo Administrador -->
                @if(!auth()->check() || auth()->user()->hasRole('admin'))
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center px-3.5 py-2.5 rounded-xl transition text-gray-300 hover:bg-gray-800 hover:text-white group font-medium {{ request()->is('dashboard') ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-600/30' : '' }}">
                        <span class="mr-3 text-base">📊</span> Panel General
                    </a>

                    <a href="{{ route('dentist-schedules.index') }}"
                        class="flex items-center px-3.5 py-2.5 rounded-xl transition text-gray-300 hover:bg-gray-800 hover:text-white group font-medium {{ request()->is('dashboard/dentist-schedules*') ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-600/30' : '' }}">
                        <span class="mr-3 text-base">⏱️</span> Horarios Odontólogos
                    </a>
                @endif

                <!-- Directorio Pacientes: Admin, Recepcionista y Dentista -->
                @if(!auth()->check() || auth()->user()->hasRole('admin', 'receptionist', 'dentist'))
                    <a href="{{ route('patients.index') }}"
                        class="flex items-center px-3.5 py-2.5 rounded-xl transition text-gray-300 hover:bg-gray-800 hover:text-white group font-medium {{ request()->is('dashboard/patients*') ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-600/30' : '' }}">
                        <span class="mr-3 text-base">👥</span> Directorio Pacientes
                    </a>
                @endif

                <!-- Gestión de Citas: Admin, Recepcionista y Dentista -->
                @if(!auth()->check() || auth()->user()->hasRole('admin', 'receptionist', 'dentist'))
                    <a href="{{ route('appointments.index') }}"
                        class="flex items-center px-3.5 py-2.5 rounded-xl transition text-gray-300 hover:bg-gray-800 hover:text-white group font-medium {{ request()->is('dashboard/appointments*') ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-600/30' : '' }}">
                        <span class="mr-3 text-base">📅</span> Gestión de Citas
                    </a>
                @endif

                <!-- Caja y Pagos: Solo Admin y Recepcionista (Dentista bloqueado) -->
                @if(!auth()->check() || auth()->user()->hasRole('admin', 'receptionist'))
                    <a href="{{ route('cash.index') }}"
                        class="flex items-center px-3.5 py-2.5 rounded-xl transition text-gray-300 hover:bg-gray-800 hover:text-white group font-medium {{ request()->is('dashboard/cash*') ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-600/30' : '' }}">
                        <span class="mr-3 text-base">💰</span> Caja y Pagos
                    </a>
                @endif

                <!-- Historias Clínicas: Solo Admin y Dentista (Recepcionista bloqueada) -->
                @if(!auth()->check() || auth()->user()->hasRole('admin', 'dentist'))
                    <a href="{{ route('medical-records.index') }}"
                        class="flex items-center px-3.5 py-2.5 rounded-xl transition text-gray-300 hover:bg-gray-800 hover:text-white group font-medium {{ request()->is('dashboard/medical-records*') ? 'bg-blue-600 text-white font-semibold shadow-md shadow-blue-600/30' : '' }}">
                        <span class="mr-3 text-base">📋</span> Historias Clínicas
                    </a>
                @endif
            </nav>

            <!-- Sección de Usuario y Cerrar Sesión -->
            <div class="p-4 bg-gray-950 border-t border-gray-800 shrink-0">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 rounded-full bg-blue-500/20 text-blue-400 font-bold flex items-center justify-center text-xs ring-1 ring-blue-500/30">
                        {{ strtoupper(substr(auth()->user()->name ?? 'Dr', 0, 2)) }}
                    </div>
                    <div class="ml-3 overflow-hidden">
                        <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name ?? 'Usuario de Turno' }}</p>
                        <p class="text-[10px] text-blue-400 uppercase tracking-wider">{{ auth()->user()->role?->value ?? auth()->user()->role ?? 'Personal Clínico' }}</p>
                    </div>
                </div>
                <form id="global-logout-form" action="/logout" method="POST">
                    @csrf
                    <button type="button" @click="window.dentalApi.logout()"
                        class="w-full bg-red-600/90 hover:bg-red-600 text-white text-xs font-semibold py-2 px-3 rounded-lg transition text-center shadow-sm">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Barra Superior Responsive -->
            <header class="bg-white/80 backdrop-blur-md border-b border-gray-200/80 h-16 flex items-center justify-between px-6 sticky top-0 z-40">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen"
                        class="md:hidden p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-base font-bold text-gray-800 leading-tight">Clínica Odontológica Especializada</h2>
                        <p class="text-xs text-gray-400 hidden sm:block">Plataforma Dental Cloud • San Juan de Lurigancho</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 text-xs bg-blue-50 text-blue-700 font-medium px-3 py-1 rounded-full border border-blue-200/60 shadow-xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        Sede San Juan de Lurigancho
                    </span>
                </div>
            </header>

            <!-- Área Dinámica de Contenido -->
            <main class="p-4 sm:p-6 lg:p-8 flex-grow overflow-x-hidden">
                <!-- Alertas Flash de Autorización / RBAC -->
                @if(session('error'))
                    <div class="mb-5 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl flex items-center justify-between text-xs font-semibold shadow-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-base">⛔</span>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-700">✕</button>
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl flex items-center justify-between text-xs font-semibold shadow-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-base">✅</span>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-700">✕</button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>
