<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";



if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../carrito_de_compras.php");
    exit;
}



if (!usuarioAutenticado()) {
    $_SESSION["mensaje_usuario"] = "Debes iniciar sesión para finalizar la compra.";
    $_SESSION["tipo_mensaje_usuario"] = "error";
    $_SESSION["destino_despues_login"] = "../modulo_de_pago.php";

    header("Location: ../inicio_de_sesion.php");
    exit;
}

$usuarioActual = obtenerUsuarioActual();
$idUsuario = (int) ($usuarioActual["id_usuario"] ?? 0);

if ($idUsuario <= 0) {
    $_SESSION["mensaje_pago_error"] = "La sesión del usuario no es válida.";
    header("Location: ../modulo_de_pago.php");
    exit;
}



$direccion = trim($_POST["direccion"] ?? "");
$_SESSION["direccion_pago"] = $direccion;

if ($direccion === "") {
    $_SESSION["mensaje_pago_error"] = "La dirección de entrega es obligatoria.";
    header("Location: ../modulo_de_pago.php");
    exit;
}

if (strlen($direccion) < 10) {
    $_SESSION["mensaje_pago_error"] = "La dirección debe contener al menos 10 caracteres.";
    header("Location: ../modulo_de_pago.php");
    exit;
}

if (strlen($direccion) > 255) {
    $_SESSION["mensaje_pago_error"] = "La dirección no puede superar los 255 caracteres.";
    header("Location: ../modulo_de_pago.php");
    exit;
}



$carrito = $_SESSION["carrito"] ?? [];

if (empty($carrito)) {
    header("Location: ../carrito_de_compras.php");
    exit;
}



try {
    $conexion->begin_transaction();

    $detallesPedido = [];
    $totalPedido = 0;



    $consultaProducto = $conexion->prepare(
        "SELECT
            p.id_producto,
            p.nombre,
            p.precio,
            p.descuento,
            pt.stock
        FROM productos p
        INNER JOIN producto_tallas pt
            ON pt.id_producto = p.id_producto
        WHERE p.id_producto = ?
          AND pt.talla = ?
        FOR UPDATE"
    );

    foreach ($carrito as $item) {
        $idProducto = (int) ($item["id_producto"] ?? 0);
        $talla = strtoupper(trim($item["talla"] ?? ""));
        $cantidad = (int) ($item["cantidad"] ?? 0);

        if ($idProducto <= 0 || $talla === "" || $cantidad <= 0) {
            throw new Exception("Uno de los productos del carrito no es válido.");
        }

        $consultaProducto->bind_param("is", $idProducto, $talla);
        $consultaProducto->execute();

        $resultadoProducto = $consultaProducto->get_result();
        $producto = $resultadoProducto->fetch_assoc();

        if (!$producto) {
            throw new Exception("Uno de los productos o tallas ya no existe.");
        }

        $stockDisponible = (int) $producto["stock"];

        if ($stockDisponible <= 0) {
            throw new Exception("{$producto['nombre']} en talla {$talla} ya no tiene existencias.");
        }

        if ($cantidad > $stockDisponible) {
            throw new Exception("No hay suficientes existencias de {$producto['nombre']} en talla {$talla}. Stock disponible: {$stockDisponible}.");
        }

        $precioNormal = (float) $producto["precio"];
        $descuento = (float) $producto["descuento"];
        $precioUnitario = $precioNormal;

        if ($descuento > 0) {
            $precioUnitario = $precioNormal - ($precioNormal * $descuento / 100);
        }

        $precioUnitario = round($precioUnitario, 2);
        $subtotal = round($precioUnitario * $cantidad, 2);
        $totalPedido += $subtotal;

        $detallesPedido[] = [
            "id_producto"     => $idProducto,
            "nombre"          => $producto["nombre"],
            "talla"           => $talla,
            "cantidad"        => $cantidad,
            "precio_unitario" => $precioUnitario
        ];
    }

    $consultaProducto->close();

    if (empty($detallesPedido)) {
        throw new Exception("No existen productos válidos en el carrito.");
    }

    $totalPedido = round($totalPedido, 2);



    $insertarPedido = $conexion->prepare(
        "INSERT INTO pedidos (id_usuario, direccion, total, estado)
        VALUES (?, ?, ?, 'pendiente')"
    );

    $insertarPedido->bind_param("isd", $idUsuario, $direccion, $totalPedido);
    $insertarPedido->execute();

    $idPedido = (int) $conexion->insert_id;
    $insertarPedido->close();

    if ($idPedido <= 0) {
        throw new Exception("No fue posible generar el número del pedido.");
    }


    $insertarDetalle = $conexion->prepare(
        "INSERT INTO detalle_pedido (id_pedido, id_producto, talla, cantidad, precio_unitario)
        VALUES (?, ?, ?, ?, ?)"
    );

    $actualizarStock = $conexion->prepare(
        "UPDATE producto_tallas
        SET stock = stock - ?
        WHERE id_producto = ?
          AND talla = ?
          AND stock >= ?"
    );

    

    foreach ($detallesPedido as $detalle) {
        $idProducto = (int) $detalle["id_producto"];
        $talla = $detalle["talla"];
        $cantidad = (int) $detalle["cantidad"];
        $precioUnitario = (float) $detalle["precio_unitario"];

        $insertarDetalle->bind_param("iisid", $idPedido, $idProducto, $talla, $cantidad, $precioUnitario);
        $insertarDetalle->execute();

        $actualizarStock->bind_param("iisi", $cantidad, $idProducto, $talla, $cantidad);
        $actualizarStock->execute();

        if ($actualizarStock->affected_rows !== 1) {
            throw new Exception("No fue posible actualizar el inventario de {$detalle['nombre']}.");
        }
    }

    $insertarDetalle->close();
    $actualizarStock->close();

    /* =====================================================
       CONFIRMAR COMPRA Y LIMPIAR SESIÓN
    ===================================================== */

    $conexion->commit();

    $_SESSION["mensaje_compra"] = "Tu compra se registró correctamente. El número de tu pedido es #{$idPedido}.";
    $_SESSION["tipo_mensaje_compra"] = "exito";

    unset($_SESSION["direccion_pago"], $_SESSION["mensaje_pago_error"]);

    $_SESSION["carrito"] = [];

    $conexion->close();
    header("Location: ../index.php");
    exit;

} catch (Throwable $error) {
    try {
        $conexion->rollback();
    } catch (Throwable $errorRollback) {
        // La transacción ya no estaba activa.
    }

    $_SESSION["mensaje_pago_error"] = "No fue posible finalizar la compra: " . $error->getMessage();
    $_SESSION["direccion_pago"] = $direccion;

    $conexion->close();

    header("Location: ../modulo_de_pago.php");
    exit;
}