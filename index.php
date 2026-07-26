<?php

require_once __DIR__ . "/config/sesion.php";
require_once __DIR__ . "/config/conexion.php";



$mensajeCompra = $_SESSION["mensaje_compra"] ?? "";
$tipoMensajeCompra = $_SESSION["tipo_mensaje_compra"] ?? "";



unset(
    $_SESSION["mensaje_compra"],
    $_SESSION["tipo_mensaje_compra"]
);



$queryDestacados = "SELECT
        p.id_producto,
        p.slug,
        p.nombre,
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
        ) AS stock_total,
        (
            SELECT GROUP_CONCAT(
                pt.talla
                ORDER BY pt.id_producto_talla
                SEPARATOR ', '
            )
            FROM producto_tallas pt
            WHERE pt.id_producto = p.id_producto
              AND pt.stock > 0
        ) AS tallas_disponibles
    FROM productos p
    WHERE EXISTS (
        SELECT 1
        FROM producto_tallas pt
        WHERE pt.id_producto = p.id_producto
          AND pt.stock > 0
    )
    ORDER BY p.id_producto
    LIMIT 4";

$consultaDestacados = $conexion->prepare($queryDestacados);

if (!$consultaDestacados) {
    die("Error al preparar los productos destacados: " . $conexion->error);
}

$consultaDestacados->execute();
$resultadoDestacados = $consultaDestacados->get_result();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legacy Jerseys</title>

    <link rel="stylesheet" href="CSS/estilos.css">
    <link rel="stylesheet" href="CSS/index.css">
</head>

<body>

    
    <?php require_once __DIR__ . "/header/header.php"; ?>

    <!-- ================= MENSAJE DE COMPRA ================= -->
    <?php if ($mensajeCompra !== ""): ?>
        <div class="mensaje-compra <?= htmlspecialchars($tipoMensajeCompra, ENT_QUOTES, "UTF-8") ?>" role="alert">
            <strong>Compra finalizada:</strong>
            <span><?= htmlspecialchars($mensajeCompra, ENT_QUOTES, "UTF-8") ?></span>
        </div>
    <?php endif; ?>

 
    <section class="banner">
        <div class="texto-banner">
            <h1>LEGACY JERSEYS</h1>
            <p>Encuentra los mejores jerseys nacionales e internacionales.</p>
            <a href="catalogo.php" class="boton-banner">Ver catálogo</a>
        </div>

        <div class="imagen-banner">
            <img src="pictures/banner.jpg" alt="Colección de jerseys de fútbol">
        </div>
    </section>

   
    <section class="destacados">
        <h2>⭐ Productos destacados</h2>

        <div class="contenedor-productos">
            <?php if ($resultadoDestacados->num_rows > 0): ?>
                <?php while ($producto = $resultadoDestacados->fetch_assoc()): ?>
                    <?php
                    $precioNormal = (float) $producto["precio"];
                    $descuento = (float) $producto["descuento"];
                    $precioFinal = $precioNormal;

                    if ($descuento > 0) {
                        $precioFinal = $precioNormal - ($precioNormal * $descuento / 100);
                    }
                    ?>

                    <article class="producto">
                        <!-- IMAGEN -->
                        <a href="descripcion_del_producto.php?id=<?= urlencode($producto["slug"]) ?>" class="enlace-imagen-producto">
                            <?php if (!empty($producto["imagen"])): ?>
                                <img src="<?= htmlspecialchars($producto["imagen"], ENT_QUOTES, "UTF-8") ?>" alt="<?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>">
                            <?php else: ?>
                                <div class="producto-sin-imagen">Sin imagen disponible</div>
                            <?php endif; ?>
                        </a>

                        <!-- NOMBRE -->
                        <h3><?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?></h3>

                        <!-- MODELO -->
                        <?php if (!empty($producto["modelo"])): ?>
                            <p class="modelo-destacado">
                                <?= htmlspecialchars($producto["modelo"], ENT_QUOTES, "UTF-8") ?>
                            </p>
                        <?php endif; ?>

                        <!-- TALLAS -->
                        <p>
                            Tallas: <?= htmlspecialchars($producto["tallas_disponibles"] ?? "No disponibles", ENT_QUOTES, "UTF-8") ?>
                        </p>

                        <!-- PRECIO Y STOCK -->
                        <div class="info">
                            <span class="precio">$<?= number_format($precioFinal, 2) ?> MXN</span>
                            <span class="stock">
                                <?= (int) $producto["stock_total"] > 0 ? "Disponible" : "Agotado" ?>
                            </span>
                        </div>

                        <!-- DESCUENTO -->
                        <?php if ($descuento > 0): ?>
                            <p class="descuento-destacado">
                                Antes: <del>$<?= number_format($precioNormal, 2) ?> MXN</del> · <?= number_format($descuento, 0) ?>% de descuento
                            </p>
                        <?php endif; ?>

                        <!-- BOTÓN -->
                        <a href="descripcion_del_producto.php?id=<?= urlencode($producto["slug"]) ?>" class="boton-producto">
                            Ver producto
                        </a>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="destacados-vacios">
                    <h3>No hay productos destacados</h3>
                    <p>Actualmente no existen productos con stock disponible.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div>🔒 Pago seguro</div>
        <div>🚚 Envíos a todo México</div>
        <div>✔ Productos originales</div>
        <div>📞 +52 777 447 7773</div>
        <div class="copyright">© 2026 LEGACY JERSEYS</div>
    </footer>

   
    <script src="script/index.js"></script>

</body>
</html>

<?php
$consultaDestacados->close();
$conexion->close();
?>