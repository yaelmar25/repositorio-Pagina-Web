<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inicio de sesión | Legacy Jerseys</title>

    <link rel="stylesheet" href="CSS/estilos.css">

</head>

<body class="fondo-login">

    <div class="login">

        <!-- FORMULARIO -->

        <div class="login-form">

            <a href="index.php" class="volver">
                ← Volver al inicio
            </a>

            <h1>Inicio de sesión</h1>

            <p class="descripcion">
                Ingresa tus datos para continuar
            </p>

            <!-- INICIO DE SESIÓN -->

            <input
                type="email"
                id="correoLogin"
                placeholder="Correo electrónico">

            <input
                type="password"
                id="contrasenaLogin"
                placeholder="Contraseña">

            <button
                class="boton-login"
                onclick="iniciarSesion()">

                Iniciar sesión

            </button>

            <button
                class="crear"
                onclick="mostrarRegistro()">

                Crear una cuenta

            </button>

            <!-- REGISTRO -->

            <div id="registro" style="display:none;">

                <h2>Crear cuenta</h2>

                <input
                    type="text"
                    id="nombre"
                    placeholder="Nombre completo">

                <input
                    type="email"
                    id="correo"
                    placeholder="Correo electrónico">

                <input
                    type="password"
                    id="contrasena"
                    placeholder="Contraseña">

                <input
                    type="password"
                    id="confirmar"
                    placeholder="Confirmar contraseña">

                <button
                    class="boton-login"
                    onclick="registrar()">

                    Registrarse

                </button>

            </div>

        </div>

        <!-- IMAGEN -->

        <div class="login-imagen">

            <img src="pictures/futbol.jpg" alt="Jerseys">

        </div>

    </div>
    <script src="script/login.js">
        // Mostrar u ocultar el formulario de registro
        function mostrarRegistro() {

            let registro = document.getElementById("registro");

            if (registro.style.display === "block") {
                registro.style.display = "none";
            } else {
                registro.style.display = "block";
            }

        }


        // Registrar usuario
        function registrar() {

            let nombre = document.getElementById("nombre").value.trim();
            let correo = document.getElementById("correo").value.trim();
            let contrasena = document.getElementById("contrasena").value;
            let confirmar = document.getElementById("confirmar").value;

            // Validar campos vacíos
            if (nombre === "" || correo === "" || contrasena === "" || confirmar === "") {
                alert("Todos los campos son obligatorios.");
                return;
            }


            if (nombre.length < 3) {
                alert("El nombre debe tener al menos 3 caracteres.");
                return;
            }

            if (/\d/.test(nombre)) {
                alert("El nombre no puede contener números.");
                return;
            }

            // Validar correo
            if (!correo.includes("@") || !correo.includes(".")) {
                alert("Ingresa un correo válido.");
                return;
            }

            // Validar contraseña
            if (contrasena.length < 6) {
                alert("La contraseña debe tener al menos 6 caracteres.");
                return;
            }

            if (contrasena.includes(" ")) {
                alert("La contraseña no puede contener espacios.");
                return;
            }

            // Confirmar contraseña
            if (contrasena !== confirmar) {
                alert("Las contraseñas no coinciden.");
                return;
            }

            // Guardar usuario en el navegador
            localStorage.setItem("usuario", correo);
            localStorage.setItem("password", contrasena);
            localStorage.setItem("nombre", nombre);

            alert("¡Registro exitoso!");

            // Limpiar formulario
            document.getElementById("nombre").value = "";
            document.getElementById("correo").value = "";
            document.getElementById("contrasena").value = "";
            document.getElementById("confirmar").value = "";

            // Ocultar formulario
            document.getElementById("registro").style.display = "none";
        }
        // Iniciar sesión
        function iniciarSesion() {

            let correo = document.getElementById("correoLogin").value.trim();
            let contrasena = document.getElementById("contrasenaLogin").value;

            // Validar campos vacíos
            if (correo === "" || contrasena === "") {
                alert("Ingresa tu correo y contraseña.");
                return;
            }

            // Obtener datos guardados
            let correoGuardado = localStorage.getItem("usuario");
            let contrasenaGuardada = localStorage.getItem("password");
            let nombre = localStorage.getItem("nombre");

            // Verificar si existe una cuenta
            if (correoGuardado === null || contrasenaGuardada === null) {
                alert("No hay ninguna cuenta registrada. Crea una cuenta primero.");
                return;
            }

            // Validar credenciales
            if (correo === correoGuardado && contrasena === contrasenaGuardada) {

                alert("¡Bienvenido " + nombre + "!");

                // Redirigir a la página principal
                window.location.href = "pagina_principal.html";

            } else {

                alert("Correo o contraseña incorrectos.");

            }

        }
    </script>

</body>

</html>