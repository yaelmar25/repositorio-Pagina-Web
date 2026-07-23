<?php

session_start();

require_once __DIR__ . "/../config/conexion.php";

/* =========================================================
   VALIDAR EL MÉTODO DE ENVÍO
========================================================= */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../catalogo.php");
    exit;
}

/* =========================================================
   CREAR EL CARRITO SI TODAVÍA NO EXISTE
========================================================= */
if (!isset($_SESSION["carrito"])) {
    $_SESSION["carrito"] = [];
}

/* =========================================================
   OBTENER LA ACCIÓN
========================================================= */
$accion = trim($_POST["accion"] ?? "");

/* =========================================================
   PROCESAR LA ACCIÓN
========================================================= */
switch ($accion) {

    /* =====================================================
       AGREGAR PRODUCTO
    ===================================================== */
    case "agregar":
        $idProducto = filter_input(INPUT_POST, "id_producto", FILTER_VALIDATE_INT);
        $talla      = strtoupper(trim($_POST["talla"] ?? ""));
        $cantidad   = filter_input(INPUT_POST, "cantidad", FILTER_VALIDATE_INT);

        /* Validar datos recibidos */
        if (!$idProducto || $talla === "" || !$cantidad || $cantidad < 1) {
            die("Los datos enviados no son válidos.");
        }

        /* Consultar el stock real */
        $consultaStock = $conexion->prepare(
            "SELECT stock
             FROM producto_tallas
             WHERE id_producto = ?
             AND talla = ?"
        );

        if (!$consultaStock) {
            die("Error al preparar la consulta: " . $conexion->error);
        }

        $consultaStock->bind_param("is", $idProducto, $talla);
        $consultaStock->execute();
        $resultadoStock = $consultaStock->get_result();
        $registroStock  = $resultadoStock->fetch_assoc();
        $consultaStock->close();

        /* Comprobar que la talla exista */
        if (!$registroStock) {
            die("La talla seleccionada no existe para este producto.");
        }

        $stockDisponible = (int) $registroStock["stock"];

        /* Validar inventario */
        if ($stockDisponible <= 0) {
            die("La talla seleccionada no tiene existencias.");
        }

        if ($cantidad > $stockDisponible) {
            die("La cantidad solicitada supera el stock disponible.");
        }

        /*
        | La clave combina producto y talla.
        | Ejemplos: 1-S, 1-M, 2-L
        */
        $clave = $idProducto . "-" . $talla;

        /* Si ya existe, aumentar cantidad */
        if (isset($_SESSION["carrito"][$clave])) {
            $cantidadActual = (int) $_SESSION["carrito"][$clave]["cantidad"];
            $cantidadNueva  = $cantidadActual + $cantidad;

            if ($cantidadNueva > $stockDisponible) {
                die("No hay suficientes existencias para agregar esa cantidad.");
            }

            $_SESSION["carrito"][$clave]["cantidad"] = $cantidadNueva;
        } else {
            /* Crear un producto nuevo en el carrito */
            $_SESSION["carrito"][$clave] = [
                "id_producto" => $idProducto,
                "talla"       => $talla,
                "cantidad"    => $cantidad
            ];
        }

        header("Location: ../carrito_de_compras.php");
        exit;

    /* =====================================================
       ACTUALIZAR CANTIDAD
    ===================================================== */
    case "actualizar":
        $clave    = trim($_POST["clave"] ?? "");
        $cantidad = filter_input(INPUT_POST, "cantidad", FILTER_VALIDATE_INT);

        /* Validar que el producto exista en la sesión */
        if ($clave === "" || !isset($_SESSION["carrito"][$clave])) {
            die("El producto no existe en el carrito.");
        }

        /* Validar la cantidad */
        if (!$cantidad || $cantidad < 1) {
            die("La cantidad proporcionada no es válida.");
        }

        /* Obtener información guardada */
        $itemCarrito = $_SESSION["carrito"][$clave];
        $idProducto  = (int) $itemCarrito["id_producto"];
        $talla       = strtoupper(trim($itemCarrito["talla"]));

        /* Consultar nuevamente el stock */
        $consultaStock = $conexion->prepare(
            "SELECT stock
             FROM producto_tallas
             WHERE id_producto = ?
             AND talla = ?"
        );

        if (!$consultaStock) {
            die("Error al preparar la consulta: " . $conexion->error);
        }

        $consultaStock->bind_param("is", $idProducto, $talla);
        $consultaStock->execute();
        $resultadoStock = $consultaStock->get_result();
        $registroStock  = $resultadoStock->fetch_assoc();
        $consultaStock->close();

        /* Comprobar inventario */
        if (!$registroStock) {
            die("No se encontró el inventario del producto.");
        }

        $stockDisponible = (int) $registroStock["stock"];

        if ($stockDisponible <= 0) {
            die("La talla seleccionada ya no tiene existencias.");
        }

        if ($cantidad > $stockDisponible) {
            die("La cantidad supera el stock disponible. Solo hay " . $stockDisponible . " piezas.");
        }

        /* Actualizar cantidad en la sesión */
        $_SESSION["carrito"][$clave]["cantidad"] = $cantidad;

        header("Location: ../carrito_de_compras.php");
        exit;

    /* =====================================================
       ELIMINAR UN PRODUCTO
    ===================================================== */
    case "eliminar":
        $clave = trim($_POST["clave"] ?? "");

        /* Validar la clave */
        if ($clave === "") {
            die("No se indicó qué producto se debe eliminar.");
        }

        /* Eliminar solo esa combinación de producto y talla */
        if (isset($_SESSION["carrito"][$clave])) {
            unset($_SESSION["carrito"][$clave]);
        }

        header("Location: ../carrito_de_compras.php");
        exit;

    /* =====================================================
       VACIAR TODO EL CARRITO
    ===================================================== */
    case "vaciar":
        $_SESSION["carrito"] = [];

        header("Location: ../carrito_de_compras.php");
        exit;

    /* =====================================================
       ACCIÓN NO RECONOCIDA
    ===================================================== */
    default:
        die("La acción solicitada no es válida.");
}