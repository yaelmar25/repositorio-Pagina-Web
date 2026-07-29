<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


function usuarioAutenticado(): bool
{
    return (
        isset($_SESSION["usuario"]) &&
        is_array($_SESSION["usuario"]) &&
        isset($_SESSION["usuario"]["id_usuario"])
    );
}



function obtenerUsuarioActual(): ?array
{
    if (!usuarioAutenticado()) {
        return null;
    }

    return $_SESSION["usuario"];
}



function obtenerRolUsuario(): string
{
    $usuario = obtenerUsuarioActual();

    if ($usuario === null) {
        return "";
    }

    return $usuario["rol"] ?? "cliente";
}




function usuarioEsAdministrador(): bool
{
    return (
        usuarioAutenticado() &&
        obtenerRolUsuario() === "administrador"
    );
}




function obtenerPrimerNombreUsuario(): string
{
    $usuario = obtenerUsuarioActual();

    if ($usuario === null) {
        return "";
    }

    $nombreCompleto = trim(
        $usuario["nombre"] ?? ""
    );

    if ($nombreCompleto === "") {
        return "Usuario";
    }

    $partesNombre = preg_split(
        "/\s+/",
        $nombreCompleto
    );

    return $partesNombre[0] ?? "Usuario";
}




function obtenerCantidadCarrito(): int
{
    $carrito =
        $_SESSION["carrito"] ?? [];

    $cantidadTotal = 0;

    foreach ($carrito as $item) {
        $cantidadTotal +=
            (int) ($item["cantidad"] ?? 0);
    }

    return $cantidadTotal;
}


function requerirInicioSesion(
    string $rutaLogin = "inicio_de_sesion.php"
): void {

    if (usuarioAutenticado()) {
        return;
    }

    $_SESSION["destino_despues_login"] =
        $_SERVER["REQUEST_URI"] ?? "/";

    $_SESSION["mensaje_usuario"] =
        "Debes iniciar sesión para continuar.";

    $_SESSION["tipo_mensaje_usuario"] =
        "error";

    header("Location: " . $rutaLogin);
    exit;
}



function requerirAdministrador(
    string $rutaLogin = "../inicio_de_sesion.php",
    string $rutaInicio = "../index.php"
): void {

    if (!usuarioAutenticado()) {

        $_SESSION["destino_despues_login"] =
            $_SERVER["REQUEST_URI"] ?? "/admin/index.php";

        $_SESSION["mensaje_usuario"] =
            "Debes iniciar sesión como administrador.";

        $_SESSION["tipo_mensaje_usuario"] =
            "error";

        header("Location: " . $rutaLogin);
        exit;
    }

    if (!usuarioEsAdministrador()) {

        $_SESSION["mensaje_usuario"] =
            "No tienes permisos para acceder al panel administrativo.";

        $_SESSION["tipo_mensaje_usuario"] =
            "error";

        header("Location: " . $rutaInicio);
        exit;
    }
}