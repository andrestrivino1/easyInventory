<script>
    // ============================================
    // SISTEMA DE GESTIÓN DE SESIÓN EXPIRADA
    // ============================================

    // Variable global para evitar múltiples alertas
    window.sessionExpiredAlertShown = false;

    // Función global para hacer logout
    function doLogout() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("logout") }}';

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken.getAttribute('content');
            form.appendChild(csrfInput);
        }

        document.body.appendChild(form);
        form.submit();
    }

    // Función para mostrar alerta de sesión expirada
    function showSessionExpiredAlert(message) {
        if (window.sessionExpiredAlertShown) {
            return; // Ya se mostró la alerta, no mostrar otra
        }

        window.sessionExpiredAlertShown = true;

        Swal.fire({
            icon: 'warning',
            title: '{{ __("common.sesion_expirada") ?? "Sesión Expirada" }}',
            text: message || 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.',
            confirmButtonText: '{{ __("common.aceptar") ?? "Aceptar" }}',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showCancelButton: false
        }).then(function () {
            window.location.href = '{{ route("logout") }}';
        });
    }

    // ============================================
    // INTERCEPTOR DE PETICIONES FETCH (AJAX)
    // ============================================
    (function () {
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = function (...args) {
                return originalFetch.apply(this, args).then(function (response) {
                    // Interceptar errores de autenticación
                    if (response.status === 401 || response.status === 419) {
                        // 401: No autenticado
                        // 419: Token CSRF expirado
                        showSessionExpiredAlert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
                        return Promise.reject(new Error('Session expired'));
                    } else if (response.status === 403) {
                        // 403: Forbidden (puede ser por sesión expirada)
                        showSessionExpiredAlert('No tienes permisos o tu sesión ha expirado.');
                        return Promise.reject(new Error('Forbidden'));
                    }
                    return response;
                }).catch(function (error) {
                    // Si hay error de red y no se ha mostrado alerta, podría ser sesión expirada
                    if (!window.sessionExpiredAlertShown && error.message !== 'Session expired' && error.message !== 'Forbidden') {
                        console.error('Error en petición fetch:', error);
                    }
                    throw error;
                });
            };
        }
    })();

    // ============================================
    // INTERCEPTOR DE XMLHttpRequest (AJAX tradicional)
    // ============================================
    (function () {
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function (method, url, async, user, password) {
            this._url = url;
            return originalOpen.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function (data) {
            const xhr = this;

            // Interceptar la respuesta
            xhr.addEventListener('load', function () {
                if (xhr.status === 401 || xhr.status === 419) {
                    showSessionExpiredAlert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
                } else if (xhr.status === 403) {
                    showSessionExpiredAlert('No tienes permisos o tu sesión ha expirado.');
                } else if (xhr.status === 500) {
                    // Verificar si el error 500 es por sesión expirada
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message && response.message.includes('CSRF') || response.message.includes('session')) {
                            showSessionExpiredAlert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
                        }
                    } catch (e) {
                        // Si no es JSON o no contiene mensaje de sesión, ignorar
                    }
                }
            });

            return originalSend.apply(this, arguments);
        };
    })();

    // ============================================
    // INTERCEPTOR DE FORMULARIOS TRADICIONALES
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        // Interceptar todos los formularios
        document.addEventListener('submit', function (e) {
            const form = e.target;

            // Ignorar el formulario de logout
            if (form.action.includes('logout')) {
                return;
            }

            // Verificar si hay token CSRF
            const csrfToken = form.querySelector('input[name="_token"]');
            const metaCsrfToken = document.querySelector('meta[name="csrf-token"]');

            if (!csrfToken && metaCsrfToken) {
                // Agregar token CSRF si no existe
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = metaCsrfToken.getAttribute('content');
                form.appendChild(input);
            }
        }, true);
    });

    // ============================================
    // DETECCIÓN DE INACTIVIDAD (30 minutos)
    // ============================================
    (function () {
        let inactivityTimer;
        const INACTIVITY_TIME = 30 * 60 * 1000; // 30 minutos

        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(function () {
                showSessionExpiredAlert('Tu sesión ha expirado por inactividad.');
            }, INACTIVITY_TIME);
        }

        // Eventos que indican actividad del usuario
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        events.forEach(function (event) {
            document.addEventListener(event, resetInactivityTimer, true);
        });

        // Iniciar el timer
        resetInactivityTimer();
    })();

    // ============================================
    // VERIFICACIÓN PERIÓDICA DE SESIÓN (cada 5 minutos)
    // ============================================
    (function () {
        const CHECK_INTERVAL = 5 * 60 * 1000; // 5 minutos

        function checkSession() {
            fetch('{{ route("home") }}', {
                method: 'HEAD',
                credentials: 'same-origin'
            }).then(function (response) {
                if (response.status === 401 || response.status === 419) {
                    showSessionExpiredAlert('Tu sesión ha expirado.');
                }
            }).catch(function (error) {
                console.error('Error verificando sesión:', error);
            });
        }

        // Verificar sesión periódicamente
        setInterval(checkSession, CHECK_INTERVAL);
    })();
</script>
