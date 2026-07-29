<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../inicio_de_sesion.php");
    exit;
}



$accion = trim($_POST["accion"] ?? "");




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




function destinoInternoValido(string $destino): bool
{
    if ($destino === "") {
        return false;
    }

    return !(
        str_contains($destino, "://") ||
        str_starts_with($destino, "//") ||
        str_contains($destino, "\r") ||
        str_contains($destino, "\n")
    );
}


switch ($accion) {




    case "registrar":

        $nombre = trim($_POST["nombre"] ?? "");
        $correo = strtolower(
            trim($_POST["correo"] ?? "")
        );

        $contrasena = $_POST["contrasena"] ?? "";
        $confirmar = $_POST["confirmar"] ?? "";


        /* Validar campos obligatorios */

        if (
            $nombre === "" ||
            $correo === "" ||
            $contrasena === "" ||
            $confirmar === ""
        ) {
            regresarConMensaje(
                "Todos los campos son obligatorios.",
                "error",
                true
            );
        }


        /* Validar nombre */

        if (mb_strlen($nombre) < 3) {
            regresarConMensaje(
                "El nombre debe tener al menos 3 caracteres.",
                "error",
                true
            );
        }

        if (mb_strlen($nombre) > 100) {
            regresarConMensaje(
                "El nombre no puede superar los 100 caracteres.",
                "error",
                true
            );
        }

        if (!preg_match("/^[\p{L}\s'-]+$/u", $nombre)) {
            regresarConMensaje(
                "El nombre solamente puede contener letras y espacios.",
                "error",
                true
            );
        }


        /* Validar correo */

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            regresarConMensaje(
                "Ingresa un correo electrónico válido.",
                "error",
                true
            );
        }

        if (strlen($correo) > 150) {
            regresarConMensaje(
                "El correo electrónico es demasiado largo.",
                "error",
                true
            );
        }


        /* Validar contraseña */

        if (strlen($contrasena) < 6) {
            regresarConMensaje(
                "La contraseña debe tener al menos 6 caracteres.",
                "error",
                true
            );
        }

        if (preg_match("/\s/", $contrasena)) {
            regresarConMensaje(
                "La contraseña no puede contener espacios.",
                "error",
                true
            );
        }

        if ($contrasena !== $confirmar) {
            regresarConMensaje(
                "Las contraseñas no coinciden.",
                "error",
                true
            );
        }


        /* Comprobar que el correo no esté registrado */

        $consultaUsuario = $conexion->prepare(
            "SELECT id_usuario
             FROM usuarios
             WHERE correo = ?
             LIMIT 1"
        );

        if (!$consultaUsuario) {
            die(
                "Error al preparar la consulta del usuario: " .
                $conexion->error
            );
        }

        $consultaUsuario->bind_param(
            "s",
            $correo
        );

        $consultaUsuario->execute();

        $resultadoUsuario =
            $consultaUsuario->get_result();

        $usuarioExistente =
            $resultadoUsuario->fetch_assoc();

        $consultaUsuario->close();


        if ($usuarioExistente) {
            regresarConMensaje(
                "Ya existe una cuenta registrada con ese correo.",
                "error",
                true
            );
        }


        /* Proteger contraseña */

        $contrasenaProtegida =
            password_hash(
                $contrasena,
                PASSWORD_DEFAULT
            );

        if ($contrasenaProtegida === false) {
            regresarConMensaje(
                "No fue posible proteger la contraseña.",
                "error",
                true
            );
        }


        /*
         * Todos los usuarios registrados desde el formulario
         * público tendrán el rol de cliente.
         */

        $rol = "cliente";


        /* Insertar usuario */

        $insertarUsuario = $conexion->prepare(
            "INSERT INTO usuarios (
                nombre,
                correo,
                contrasena,
                rol
            )
            VALUES (?, ?, ?, ?)"
        );

        if (!$insertarUsuario) {
            die(
                "Error al preparar el registro: " .
                $conexion->error
            );
        }

        $insertarUsuario->bind_param(
            "ssss",
            $nombre,
            $correo,
            $contrasenaProtegida,
            $rol
        );

        $registroCorrecto =
            $insertarUsuario->execute();

        $insertarUsuario->close();


        if (!$registroCorrecto) {
            regresarConMensaje(
                "No fue posible registrar la cuenta.",
                "error",
                true
            );
        }

        regresarConMensaje(
            "Cuenta creada correctamente. Ya puedes iniciar sesión.",
            "exito",
            false
        );



    case "iniciar":

        $correo = strtolower(
            trim($_POST["correo"] ?? "")
        );

        $contrasena =
            $_POST["contrasena"] ?? "";


        /* Validar campos */

        if ($correo === "" || $contrasena === "") {
            regresarConMensaje(
                "Ingresa tu correo y contraseña."
            );
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            regresarConMensaje(
                "Ingresa un correo electrónico válido."
            );
        }


        /* Buscar usuario y obtener su rol */

        $consultaUsuario = $conexion->prepare(
            "SELECT
                id_usuario,
                nombre,
                correo,
                contrasena,
                rol
            FROM usuarios
            WHERE correo = ?
            LIMIT 1"
        );

        if (!$consultaUsuario) {
            die(
                "Error al preparar el inicio de sesión: " .
                $conexion->error
            );
        }

        $consultaUsuario->bind_param(
            "s",
            $correo
        );

        $consultaUsuario->execute();

        $resultadoUsuario =
            $consultaUsuario->get_result();

        $usuario =
            $resultadoUsuario->fetch_assoc();

        $consultaUsuario->close();


        /* Verificar correo y contraseña */

        if (
            !$usuario ||
            !password_verify(
                $contrasena,
                $usuario["contrasena"]
            )
        ) {
            regresarConMensaje(
                "Correo o contraseña incorrectos."
            );
        }


        /* Validar el rol obtenido */

        $rol = $usuario["rol"] ?? "cliente";

        if (
            $rol !== "cliente" &&
            $rol !== "administrador"
        ) {
            $rol = "cliente";
        }


        /* Renovar identificador de sesión */

        session_regenerate_id(true);


        /* Guardar usuario y rol */

        $_SESSION["usuario"] = [
            "id_usuario" => (int) $usuario["id_usuario"],
            "nombre" => $usuario["nombre"],
            "correo" => $usuario["correo"],
            "rol" => $rol
        ];


        /* Obtener destino anterior */

        $destinoGuardado =
            $_SESSION["destino_despues_login"] ?? "";

        unset(
            $_SESSION["destino_despues_login"]
        );


        /*
         * Si existe un destino interno válido,
         * se intenta conservar.
         */

        if (
            destinoInternoValido($destinoGuardado)
        ) {

            $rutaDestino = parse_url(
                $destinoGuardado,
                PHP_URL_PATH
            ) ?? "";

            /*
             * Impedir que un cliente sea enviado
             * a una página administrativa.
             */

            if (
                $rol !== "administrador" &&
                preg_match(
                    "#(^|/)admin(/|$)#i",
                    $rutaDestino
                )
            ) {
                $destino = "../index.php";
            } else {
                $destino = $destinoGuardado;
            }

        } else {

            /*
             * Redirección normal según el rol.
             */

            if ($rol === "administrador") {
                $destino = "../admin/index.php";
            } else {
                $destino = "../index.php";
            }
        }


        header("Location: " . $destino);
        exit;



    case "cerrar":

        /*
         * Eliminar los datos del usuario.
         * El carrito se conserva.
         */

        unset(
            $_SESSION["usuario"],
            $_SESSION["destino_despues_login"]
        );


        /* Renovar identificador de sesión */

        session_regenerate_id(true);


        /* Crear mensaje */

        $_SESSION["mensaje_usuario"] =
            "La sesión se cerró correctamente.";

        $_SESSION["tipo_mensaje_usuario"] =
            "exito";

        $_SESSION["mostrar_registro"] =
            false;


        header(
            "Location: ../inicio_de_sesion.php"
        );

        exit;


    default:

        regresarConMensaje(
            "La acción solicitada no es válida."
        );
}