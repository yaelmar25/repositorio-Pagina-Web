<?php

require_once __DIR__ . "/config/conexion.php";



$sql = "
    SELECT
        p.id_producto,
        p.slug,
        p.nombre,
        p.equipo,
        p.modelo,
        p.precio,
        p.descuento,
        (
            SELECT ip.ruta_imagen
            FROM imagenes_producto ip
            WHERE ip.id_producto = p.id_producto
            ORDER BY ip.id_imagen
            LIMIT 1
        ) AS imagen,
        (
            SELECT COALESCE(SUM(pt.stock), 0)
            FROM producto_tallas pt
            WHERE pt.id_producto = p.id_producto
        ) AS stock_total
    FROM productos p
    WHERE p.descuento > 0
      AND EXISTS (
            SELECT 1
            FROM producto_tallas pt
            WHERE pt.id_producto = p.id_producto
              AND pt.stock > 0
      )
    ORDER BY
        p.descuento DESC,
        p.id_producto ASC
";

$resultadoOfertas = $conexion->query($sql);

if (!$resultadoOfertas) {
    die("Error al consultar las ofertas: " . $conexion->error);
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ofertas | Legacy Jerseys</title>

    <link rel="stylesheet" href="CSS/estilos.css">
    <link rel="stylesheet" href="CSS/ofertas.css">
</head>

<body>

    
    <?php require_once __DIR__ . "/header/header.php"; ?>

    
    <main class="ofertas-page">
        <section class="ofertas-section">

            <h1 class="ofertas-titulo">🔥 Ofertas especiales 🔥</h1>
            <p class="ofertas-descripcion">Aprovecha nuestros descuentos disponibles por tiempo limitado.</p>

            <div class="ofertas-grid">
                <?php if ($resultadoOfertas->num_rows > 0): ?>
                    <?php while ($producto = $resultadoOfertas->fetch_assoc()): ?>
                        <?php
                        $precioNormal = (float) $producto["precio"];
                        $descuento    = (float) $producto["descuento"];
                        $precioOferta = $precioNormal - ($precioNormal * $descuento / 100);
                        $stockTotal   = (int) $producto["stock_total"];
                        ?>

                        <article class="oferta-card">
                            <!-- DESCUENTO -->
                            <span class="oferta-badge">
                                🔥 -<?= number_format($descuento, 0) ?>%
                            </span>

                            <!-- IMAGEN -->
                            <a href="descripcion_del_producto.php?id=<?= urlencode($producto["slug"]) ?>" class="oferta-imagen-link">
                                <?php if (!empty($producto["imagen"])): ?>
                                    <img 
                                        class="oferta-imagen" 
                                        src="<?= htmlspecialchars($producto["imagen"], ENT_QUOTES, "UTF-8") ?>" 
                                        alt="<?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>"
                                    >
                                <?php else: ?>
                                    <div class="oferta-sin-imagen">Sin imagen disponible</div>
                                <?php endif; ?>
                            </a>

                            <!-- CONTENIDO -->
                            <div class="oferta-contenido">
                                <h2 class="oferta-nombre">
                                    <?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>
                                </h2>

                                <p class="oferta-modelo">
                                    <?= htmlspecialchars($producto["modelo"], ENT_QUOTES, "UTF-8") ?>
                                </p>

                                <p class="oferta-precio-anterior">
                                    Antes: <del>$<?= number_format($precioNormal, 2) ?> MXN</del>
                                </p>

                                <p class="oferta-precio-final">
                                    $<?= number_format($precioOferta, 2) ?> MXN
                                </p>

                                <p class="oferta-stock">
                                    <?= $stockTotal === 1 ? "1 pieza disponible" : $stockTotal . " piezas disponibles" ?>
                                </p>

                                <a href="descripcion_del_producto.php?id=<?= urlencode($producto["slug"]) ?>" class="oferta-boton">
                                    Comprar ahora
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="ofertas-vacias">
                        <h3>No hay ofertas disponibles</h3>
                        <p>Actualmente no existen productos con descuento y existencias disponibles.</p>
                        <a href="catalogo.php">Ver catálogo</a>
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </main>

    <footer>
        <div>🔒 Pago seguro</div>
        <div>🚚 Envíos a todo México</div>
        <div>✔ Productos originales</div>
        <div>📞 +52 777 447 7773</div>
        <div class="copyright">© 2026 LEGACY JERSEYS</div>
    </footer>

</body>
</html>

<?php
$resultadoOfertas->free();
$conexion->close();
?>