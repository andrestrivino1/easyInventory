<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Confirm Password') }} - EasyInventory</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --text-main: #1f2937;
            --text-muted: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 450px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #4f46e5, #ec4899);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .auth-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .auth-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px 12px 48px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .invalid-feedback {
            color: #df1c1c;
            font-size: 0.8rem;
            margin-top: 4px;
        }

        /* Language Switcher */
        .lang-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
        }

        .lang-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .lang-link.active {
            opacity: 1;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="lang-switcher">
        <a href="{{ route('lang.switch', 'es') }}"
            class="lang-link {{ app()->getLocale() == 'es' ? 'active' : '' }}">ES</a>
        <a href="{{ route('lang.switch', 'en') }}"
            class="lang-link {{ app()->getLocale() == 'en' ? 'active' : '' }}">EN</a>
        <a href="{{ route('lang.switch', 'zh') }}"
            class="lang-link {{ app()->getLocale() == 'zh' ? 'active' : '' }}">ZH</a>
    </div>

    <div class="auth-card">
        <div class="logo-container">
            <i class="fas fa-user-shield"></i>
            <h1 class="auth-title">{{ __('common.confirmar_contraseña') }}</h1>
            <p class="auth-subtitle">{{ __('common.por_favor_confirme_contraseña') }}</p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="form-group">
                <label class="form-label">{{ __('common.contraseña') }}</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control"
                        placeholder="{{ __('common.ingresa_tu_contraseña') }}" required autofocus>
                </div>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn-submit">
                {{ __('common.confirmar_contraseña') }}
            </button>

            @if (Route::has('password.request'))
                <a class="back-link" href="{{ route('password.request') }}">
                    {{ __('common.olvidaste_contraseña') }}
                </a>
            @endif
        </form>
    </div>
</body>

</html>