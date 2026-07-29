<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

requerirAdministrador(
    "../inicio_de_sesion.php",
    "../index.php"
);



if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin/pedidos.php");
    exit;
}



function regresarPedido(
    int $idPedido,
    string $mensaje,
    string $tipo = "error"
): void {

    $_SESSION["mensaje_admin"] = $mensaje;
    $_SESSION["tipo_mensaje_admin"] = $tipo;

    header(
        "Location: ../admin/pedido_detalle.php?id=" .
        $idPedido
    );

    exit;
}


$accion = trim($_POST["accion"] ?? "");

$idPedido = filter_var(
    $_POST["id_pedido"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$idPedido || $idPedido <= 0) {

    $_SESSION["mensaje_admin"] =
        "El identificador del pedido no es válido.";

    $_SESSION["tipo_mensaje_admin"] =
        "error";

    header("Location: ../admin/pedidos.php");
    exit;
}


if ($accion !== "actualizar_estado") {
    regresarPedido(
        $idPedido,
        "La acción solicitada no es válida."
    );
}


$tokenRecibido =
    $_POST["csrf_token"] ?? "";

$tokenGuardado =
    $_SESSION["csrf_admin"] ?? "";

if (
    !is_string($tokenRecibido) ||
    !is_string($tokenGuardado) ||
    $tokenGuardado === "" ||
    !hash_equals(
        $tokenGuardado,
        $tokenRecibido
    )
) {
    regresarPedido(
        $idPedido,
        "La solicitud no es válida. Recarga la página."
    );
}



$estadoNuevo = strtolower(
    trim($_POST["estado"] ?? "")
);

$estadosPermitidos = [
    "pendiente",
    "confirmado",
    "enviado",
    "entregado",
    "cancelado"
];

if (
    !in_array(
        $estadoNuevo,
        $estadosPermitidos,
        true
    )
) {
    regresarPedido(
        $idPedido,
        "El estado seleccionado no es válido."
    );
}


$transaccionIniciada = false;

try {

    $conexion->begin_transaction();
    $transaccionIniciada = true;



    $consultaPedido = $conexion->prepare(
        "SELECT estado
         FROM pedidos
         WHERE id_pedido = ?
         LIMIT 1
         FOR UPDATE"
    );

    if (!$consultaPedido) {
        throw new RuntimeException(
            "No fue posible consultar el pedido."
        );
    }

    $consultaPedido->bind_param(
        "i",
        $idPedido
    );

    $consultaPedido->execute();

    $resultadoPedido =
        $consultaPedido->get_result();

    $pedidoActual =
        $resultadoPedido->fetch_assoc();

    $resultadoPedido->free();
    $consultaPedido->close();


    if (!$pedidoActual) {
        throw new RuntimeException(
            "El pedido solicitado ya no existe."
        );
    }


    $estadoAnterior = strtolower(
        trim($pedidoActual["estado"] ?? "")
    );


    /*
     * Si el pedido ya tiene el estado seleccionado,
     * no se modifica nuevamente el inventario.
     */

    if ($estadoAnterior === $estadoNuevo) {

        $conexion->commit();
        $transaccionIniciada = false;

        $_SESSION["csrf_admin"] =
            bin2hex(random_bytes(32));

        regresarPedido(
            $idPedido,
            "El pedido ya se encontraba en el estado " .
            $estadoNuevo . ".",
            "exito"
        );
    }


    $detallesPedido = [];

    $consultaDetalles = $conexion->prepare(
        "SELECT
            id_producto,
            talla,
            cantidad
         FROM detalle_pedido
         WHERE id_pedido = ?
         ORDER BY id_detalle"
    );

    if (!$consultaDetalles) {
        throw new RuntimeException(
            "No fue posible consultar los productos del pedido."
        );
    }

    $consultaDetalles->bind_param(
        "i",
        $idPedido
    );

    $consultaDetalles->execute();

    $resultadoDetalles =
        $consultaDetalles->get_result();

    while (
        $detalle =
        $resultadoDetalles->fetch_assoc()
    ) {
        $detallesPedido[] = [
            "id_producto" =>
                (int) $detalle["id_producto"],

            "talla" =>
                (string) $detalle["talla"],

            "cantidad" =>
                (int) $detalle["cantidad"]
        ];
    }

    $resultadoDetalles->free();
    $consultaDetalles->close();


    if (
        $estadoNuevo === "cancelado" &&
        $estadoAnterior !== "cancelado"
    ) {

        $consultarStock = $conexion->prepare(
            "SELECT
                id_producto_talla,
                stock
             FROM producto_tallas
             WHERE id_producto = ?
             AND talla = ?
             LIMIT 1
             FOR UPDATE"
        );

        $actualizarStock = $conexion->prepare(
            "UPDATE producto_tallas
             SET stock = stock + ?
             WHERE id_producto_talla = ?"
        );

        $insertarStock = $conexion->prepare(
            "INSERT INTO producto_tallas (
                id_producto,
                talla,
                stock
             )
             VALUES (?, ?, ?)"
        );


        if (
            !$consultarStock ||
            !$actualizarStock ||
            !$insertarStock
        ) {
            throw new RuntimeException(
                "No fue posible preparar la devolución del inventario."
            );
        }


        foreach ($detallesPedido as $detalle) {

            $idProducto =
                $detalle["id_producto"];

            $talla =
                $detalle["talla"];

            $cantidad =
                $detalle["cantidad"];


            if ($cantidad <= 0) {
                continue;
            }


            $consultarStock->bind_param(
                "is",
                $idProducto,
                $talla
            );

            $consultarStock->execute();

            $resultadoStock =
                $consultarStock->get_result();

            $stockActual =
                $resultadoStock->fetch_assoc();

            $resultadoStock->free();


            if ($stockActual) {

                $idProductoTalla =
                    (int) $stockActual["id_producto_talla"];

                $actualizarStock->bind_param(
                    "ii",
                    $cantidad,
                    $idProductoTalla
                );

                if (!$actualizarStock->execute()) {
                    throw new RuntimeException(
                        "No fue posible devolver las existencias del producto."
                    );
                }

            } else {

                /*
                 * Si la talla ya no existía en el inventario,
                 * se vuelve a crear con la cantidad devuelta.
                 */

                $insertarStock->bind_param(
                    "isi",
                    $idProducto,
                    $talla,
                    $cantidad
                );

                if (!$insertarStock->execute()) {
                    throw new RuntimeException(
                        "No fue posible restaurar una talla del producto."
                    );
                }
            }
        }


        $consultarStock->close();
        $actualizarStock->close();
        $insertarStock->close();
    }


    if (
        $estadoAnterior === "cancelado" &&
        $estadoNuevo !== "cancelado"
    ) {

        $consultarStock = $conexion->prepare(
            "SELECT
                id_producto_talla,
                stock
             FROM producto_tallas
             WHERE id_producto = ?
             AND talla = ?
             LIMIT 1
             FOR UPDATE"
        );

        $descontarStock = $conexion->prepare(
            "UPDATE producto_tallas
             SET stock = stock - ?
             WHERE id_producto_talla = ?"
        );


        if (
            !$consultarStock ||
            !$descontarStock
        ) {
            throw new RuntimeException(
                "No fue posible preparar la reactivación del pedido."
            );
        }


        foreach ($detallesPedido as $detalle) {

            $idProducto =
                $detalle["id_producto"];

            $talla =
                $detalle["talla"];

            $cantidad =
                $detalle["cantidad"];


            if ($cantidad <= 0) {
                continue;
            }


            $consultarStock->bind_param(
                "is",
                $idProducto,
                $talla
            );

            $consultarStock->execute();

            $resultadoStock =
                $consultarStock->get_result();

            $stockActual =
                $resultadoStock->fetch_assoc();

            $resultadoStock->free();


            if (!$stockActual) {
                throw new RuntimeException(
                    "No existe inventario para el producto " .
                    $idProducto .
                    " en talla " .
                    $talla .
                    "."
                );
            }


            $stockDisponible =
                (int) $stockActual["stock"];

            if ($stockDisponible < $cantidad) {
                throw new RuntimeException(
                    "No hay existencias suficientes para reactivar el pedido. " .
                    "Producto " .
                    $idProducto .
                    ", talla " .
                    $talla .
                    "."
                );
            }


            $idProductoTalla =
                (int) $stockActual["id_producto_talla"];


            $descontarStock->bind_param(
                "ii",
                $cantidad,
                $idProductoTalla
            );

            if (!$descontarStock->execute()) {
                throw new RuntimeException(
                    "No fue posible descontar nuevamente las existencias."
                );
            }
        }


        $consultarStock->close();
        $descontarStock->close();
    }


    $actualizarPedido = $conexion->prepare(
        "UPDATE pedidos
         SET estado = ?
         WHERE id_pedido = ?"
    );

    if (!$actualizarPedido) {
        throw new RuntimeException(
            "No fue posible preparar la actualización del pedido."
        );
    }

    $actualizarPedido->bind_param(
        "si",
        $estadoNuevo,
        $idPedido
    );

    if (!$actualizarPedido->execute()) {
        throw new RuntimeException(
            "No fue posible actualizar el estado del pedido."
        );
    }

    $actualizarPedido->close();


    $conexion->commit();
    $transaccionIniciada = false;


    $_SESSION["csrf_admin"] =
        bin2hex(random_bytes(32));


    $mensajeExito =
        "El estado del pedido se actualizó correctamente.";

    if ($estadoNuevo === "cancelado") {
        $mensajeExito =
            "El pedido se canceló y las existencias regresaron al inventario.";
    }

    if (
        $estadoAnterior === "cancelado" &&
        $estadoNuevo !== "cancelado"
    ) {
        $mensajeExito =
            "El pedido se reactivó y las existencias se descontaron nuevamente.";
    }


    regresarPedido(
        $idPedido,
        $mensajeExito,
        "exito"
    );


} catch (Throwable $error) {

    if ($transaccionIniciada) {
        $conexion->rollback();
    }

    regresarPedido(
        $idPedido,
        $error->getMessage(),
        "error"
    );
}