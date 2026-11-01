<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Clínica Dental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8" x-data="loginApp()">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Clínica Dental</h1>
            <p class="text-sm text-gray-500">Ingrese sus credenciales para acceder</p>
        </div>

        <!-- Alerta de Error -->
        <template x-if="message">
            <div class="mb-4 p-3 rounded text-sm bg-red-100 text-red-700" x-text="message"></div>
        </template>

        <form @submit.prevent="submitLogin">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Correo Electrónico:</label>
                <input type="email" x-model="form.email"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Contraseña:</label>
                <input type="password" x-model="form.password"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition">
                Ingresar al Sistema
            </button>
        </form>
    </div>

    <script>
        function loginApp() {
            return {
                form: {
                    email: '',
                    password: ''
                },
                message: '',
                async submitLogin() {
                    try {
                        let response = await fetch('/api/login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.form)
                        });

                        let data = await response.json();

                        if (response.ok) {
                            // Guardar el token en el navegador (localStorage)
                            localStorage.setItem('auth_token', data.token);
                            localStorage.setItem('user_role', data.role);

                            alert(data.message);

                            // Redirigir según el rol
                            if (data.role === 'dentista' || data.role === 'Dentista') {
                                window.location.href = '/dashboard/medical-records';
                            } else {
                                window.location.href = '/dashboard/appointments';
                            }
                        } else {
                            this.message = data.message || 'Credenciales inválidas.';
                        }
                    } catch (error) {
                        this.message = 'Error de conexión con el servidor.';
                    }
                }
            }
        }
    </script>
</body>

</html>