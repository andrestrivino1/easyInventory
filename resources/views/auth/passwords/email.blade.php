<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('common.restablecer_contraseña') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f4f4;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .language-selector-login {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: inline-block;
        }

        .language-selector-login-wrapper {
            position: relative;
            display: inline-block;
        }

        .language-selector-login-trigger {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 8px 10px 8px 18px;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            outline: none;
            min-width: 140px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .language-selector-login-trigger:hover {
            border-color: rgba(0, 0, 0, 0.15);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .language-selector-login .flag-display {
            display: block;
            width: 24px;
            height: 18px;
            border-radius: 2px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .language-selector-login .flag-display img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .language-selector-login .arrow {
            pointer-events: none;
            color: #666;
            font-size: 10px;
            margin-left: auto;
        }

        .language-selector-login-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 4px;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 140px;
            overflow: hidden;
            display: none;
            z-index: 1001;
        }

        .language-selector-login-dropdown.show {
            display: block;
        }

        .language-selector-login-option {
            padding: 10px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s ease;
            font-size: 14px;
            color: #333;
        }

        .language-selector-login-option:hover {
            background: #f5f5f5;
        }

        .language-selector-login-option .flag-option {
            width: 24px;
            height: 18px;
            border-radius: 2px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .language-selector-login-option .flag-option img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .language-selector-login select {
            display: none;
        }

        .login-container {
            width: 360px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            color: #444;
            margin-bottom: 5px;
        }

        .input-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus {
            border-color: #007bff;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #007bff;
            border: none;
            color: #fff;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .btn:hover {
            background: #005fcc;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .invalid-feedback {
            color: #c00;
            font-size: 13px;
            margin-top: 4px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            text-align: center;
        }

        .back-to-login {
            text-align: center;
            margin-top: 10px;
        }

        .back-to-login a {
            color: #666;
            font-size: 13px;
            text-decoration: none;
        }

        .back-to-login a:hover {
            color: #333;
            text-decoration: underline;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="language-selector-login">
        <div class="language-selector-login-wrapper">
            <select id="languageSelectLogin" style="display: none;">
                <option value="es" {{ app()->getLocale() == 'es' ? 'selected' : '' }}>{{ __('common.espanol') }}</option>
                <option value="en" {{ app()->getLocale() == 'en' ? 'selected' : '' }}>{{ __('common.ingles') }}</option>
                <option value="zh" {{ app()->getLocale() == 'zh' ? 'selected' : '' }}>{{ __('common.chino') }}</option>
            </select>
            <div class="language-selector-login-trigger" id="languageTriggerLogin">
                <span class="flag-display" id="flagDisplayLogin">
                    <img src="{{ asset('public/images/flags/' . (app()->getLocale() == 'es' ? 'colombia' : (app()->getLocale() == 'en' ? 'usa' : 'china')) . '.png') }}"
                        alt="Flag" />
                </span>
                <span
                    id="languageTextLogin">{{ app()->getLocale() == 'es' ? __('common.espanol') : (app()->getLocale() == 'en' ? __('common.ingles') : __('common.chino')) }}</span>
                <span class="arrow">▼</span>
            </div>
            <div class="language-selector-login-dropdown" id="languageDropdownLogin">
                <div class="language-selector-login-option" data-value="es">
                    <span class="flag-option"><img src="{{ asset('public/images/flags/colombia.png') }}"
                            alt="Colombia" /></span>
                    <span>{{ __('common.espanol') }}</span>
                </div>
                <div class="language-selector-login-option" data-value="en">
                    <span class="flag-option"><img src="{{ asset('public/images/flags/usa.png') }}" alt="USA" /></span>
                    <span>{{ __('common.ingles') }}</span>
                </div>
                <div class="language-selector-login-option" data-value="zh">
                    <span class="flag-option"><img src="{{ asset('public/images/flags/china.png') }}"
                            alt="China" /></span>
                    <span>{{ __('common.chino') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="login-container">
        <h2>{{ __('common.restablecer_contraseña') }}</h2>


        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="input-group">
                <label for="email">{{ __('common.correo') }}</label>
                <input type="email" id="email" name="email" placeholder="{{ __('common.ingresa_tu_correo') }}"
                    value="{{ old('email') }}" required autofocus />
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="input-group">
                <label for="password">{{ __('common.contraseña') }}</label>
                <input type="password" id="password" name="password"
                    placeholder="{{ __('common.ingresa_tu_contraseña') }}" required />
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="input-group">
                <label for="password_confirmation">{{ __('common.confirmar_contraseña') }}</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                    placeholder="{{ __('common.confirmar_contraseña') }}" required />
            </div>

            <button type="submit" class="btn">{{ __('common.restablecer_contraseña') }}</button>

            <div class="back-to-login">
                <a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> {{ __('common.volver_al_login') }}</a>
            </div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const trigger = document.getElementById('languageTriggerLogin');
            const dropdown = document.getElementById('languageDropdownLogin');
            const select = document.getElementById('languageSelectLogin');
            const flagDisplay = document.getElementById('flagDisplayLogin');
            const languageText = document.getElementById('languageTextLogin');
            const options = dropdown.querySelectorAll('.language-selector-login-option');

            const flagFiles = {
                'es': '{{ asset("public/images/flags/colombia.png") }}',
                'en': '{{ asset("public/images/flags/usa.png") }}',
                'zh': '{{ asset("public/images/flags/china.png") }}'
            };

            const languageNames = {
                'es': '{{ __('common.espanol') }}',
                'en': '{{ __('common.ingles') }}',
                'zh': '{{ __('common.chino') }}'
            };

            // Toggle dropdown
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });

            // Cerrar al hacer click fuera
            document.addEventListener('click', function (e) {
                if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });

            // Seleccionar opción
            options.forEach(option => {
                option.addEventListener('click', function () {
                    const value = this.dataset.value;
                    select.value = value;

                    // Actualizar display
                    const img = flagDisplay.querySelector('img');
                    if (img) {
                        img.src = flagFiles[value] || flagFiles['es'];
                    }
                    if (languageText) {
                        languageText.textContent = languageNames[value] || languageNames['es'];
                    }

                    // Cerrar dropdown
                    dropdown.classList.remove('show');

                    // Cambiar idioma
                    changeLanguage(value);
                });
            });
        });

        function changeLanguage(locale) {
            const url = '{{ route("language.switch", ["locale" => "__LOCALE__"]) }}'.replace('__LOCALE__', locale);
            window.location.href = url;
        }

        @if (session('status'))
            Swal.fire({
                icon: 'success',
                title: '{{ __("common.exito") ?? "Éxito" }}',
                text: '{{ session("status") }}',
                confirmButtonColor: '#007bff'
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: '{{ __("common.error") ?? "Error" }}',
                text: '{{ $errors->first() }}',
                confirmButtonColor: '#007bff'
            });
        @endif
    </script>
</body>

</html>