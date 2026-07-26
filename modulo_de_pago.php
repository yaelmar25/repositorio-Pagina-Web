<?php

require_once __DIR__ . "/config/sesion.php";
require_once __DIR__ . "/config/conexion.php";


requerirInicioSesion("inicio_de_sesion.php");


$usuarioActual = obtenerUsuarioActual();
$carritoSesion = $_SESSION["carrito"] ?? [];


$mensajePagoError  = $_SESSION["mensaje_pago_error"] ?? "";
$direccionAnterior = $_SESSION["direccion_pago"] ?? "";

unset($_SESSION["mensaje_pago_error"], $_SESSION["direccion_pago"]);


if (empty($carritoSesion)) {
    header("Location: carrito_de_compras.php");
    exit;
}


$productosPago   = [];
$cantidadTotal   = 0;
$subtotalGeneral = 0;


$consultaProducto = $conexion->prepare(
    "SELECT
        p.id_producto,
        p.slug,
        p.nombre,
        p.descripcion,
        p.precio,
        p.descuento,
        pt.stock,
        (
            SELECT ip.ruta_imagen
            FROM imagenes_producto ip
            WHERE ip.id_producto = p.id_producto
            ORDER BY ip.id_imagen
            LIMIT 1
        ) AS imagen
    FROM productos p
    INNER JOIN producto_tallas pt
        ON pt.id_producto = p.id_producto
    WHERE p.id_producto = ?
      AND pt.talla = ?"
);

if (!$consultaProducto) {
    die("Error al preparar el resumen del pedido: " . $conexion->error);
}


foreach ($carritoSesion as $clave => $itemSesion) {
    $idProducto = (int) ($itemSesion["id_producto"] ?? 0);
    $talla      = strtoupper(trim($itemSesion["talla"] ?? ""));
    $cantidad   = (int) ($itemSesion["cantidad"] ?? 0);

    if ($idProducto <= 0 || $talla === "" || $cantidad <= 0) {
        unset($_SESSION["carrito"][$clave]);
        continue;
    }

    $consultaProducto->bind_param("is", $idProducto, $talla);
    $consultaProducto->execute();
    $resultadoProducto = $consultaProducto->get_result();
    $producto          = $resultadoProducto->fetch_assoc();

    if (!$producto) {
        unset($_SESSION["carrito"][$clave]);
        continue;
    }

    $stockDisponible = (int) $producto["stock"];

    if ($stockDisponible <= 0) {
        unset($_SESSION["carrito"][$clave]);
        continue;
    }

    if ($cantidad > $stockDisponible) {
        $cantidad = $stockDisponible;
        $_SESSION["carrito"][$clave]["cantidad"] = $cantidad;
    }

    $precioNormal   = (float) $producto["precio"];
    $descuento      = (float) $producto["descuento"];
    $precioUnitario = $precioNormal;

    if ($descuento > 0) {
        $precioUnitario = $precioNormal - ($precioNormal * $descuento / 100);
    }

    $precioUnitario = round($precioUnitario, 2);
    $subtotal       = round($precioUnitario * $cantidad, 2);

    $cantidadTotal   += $cantidad;
    $subtotalGeneral += $subtotal;

    $productosPago[] = [
        "clave"           => $clave,
        "id_producto"     => $idProducto,
        "slug"            => $producto["slug"],
        "nombre"          => $producto["nombre"],
        "descripcion"     => $producto["descripcion"],
        "imagen"          => $producto["imagen"],
        "talla"           => $talla,
        "cantidad"        => $cantidad,
        "stock"           => $stockDisponible,
        "precio_normal"   => $precioNormal,
        "descuento"       => $descuento,
        "precio_unitario" => $precioUnitario,
        "subtotal"        => $subtotal
    ];
}

$consultaProducto->close();
$conexion->close();

if (empty($productosPago)) {
    header("Location: carrito_de_compras.php");
    exit;
}

$subtotalGeneral = round($subtotalGeneral, 2);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago | Legacy Jerseys</title>

    <link rel="stylesheet" href="CSS/estilos.css">
    <link rel="stylesheet" href="CSS/pago.css">
</head>

