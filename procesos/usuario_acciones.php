<?php

session_start();

require_once __DIR__ . "/../config/conexion.php";


/* =========================================================
   VALIDAR MÉTODO
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../inicio_de_sesion.php");
    exit;
}


/* =========================================================
   OBTENER ACCIÓN
========================================================= */

$accion = trim($_POST["accion"] ?? "");


/* =========================================================
   FUNCIÓN PARA REGRESAR CON MENSAJE
========================================================= */

function regresarConMensaje(
    string $mensaje,
    string $tipo = "error",
    bool $mostrarRegistro = false
): void {
    $_SESSION["mensaje_usuario"] = $mensaje;
    $_SESSION["tipo_mensaje_usuario"] = $tipo;
    $_SESSION["mostrar_registro"] = $mostrarRegistro;

    header("Location: ../inicio_de_sesion.php");
    exit;
}


/* =========================================================
   PROCESAR ACCIÓN
========================================================= */

switch ($accion) {

    /* =====================================================
       REGISTRAR USUARIO
    ===================================================== */

    case "registrar":

        $nombre = trim($_POST["nombre"] ?? "");
        $correo = strtolower(trim($_POST["correo"] ?? ""));
        $contrasena = $_POST["contrasena"] ?? "";
        $confirmar = $_POST["confirmar"] ?? "";

        /* Validar campos vacíos */

        if ($nombre === "" || $correo === "" || $contrasena === "" || $confirmar === "") {
            regresarConMensaje("Todos los campos son obligatorios.", "error", true);
        }

        /* Validar nombre */

        if (mb_strlen($nombre) < 3) {
            regresarConMensaje("El nombre debe tener al menos 3 caracteres.", "error", true);
        }

        if (!preg_match("/^[\p{L}\s'-]+$/u", $nombre)) {
            regresarConMensaje("El nombre solamente puede contener letras y espacios.", "error", true);
        }

        /* Validar correo */

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            regresarConMensaje("Ingresa un correo electrónico válido.", "error", true);
        }

        /* Validar contraseña */

        if (strlen($contrasena) < 6) {
            regresarConMensaje("La contraseña debe tener al menos 6 caracteres.", "error", true);
        }

        if (preg_match("/\s/", $contrasena)) {
            regresarConMensaje("La contraseña no puede contener espacios.", "error", true);
        }

        if ($contrasena !== $confirmar) {
            regresarConMensaje("Las contraseñas no coinciden.", "error", true);
        }

        /* Comprobar que el correo no esté registrado */

        $consultaUsuario = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");

        if (!$consultaUsuario) {
            die("Error al preparar la consulta: " . $conexion->error);
        }

        $consultaUsuario->bind_param("s", $correo);
        $consultaUsuario->execute();

        $resultadoUsuario = $consultaUsuario->get_result();

        if ($resultadoUsuario->num_rows > 0) {
            $consultaUsuario->close();
            regresarConMensaje("Ya existe una cuenta registrada con ese correo.", "error", true);
        }

        $consultaUsuario->close();

        /* Proteger la contraseña */

        $contrasenaProtegida = password_hash($contrasena, PASSWORD_DEFAULT);

        /* Insertar usuario */

        $insertarUsuario = $conexion->prepare(
            "INSERT INTO usuarios (nombre, correo, contrasena) VALUES (?, ?, ?)"
        );

        if (!$insertarUsuario) {
            die("Error al preparar el registro: " . $conexion->error);
        }

        $insertarUsuario->bind_param("sss", $nombre, $correo, $contrasenaProtegida);

        if (!$insertarUsuario->execute()) {
            $insertarUsuario->close();
            regresarConMensaje("No fue posible registrar la cuenta.", "error", true);
        }

        $insertarUsuario->close();

        regresarConMensaje("Cuenta creada correctamente. Ya puedes iniciar sesión.", "exito", false);

        break;


    /* =====================================================
       INICIAR SESIÓN
    ===================================================== */

    case "iniciar":

        $correo = strtolower(trim($_POST["correo"] ?? ""));
        $contrasena = $_POST["contrasena"] ?? "";

        /* Validar campos */

        if ($correo === "" || $contrasena === "") {
            regresarConMensaje("Ingresa tu correo y contraseña.");
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            regresarConMensaje("Ingresa un correo electrónico válido.");
        }

        /* Buscar usuario */

        $consultaUsuario = $conexion->prepare(
            "SELECT id_usuario, nombre, correo, contrasena FROM usuarios WHERE correo = ?"
        );

        if (!$consultaUsuario) {
            die("Error al preparar la consulta: " . $conexion->error);
        }

        $consultaUsuario->bind_param("s", $correo);
        $consultaUsuario->execute();

        $resultadoUsuario = $consultaUsuario->get_result();
        $usuario = $resultadoUsuario->fetch_assoc();

        $consultaUsuario->close();

        /* Verificar correo y contraseña */

        if (!$usuario || !password_verify($contrasena, $usuario["contrasena"])) {
            regresarConMensaje("Correo o contraseña incorrectos.");
        }

        /* Crear sesión segura */

        session_regenerate_id(true);

        $_SESSION["usuario"] = [
            "id_usuario" => (int) $usuario["id_usuario"],
            "nombre"     => $usuario["nombre"],
            "correo"     => $usuario["correo"]
        ];

        /* Redirigir al inicio */

        header("Location: ../index.php");
        exit;


    /* =====================================================
       ACCIÓN INVÁLIDA
    ===================================================== */

    default:

        regresarConMensaje("La acción solicitada no es válida.");
}