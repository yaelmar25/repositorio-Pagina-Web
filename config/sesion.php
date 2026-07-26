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


function obtenerPrimerNombreUsuario(): string
{
    $usuario = obtenerUsuarioActual();

    if ($usuario === null) {
        return "";
    }

    $nombreCompleto = trim($usuario["nombre"] ?? "");

    if ($nombreCompleto === "") {
        return "Usuario";
    }

    $partesNombre = preg_split("/\s+/", $nombreCompleto);

    return $partesNombre[0] ?? "Usuario";
}



function obtenerCantidadCarrito(): int
{
    $carrito = $_SESSION["carrito"] ?? [];
    $cantidadTotal = 0;

    foreach ($carrito as $item) {
        $cantidadTotal += (int) ($item["cantidad"] ?? 0);
    }

    return $cantidadTotal;
}



function requerirInicioSesion(string $rutaLogin = "inicio_de_sesion.php"): void
{
    /*
    | Si el usuario ya inició sesión,
    | puede continuar en la página.
    */
    if (usuarioAutenticado()) {
        return;
    }

    /*
    | Guardar la página que el usuario
    | estaba intentando visitar.
    */
    $_SESSION["destino_despues_login"] = $_SERVER["REQUEST_URI"] ?? "/";

    /*
    | Crear un mensaje para el formulario.
    */
    $_SESSION["mensaje_usuario"] = "Debes iniciar sesión para continuar.";
    $_SESSION["tipo_mensaje_usuario"] = "error";

    /*
    | Enviar al inicio de sesión.
    */
    header("Location: " . $rutaLogin);
    exit;
}