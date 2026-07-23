<?php

session_start();

/* =========================================================
   EVITAR QUE UN USUARIO AUTENTICADO VUELVA AL LOGIN
========================================================= */
if (isset($_SESSION["usuario"])) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   OBTENER MENSAJES TEMPORALES
========================================================= */
$mensaje         = $_SESSION["mensaje_usuario"] ?? "";
$tipoMensaje     = $_SESSION["tipo_mensaje_usuario"] ?? "";
$mostrarRegistro = $_SESSION["mostrar_registro"] ?? false;

/* =========================================================
   ELIMINAR MENSAJES DESPUÉS DE LEERLOS
========================================================= */
unset(
    $_SESSION["mensaje_usuario"],
    $_SESSION["tipo_mensaje_usuario"],
    $_SESSION["mostrar_registro"]
);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión | Legacy Jerseys</title>
    <link rel="stylesheet" href="CSS/estilos.css">
</head>

<body class="fondo-login">

    <main class="login">

        <!-- ================= FORMULARIOS ================= -->
        <section class="login-form">

            <a href="index.php" class="volver">
                ← Volver al inicio
            </a>

            <h1>Inicio de sesión</h1>

            <p class="descripcion">
                Ingresa tus datos para continuar
            </p>

            <!-- ================= MENSAJE ================= -->
            <?php if ($mensaje !== ""): ?>
                <div class="mensaje-usuario <?= htmlspecialchars($tipoMensaje, ENT_QUOTES, "UTF-8") ?>" role="alert">
                    <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <!-- ================= INICIO DE SESIÓN ================= -->
            <form action="procesos/usuario_acciones.php" method="POST" id="formulario-login">
                <input type="hidden" name="accion" value="iniciar">

                <input 
                    type="email" 
                    name="correo" 
                    id="correoLogin" 
                    placeholder="Correo electrónico" 
                    autocomplete="email" 
                    required
                >

                <input 
                    type="password" 
                    name="contrasena" 
                    id="contrasenaLogin" 
                    placeholder="Contraseña" 
                    autocomplete="current-password" 
                    required
                >

                <button type="submit" class="boton-login">
                    Iniciar sesión
                </button>
            </form>

            <!-- ================= BOTÓN DE REGISTRO ================= -->
            <button 
                type="button" 
                class="crear" 
                id="boton-mostrar-registro" 
                aria-controls="registro" 
                aria-expanded="<?= $mostrarRegistro ? "true" : "false" ?>"
            >
                <?= $mostrarRegistro ? "Ocultar registro" : "Crear una cuenta" ?>
            </button>

            <!-- ================= REGISTRO ================= -->
            <section id="registro" class="registro-panel<?= $mostrarRegistro ? " registro-visible" : "" ?>">
                <h2>Crear cuenta</h2>

                <form action="procesos/usuario_acciones.php" method="POST" id="formulario-registro">
                    <input type="hidden" name="accion" value="registrar">

                    <input 
                        type="text" 
                        name="nombre" 
                        id="nombre" 
                        placeholder="Nombre completo" 
                        autocomplete="name" 
                        minlength="3" 
                        maxlength="100" 
                        required
                    >

                    <input 
                        type="email" 
                        name="correo" 
                        id="correo" 
                        placeholder="Correo electrónico" 
                        autocomplete="email" 
                        maxlength="150" 
                        required
                    >

                    <input 
                        type="password" 
                        name="contrasena" 
                        id="contrasena" 
                        placeholder="Contraseña" 
                        autocomplete="new-password" 
                        minlength="6" 
                        required
                    >

                    <input 
                        type="password" 
                        name="confirmar" 
                        id="confirmar" 
                        placeholder="Confirmar contraseña" 
                        autocomplete="new-password" 
                        minlength="6" 
                        required
                    >

                    <button type="submit" class="boton-login">
                        Registrarse
                    </button>
                </form>
            </section>

        </section>

        <!-- ================= IMAGEN ================= -->
        <section class="login-imagen">
            <img src="pictures/futbol.jpg" alt="Jugador de fútbol usando un jersey">
        </section>

    </main>

    <script src="script/inicio_sesion.js"></script>

</body>

</html>