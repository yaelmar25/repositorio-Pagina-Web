<?php

require_once __DIR__ . "/config/conexion.php";

/* =========================================================
   CONSULTAR LOS PRODUCTOS
========================================================= */

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
        ) AS imagen
    FROM productos p
    ORDER BY p.id_producto
";

$resultadoProductos = $conexion->query($sql);

if (!$resultadoProductos) {
    die("Error al consultar los productos: " . $conexion->error);
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo | Legacy Jerseys</title>
    <link rel="stylesheet" href="CSS/estilos.css">
</head>

<body>

    <!-- ================= ENCABEZADO REUTILIZABLE ================= -->
    <?php require_once __DIR__ . "/header/header.php"; ?>

    <!-- ================= TÍTULO ================= -->
    <section class="titulo">
        <h2>CATÁLOGO DE CAMISETAS</h2>
        <p>Explora nuestra colección de clubes y selecciones nacionales.</p>
    </section>

    <!-- ================= PRODUCTOS ================= -->
    <section class="productos">
        <?php if ($resultadoProductos->num_rows > 0): ?>

            <?php while ($producto = $resultadoProductos->fetch_assoc()): ?>
                <?php
                $precioNormal = (float) $producto["precio"];
                $descuento    = (float) $producto["descuento"];
                $precioFinal  = $precioNormal;

                if ($descuento > 0) {
                    $precioFinal = $precioNormal - ($precioNormal * $descuento / 100);
                }
                ?>

                <article class="producto">

                    <!-- IMAGEN -->
                    <?php if (!empty($producto["imagen"])): ?>
                        <a href="descripcion_del_producto.php?id=<?= urlencode($producto["slug"]) ?>">
                            <img src="<?= htmlspecialchars($producto["imagen"], ENT_QUOTES, "UTF-8") ?>" alt="<?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>">
                        </a>
                    <?php else: ?>
                        <div class="producto-sin-imagen">Sin imagen disponible</div>
                    <?php endif; ?>

                    <!-- NOMBRE -->
                    <h3>
                        <?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>
                    </h3>

                    <!-- MODELO -->
                    <?php if (!empty($producto["modelo"])): ?>
                        <p class="modelo-producto">
                            <?= htmlspecialchars($producto["modelo"], ENT_QUOTES, "UTF-8") ?>
                        </p>
                    <?php endif; ?>

                    <!-- PRECIO -->
                    <div class="precio-catalogo">
                        <?php if ($descuento > 0): ?>
                            <span class="precio-anterior">
                                <del>$<?= number_format($precioNormal, 2) ?> MXN</del>
                            </span>
                            <span class="descuento-producto">
                                <?= number_format($descuento, 0) ?>% de descuento
                            </span>
                        <?php endif; ?>

                        <strong class="precio-actual">
                            $<?= number_format($precioFinal, 2) ?> MXN
                        </strong>
                    </div>

                    <!-- ENLACE -->
                    <a href="descripcion_del_producto.php?id=<?= urlencode($producto["slug"]) ?>" class="boton-producto">
                        Ver producto
                    </a>

                </article>
            <?php endwhile; ?>

        <?php else: ?>

            <div class="catalogo-vacio">
                <h3>No hay productos disponibles</h3>
                <p>Actualmente no existen productos registrados en el catálogo.</p>
            </div>

        <?php endif; ?>
    </section>

    <!-- ================= FOOTER ================= -->
    <footer>
        <h3>LEGACY JERSEYS</h3>
        <p>Encuentra camisetas originales, retro y ediciones especiales de los mejores clubes y selecciones.</p>
        <p>© 2026 Legacy Jerseys. Todos los derechos reservados.</p>
    </footer>

</body>

</html>
<?php

$resultadoProductos->free();
$conexion->close();

?>