<body>

    <?php require_once __DIR__ . "/header/header.php"; ?>

    <main class="payment-page">

        <section class="payment-title">
            <h1>Pago</h1>
            <p>Completa los datos necesarios para finalizar la compra.</p>
        </section>

        <section class="payment-layout">

            
            <div>
                <form action="procesos/finalizar_compra.php" method="POST" class="payment-card" id="payment-form">

                    <?php if ($mensajePagoError !== ""): ?>
                        <p class="form-message error" role="alert">
                            <?= htmlspecialchars($mensajePagoError, ENT_QUOTES, "UTF-8") ?>
                        </p>
                    <?php endif; ?>

                    <!-- DIRECCIÓN -->
                    <div class="form-group full">
                        <label for="shipping-address">Dirección de entrega</label>
                        <textarea
                            name="direccion"
                            id="shipping-address"
                            placeholder="Calle, número, colonia, municipio, estado y código postal"
                            rows="4"
                            minlength="10"
                            maxlength="255"
                            required><?= htmlspecialchars($direccionAnterior, ENT_QUOTES, "UTF-8") ?></textarea>
                    </div>

                    <!-- NOMBRE -->
                    <div class="form-group full">
                        <label for="card-name">Nombre del titular</label>
                        <input
                            type="text"
                            id="card-name"
                            value="<?= htmlspecialchars($usuarioActual["nombre"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                            placeholder="Ej. Juan Pérez"
                            autocomplete="cc-name"
                            required
                        >
                    </div>

                    <!-- TARJETA -->
                    <div class="form-group full">
                        <label for="card-number">Número de tarjeta</label>
                        <div class="input-icon">
                            <span aria-hidden="true">▭</span>
                            <input
                                type="text"
                                id="card-number"
                                placeholder="1234 5678 9012 3456"
                                autocomplete="cc-number"
                                inputmode="numeric"
                                maxlength="19"
                                required
                            >
                        </div>
                    </div>

                    <!-- FECHA -->
                    <div class="form-group">
                        <label for="expiry-date">Fecha de vencimiento</label>
                        <div class="input-icon">
                            <input
                                type="text"
                                id="expiry-date"
                                placeholder="MM / AA"
                                autocomplete="cc-exp"
                                inputmode="numeric"
                                maxlength="7"
                                required
                            >
                            <span aria-hidden="true">□</span>
                        </div>
                    </div>

                    <!-- CVV -->
                    <div class="form-group">
                        <label for="security-code">Código de seguridad</label>
                        <div class="input-icon">
                            <input
                                type="password"
                                id="security-code"
                                placeholder="CVV"
                                autocomplete="cc-csc"
                                inputmode="numeric"
                                maxlength="3"
                                required
                            >
                            <span aria-hidden="true">?</span>
                        </div>
                    </div>

                    <!-- MÉTODO -->
                    <div class="form-group full">
                        <label for="payment-method">Método de pago</label>
                        <select id="payment-method" required>
                            <option value="">Selecciona una opción</option>
                            <option value="credito-debito">Tarjeta de crédito o débito</option>
                            <option value="debito">Tarjeta de débito</option>
                            <option value="credito">Tarjeta de crédito</option>
                        </select>
                    </div>

                    <!-- MONTO -->
                    <div class="form-group">
                        <label for="payment-total">Monto total</label>
                        <input
                            type="text"
                            id="payment-total"
                            value="$<?= number_format($subtotalGeneral, 2) ?> MXN"
                            readonly
                        >
                    </div>

                    <!-- ESTADO -->
                    <div class="form-group">
                        <label>Estado de la transacción</label>
                        <div class="status-box">
                            <span class="status-badge pending" id="transaction-status">
                                Pendiente
                            </span>
                        </div>
                    </div>

                    <p class="form-message" id="form-message" role="alert"></p>

                    <button type="submit" id="pay-button" class="primary-button">
                        Confirmar pago
                    </button>

                    <a class="secondary-button" href="carrito_de_compras.php">
                        Volver al carrito
                    </a>

                </form>

                <div class="secure-note">
                    <span aria-hidden="true">🔒</span>
                    <div>
                        <strong>Pago simulado para fines del sistema</strong>
                        <p>No se almacenará el número de tarjeta ni el código de seguridad.</p>
                    </div>
                </div>
            </div>

            <!-- ================= RESUMEN ================= -->
            <aside class="summary-card">
                <h2>Resumen del pedido</h2>

                <?php foreach ($productosPago as $item): ?>
                    <article class="summary-product">

                        <?php if (!empty($item["imagen"])): ?>
                            <img
                                src="<?= htmlspecialchars($item["imagen"], ENT_QUOTES, "UTF-8") ?>"
                                alt="<?= htmlspecialchars($item["nombre"], ENT_QUOTES, "UTF-8") ?>"
                            >
                        <?php else: ?>
                            <div class="summary-product-no-image">
                                Sin imagen
                            </div>
                        <?php endif; ?>

                        <div>
                            <h3><?= htmlspecialchars($item["nombre"], ENT_QUOTES, "UTF-8") ?></h3>
                            <p><?= htmlspecialchars($item["descripcion"], ENT_QUOTES, "UTF-8") ?></p>
                            <span>Talla: <?= htmlspecialchars($item["talla"], ENT_QUOTES, "UTF-8") ?></span>
                            <span>Cantidad: <?= (int) $item["cantidad"] ?></span>

                            <?php if ($item["descuento"] > 0): ?>
                                <span>Descuento: <?= number_format($item["descuento"], 0) ?>%</span>
                            <?php endif; ?>
                        </div>

                        <strong>$<?= number_format($item["subtotal"], 2) ?> MXN</strong>

                    </article>
                <?php endforeach; ?>

                <div class="summary-line">
                    <span>
                        Subtotal (<?= (int) $cantidadTotal ?> <?= $cantidadTotal === 1 ? "producto" : "productos" ?>)
                    </span>
                    <strong>$<?= number_format($subtotalGeneral, 2) ?> MXN</strong>
                </div>

                <div class="summary-line">
                    <span>Envío</span>
                    <strong class="free-shipping">Gratis</strong>
                </div>

                <div class="summary-total">
                    <span>Total de compra</span>
                    <strong>$<?= number_format($subtotalGeneral, 2) ?> MXN</strong>
                </div>
            </aside>

        </section>

    </main>

    <script src="script/pago.js?v=12"></script>

</body>
</html>