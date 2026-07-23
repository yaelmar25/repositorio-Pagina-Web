<?php

session_start();

require_once __DIR__ . "/config/conexion.php";

/* =========================================================
   1. OBTENER EL CARRITO GUARDADO EN LA SESIÓN
========================================================= */

$carritoSesion = $_SESSION["carrito"] ?? [];

$productosCarrito = [];
$cantidadTotal = 0;
$subtotalGeneral = 0;

/* =========================================================
   2. PREPARAR LA CONSULTA DE PRODUCTOS
========================================================= */

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
    die("Error al preparar la consulta del carrito: " . $conexion->error);
}

/* =========================================================
   3. CONSULTAR LOS PRODUCTOS DEL CARRITO
========================================================= */

foreach ($carritoSesion as $clave => $itemSesion) {
    $idProducto = (int) ($itemSesion["id_producto"] ?? 0);
    $talla = strtoupper(trim($itemSesion["talla"] ?? ""));
    $cantidad = (int) ($itemSesion["cantidad"] ?? 0);

    /*
    | Ignorar registros incompletos.
    */
    if ($idProducto <= 0 || $talla === "" || $cantidad <= 0) {
        continue;
    }

    /*
    | Consultar el producto y el stock de su talla.
    */
    $consultaProducto->bind_param("is", $idProducto, $talla);
    $consultaProducto->execute();

    $resultadoProducto = $consultaProducto->get_result();
    $producto = $resultadoProducto->fetch_assoc();

    /*
    | Si el producto o la talla ya no existen, no se muestran en el carrito.
    */
    if (!$producto) {
        continue;
    }

    /*
    | Calcular el precio con descuento.
    */
    $precioNormal = (float) $producto["precio"];
    $descuento = (float) $producto["descuento"];
    $precioUnitario = $precioNormal;

    if ($descuento > 0) {
        $precioUnitario = $precioNormal - ($precioNormal * $descuento / 100);
    }

    /*
    | Evitar mostrar una cantidad superior al stock actual.
    */
    $stockDisponible = (int) $producto["stock"];

    if ($cantidad > $stockDisponible) {
        $cantidad = $stockDisponible;
    }

    if ($cantidad <= 0) {
        continue;
    }

    /*
    | Calcular el subtotal del producto.
    */
    $subtotal = $precioUnitario * $cantidad;

    /*
    | Acumular totales.
    */
    $cantidadTotal += $cantidad;
    $subtotalGeneral += $subtotal;

    /*
    | Preparar la información que se mostrará.
    */
    $productosCarrito[] = [
        "clave" => $clave,
        "id_producto" => $idProducto,
        "slug" => $producto["slug"],
        "nombre" => $producto["nombre"],
        "descripcion" => $producto["descripcion"],
        "imagen" => $producto["imagen"],
        "talla" => $talla,
        "cantidad" => $cantidad,
        "stock" => $stockDisponible,
        "precio_normal" => $precioNormal,
        "descuento" => $descuento,
        "precio_unitario" => $precioUnitario,
        "subtotal" => $subtotal
    ];
}

$consultaProducto->close();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de compras | Legacy Jerseys</title>
    <link rel="stylesheet" href="CSS/estilos.css">
    <link rel="stylesheet" href="CSS/carrito.css">
</head>

