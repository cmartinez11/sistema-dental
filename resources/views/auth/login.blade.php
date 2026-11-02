<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión - Clínica Dental San Juan de Lurigancho</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f0f4f9] flex items-center justify-center min-h-screen p-4">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-slate-100 p-8" x-data="loginApp()">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-3xl shadow-inner">
                🦷
            </div>
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Clínica Dental SJL</h1>
            <p class="text-xs text-slate-400 mt-1">Plataforma Dental Cloud • San Juan de Lurigancho</p>
        </div>

        <!-- Alerta de Error -->
        <template x-if="message">
            <div class="mb-5 p-3.5 rounded-2xl text-xs font-semibold bg-red-50 text-red-700 border border-red-200/80 flex items-center gap-2" x-text="message"></div>
        </template>

        <form @submit.prevent="submitLogin" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Correo Electrónico:</label>
                <input type="email" x-model="form.email"
                    class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="ejemplo@sjl.pe"
                    required>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Contraseña:</label>
                <input type="password" x-model="form.password"
                    class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="••••••••"
                    required>
            </div>

            <button type="submit" :disabled="loading"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-3 px-4 rounded-xl shadow-lg shadow-blue-600/30 transition transform active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50">
                <span x-show="!loading">Ingresar al Sistema</span>
                <span x-show="loading">Iniciando sesión...</span>
            </button>
        </form>
    </div>

    <script>
        function loginApp() {
            return {
                loading: false,
                form: {
                    email: '',
                    password: ''
                },
                message: '',
                async submitLogin() {
                    this.loading = true;
                    this.message = '';
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        
                        let response = await fetch('/login', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                            },
                            body: JSON.stringify(this.form)
                        });

                        let data = await response.json();

                        if (response.ok) {
                            // Guardar el token Sanctum para las llamadas fetch de Alpine.js
                            if (data.token) {
                                localStorage.setItem('auth_token', data.token);
                                localStorage.setItem('dental_token', data.token);
                            }
                            if (data.role) {
                                localStorage.setItem('user_role', data.role);
                            }

                            // Redireccionar al Panel General (Dashboard)
                            window.location.href = '/dashboard';
                        } else {
                            this.message = data.message || 'Credenciales inválidas.';
                        }
                    } catch (error) {
                        this.message = 'Error de conexión con el servidor.';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>

</html>
