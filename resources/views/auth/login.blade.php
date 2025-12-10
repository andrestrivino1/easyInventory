<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <style>
  * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
  body {
    background: #f4f4f4;
    height: 100vh; display: flex; justify-content: center; align-items: center;
  }
  .login-container { width: 320px; background: #fff; padding: 30px; border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
  .login-container h2 { text-align: center; margin-bottom: 20px; color: #333; }
  .input-group { margin-bottom: 15px; }
  .input-group label { display: block; font-size: 14px; color: #444; margin-bottom: 5px; }
  .input-group input {width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; outline: none; transition: border-color 0.3s ease;}
  .input-group input:focus { border-color: #007bff; }
  .btn { width: 100%; padding: 12px; background: #007bff; border: none; color: #fff;
    border-radius: 8px; font-size: 16px; cursor: pointer; transition: background 0.3s ease; }
  .btn:hover { background: #005fcc; }
  .invalid-feedback { color: #c00; font-size: 13px; margin-top: 4px; }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div class="login-container">
    <h2>Iniciar Sesión</h2>
    <form method="POST" action="{{ route('login') }}" autocomplete="off">
      @csrf
      <div class="input-group">
        <label for="email">Correo</label>
        <input type="email" id="email" name="email" placeholder="Ingresa tu correo" value="{{ old('email') }}" required />
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="input-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required />
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      @if(session('error'))
      <script>Swal.fire({icon:'error',title:'Error',text:'{{ session('error') }}',toast:true,position:'top-end',showConfirmButton:false,timer:4000})</script>
      @endif
      <button type="submit" class="btn">Ingresar</button>
    </form>
  </div>
</body>
</html>