<body>

    <!-- ================= HEADER ================= -->
    <header>
        <div class="logo">
            <a href="index.php">LEGACY JERSEYS</a>
        </div>

        <div class="buscador">
            <input type="text" placeholder="🔍 Buscar jerseys">
        </div>

        <div class="acciones">
            <a href="inicio_de_sesion.php">Inicio de sesión</a>
            <a href="carrito_de_compras.php">🛒 Carrito</a>
        </div>
    </header>

    <!-- ================= MENÚ ================= -->
    <nav>
        <a href="index.php">Inicio</a>
        <a href="ofertas.php">Ofertas</a>
        <a href="catalogo.php">Catálogo</a>
    </nav>

    <!-- ================= CONTENIDO ================= -->
    <main class="cart-page">

        <!-- ================= TÍTULO ================= -->
        <section class="cart-title">
            <h1>Carrito de compras</h1>
            <p>
                <span id="item-total"><?= $cantidadTotal ?></span>
                <?= $cantidadTotal === 1 ? "producto" : "productos" ?> en tu carrito
            </p>
        </section>

        <section class="cart-layout">

            <!-- ================= PANEL DE PRODUCTOS ================= -->
            <div class="cart-panel">

                <?php if (!empty($productosCarrito)): ?>

                    <!-- ENCABEZADOS -->
                    <div class="cart-head">
                        <span>Producto</span>
                        <span>Talla</span>
                        <span>Cantidad</span>
                        <span>Precio unitario</span>
                        <span>Subtotal</span>
                        <span>Acciones</span>
                    </div>

                    <!-- PRODUCTOS DEL CARRITO -->
                    <?php foreach ($productosCarrito as $item): ?>
                        <article class="cart-row">

                            <!-- INFORMACIÓN DEL PRODUCTO -->
                            <div class="product-info">
                                <?php if (!empty($item["imagen"])): ?>
                                    <a href="descripcion_del_producto.php?id=<?= urlencode($item["slug"]) ?>">
                                        <img 
                                            class="product-image" 
                                            src="<?= htmlspecialchars($item["imagen"], ENT_QUOTES, "UTF-8") ?>" 
                                            alt="<?= htmlspecialchars($item["nombre"], ENT_QUOTES, "UTF-8") ?>"
                                        >
                                    </a>
                                <?php else: ?>
                                    <div class="sin-imagen">Sin imagen</div>
                                <?php endif; ?>

                                <div>
                                    <h2 class="product-name">
                                        <a href="descripcion_del_producto.php?id=<?= urlencode($item["slug"]) ?>">
                                            <?= htmlspecialchars($item["nombre"], ENT_QUOTES, "UTF-8") ?>
                                        </a>
                                    </h2>

                                    <p class="product-description">
                                        <?= htmlspecialchars($item["descripcion"], ENT_QUOTES, "UTF-8") ?>
                                    </p>

                                    <?php if ($item["descuento"] > 0): ?>
                                        <p class="cart-discount">
                                            Descuento: <?= number_format($item["descuento"], 0) ?>%
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- TALLA -->
                            <span class="size-badge">
                                <?= htmlspecialchars($item["talla"], ENT_QUOTES, "UTF-8") ?>
                            </span>

                            <!-- ACTUALIZAR CANTIDAD -->
                            <form action="procesos/carrito_acciones.php" method="POST" class="qty-form">
                                <input type="hidden" name="accion" value="actualizar">
                                <input type="hidden" name="clave" value="<?= htmlspecialchars($item["clave"], ENT_QUOTES, "UTF-8") ?>">

                                <button type="button" class="qty-minus" aria-label="Disminuir cantidad">−</button>
                                
                                <input 
                                    type="number" 
                                    name="cantidad" 
                                    class="qty-input" 
                                    value="<?= (int) $item["cantidad"] ?>" 
                                    min="1" 
                                    max="<?= (int) $item["stock"] ?>" 
                                    required
                                >

                                <button type="button" class="qty-plus" aria-label="Aumentar cantidad">+</button>
                                <button type="submit" class="update-button" title="Actualizar cantidad">✓</button>
                            </form>

                            <!-- PRECIO UNITARIO -->
                            <div class="unit-price">
                                <?php if ($item["descuento"] > 0): ?>
                                    <small class="previous-price">
                                        <del>$<?= number_format($item["precio_normal"], 2) ?></del>
                                    </small>
                                <?php endif; ?>

                                <strong>$<?= number_format($item["precio_unitario"], 2) ?> MXN</strong>
                            </div>

                            <!-- SUBTOTAL -->
                            <strong class="line-subtotal">
                                $<?= number_format($item["subtotal"], 2) ?> MXN
                            </strong>

                            <!-- ELIMINAR PRODUCTO -->
                            <form action="procesos/carrito_acciones.php" method="POST" class="remove-form">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="clave" value="<?= htmlspecialchars($item["clave"], ENT_QUOTES, "UTF-8") ?>">
                                <button type="submit" class="remove-button">Eliminar</button>
                            </form>

                        </article>
                    <?php endforeach; ?>

                    <!-- MENSAJE INFORMATIVO -->
                    <div class="secure-note">
                        <span>♡</span>
                        <div>
                            <strong>Revisa los productos de tu carrito</strong>
                            <p>Confirma las tallas y cantidades antes de continuar con el pago.</p>
                        </div>
                    </div>

                <?php else: ?>

                    <!-- CARRITO VACÍO -->
                    <div class="empty-cart">
                        <h2>Tu carrito está vacío</h2>
                        <p>Aún no has agregado ninguna playera.</p>
                        <a href="catalogo.php">Ver catálogo</a>
                    </div>

                <?php endif; ?>

            </div>

            <!-- ================= RESUMEN DEL PEDIDO ================= -->
            <aside class="summary-card">
                <h2>Resumen del pedido</h2>

                <div class="summary-line">
                    <span>
                        Subtotal (<span id="summary-products"><?= $cantidadTotal ?></span>
                        <?= $cantidadTotal === 1 ? "producto" : "productos" ?>)
                    </span>
                    <strong id="summary-subtotal">
                        $<?= number_format($subtotalGeneral, 2) ?> MXN
                    </strong>
                </div>

                <div class="summary-line">
                    <span>Envío</span>
                    <strong class="free-shipping">Gratis</strong>
                </div>

                <div class="summary-total">
                    <span>Total de compra</span>
                    <strong id="summary-total">
                        $<?= number_format($subtotalGeneral, 2) ?> MXN
                    </strong>
                </div>

                <div class="cart-actions">
                    <?php if (!empty($productosCarrito)): ?>
                        <a href="Modulo de pago.php" class="payment-button">
                            🛒 Continuar al pago
                        </a>
                    <?php endif; ?>

                    <a href="catalogo.php" class="continue-button">
                        Seguir comprando
                    </a>

                    <?php if (!empty($productosCarrito)): ?>
                        <form action="procesos/carrito_acciones.php" method="POST" class="empty-form">
                            <input type="hidden" name="accion" value="vaciar">
                            <button
                           type="submit"
                         class="empty-button"
                            >
                             Vaciar carrito
                          </button>
                        </form>
                    <?php endif; ?>
                </div>
            </aside>

        </section>

    </main>

    
    
    <script src="script/carrito.js"></script>

</body>

</html>