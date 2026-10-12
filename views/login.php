<?php
// Iniciar sesión para verificar si ya está adentro
if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya está logueado, lo mandamos directo a su panel para evitar doble login
if (isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit();
}

// Capturar posibles mensajes de error enviados por el procesador
$error = isset($_GET['error']) ? $_GET['error'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Medilink</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            color: #172b4d;
            background: #f3f7fb;
            font-synthesis: none;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 32px 20px;
            background:
                radial-gradient(ellipse at 12% 12%, rgba(44, 125, 246, .10), transparent 32%),
                #f3f7fb;
        }
        .login-shell {
            width: min(100%, 1000px);
            min-height: 590px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e5ebf3;
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(29, 55, 90, .12);
        }
        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 42px;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(145deg, #123c72, #1769aa 62%, #2186b7);
        }
        .brand-panel::after {
            position: absolute;
            right: -115px;
            bottom: -185px;
            width: 390px;
            height: 390px;
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 50%;
            box-shadow: 0 0 0 38px rgba(255,255,255,.04), 0 0 0 78px rgba(255,255,255,.035);
            content: "";
        }
        .brand, .brand-copy, .brand-footer { position: relative; z-index: 1; }
        .brand { display: flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 700; letter-spacing: -.3px; }
        .brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 13px;
            background: rgba(255,255,255,.12);
        }
        .brand-copy { max-width: 390px; margin: 58px 0; }
        .eyebrow { margin: 0 0 15px; color: #b9e4ff; font-size: 12px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; }
        .brand-copy h1 { margin: 0; font-size: clamp(30px, 4vw, 43px); line-height: 1.14; letter-spacing: -1.4px; }
        .brand-copy p { max-width: 350px; margin: 18px 0 0; color: #d5e8f7; font-size: 15px; line-height: 1.75; }
        .brand-footer { color: #c4dced; font-size: 12px; }
        .form-panel { display: flex; align-items: center; justify-content: center; padding: 48px; }
        .form-content { width: min(100%, 365px); }
        .form-content h2 { margin: 0; color: #172b4d; font-size: 28px; letter-spacing: -.7px; }
        .form-intro { margin: 9px 0 30px; color: #728198; font-size: 14px; line-height: 1.6; }
        .field { margin-bottom: 19px; }
        label { display: block; margin-bottom: 8px; color: #34445d; font-size: 13px; font-weight: 650; }
        input {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #d9e1eb;
            border-radius: 10px;
            outline: none;
            background: #fff;
            color: #172b4d;
            font: inherit;
            font-size: 14px;
            transition: border-color .18s, box-shadow .18s;
        }
        input::placeholder { color: #a2adbc; }
        input:focus { border-color: #3184ce; box-shadow: 0 0 0 3px rgba(49,132,206,.13); }
        .error {
            margin: 0 0 21px;
            padding: 12px 14px;
            border: 1px solid #f5c8c8;
            border-radius: 10px;
            background: #fff5f5;
            color: #a83232;
            font-size: 13px;
            line-height: 1.5;
        }
        .submit {
            width: 100%;
            height: 49px;
            margin-top: 6px;
            border: 0;
            border-radius: 10px;
            background: #1769aa;
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 7px 16px rgba(23,105,170,.2);
            transition: background .18s, transform .18s, box-shadow .18s;
        }
        .submit:hover { transform: translateY(-1px); background: #125991; box-shadow: 0 10px 20px rgba(23,105,170,.25); }
        .submit:focus-visible { outline: 3px solid #8dc8f3; outline-offset: 3px; }
        .secure-note { display: flex; align-items: center; justify-content: center; gap: 7px; margin: 22px 0 0; color: #8996a8; font-size: 12px; }
        @media (max-width: 700px) {
            body { padding: 16px; }
            .login-shell { min-height: auto; grid-template-columns: 1fr; border-radius: 18px; }
            .brand-panel { min-height: 210px; padding: 25px 26px; }
            .brand-copy { margin: 30px 0 4px; }
            .brand-copy h1 { max-width: 400px; font-size: 29px; }
            .brand-copy p, .brand-footer { display: none; }
            .form-panel { padding: 34px 26px 38px; }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="brand-panel" aria-label="Medilink">
            <div class="brand">
                <span class="brand-mark" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3v18M3 12h18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </span>
                <span>Medilink</span>
            </div>
            <div class="brand-copy">
                <p class="eyebrow">Gestión clínica, más simple</p>
                <h1>Un mejor cuidado empieza con una buena conexión.</h1>
                <p>Turnos, atención e información clínica reunidos en un solo lugar.</p>
            </div>
            <div class="brand-footer">Plataforma de gestión del policonsultorio</div>
        </section>

        <section class="form-panel">
            <div class="form-content">
                <h2>Te damos la bienvenida</h2>
                <p class="form-intro">Ingresa con tus credenciales para acceder al sistema.</p>

                <?php if ($error): ?>
                    <p class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <form action="procesar_login.php" method="POST">
                    <div class="field">
                        <label for="username">Usuario</label>
                        <input type="text" name="username" id="username" required autocomplete="username" placeholder="Tu nombre de usuario">
                    </div>
                    <div class="field">
                        <label for="password">Contraseña</label>
                        <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Ingresa tu contraseña">
                    </div>
                    <button class="submit" type="submit">Ingresar al sistema</button>
                </form>
                <p class="secure-note">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 10V7a6 6 0 0 1 12 0v3m-13 0h14v11H5V10Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Acceso seguro para personal autorizado
                </p>
            </div>
        </section>
    </main>
</body>
</html>
