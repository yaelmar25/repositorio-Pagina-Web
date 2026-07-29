<?php
require_once __DIR__ . "/config/sesion.php";
require_once __DIR__ . "/config/conexion.php";



$buscar = "";

if (isset($_GET["buscar"]) && is_string($_GET["buscar"])) {
    $buscar = trim($_GET["buscar"]);
}

// Sustituir varios espacios consecutivos por uno. Ejemplo: "Real    Madrid" -> "Real Madrid".
$buscar = preg_replace("/\s+/u", " ", $buscar) ?? "";

// Limitar el término a 100 caracteres.
if (strlen($buscar) > 100) {
    $buscar = substr($buscar, 0, 100);
}



$sql = "
    SELECT
        p.id_producto,
        p.slug,
        p.nombre,
        p.equipo,
        p.modelo,
        p.descripcion,
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
";



if ($buscar !== "") {
    $sql .= "
        WHERE (
            p.nombre LIKE ?
            OR p.equipo LIKE ?
            OR p.modelo LIKE ?
            OR p.descripcion LIKE ?
        )
    ";
}



$sql .= " ORDER BY p.id_producto";

$consultaProductos = $conexion->prepare($sql);

if (!$consultaProductos) {
    die("Error al preparar la consulta de productos: " . $conexion->error);
}



if ($buscar !== "") {
    $terminoBusqueda = "%" . $buscar . "%";
    $consultaProductos->bind_param(
        "ssss",
        $terminoBusqueda,
        $terminoBusqueda,
        $terminoBusqueda,
        $terminoBusqueda
    );
}

$consultaProductos->execute();
$resultadoProductos = $consultaProductos->get_result();
$cantidadResultados = $resultadoProductos->num_rows;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo | Legacy Jerseys</title>
    <link rel="stylesheet" href="CSS/estilos.css">
    <link rel="stylesheet" href="CSS/catalogo.css?">
</head>

<body>

   
    <?php require_once __DIR__ . "/header/header.php"; ?>

    <main>

        <!-- TÍTULO DEL CATÁLOGO -->
        <section class="titulo">
            <h2>CATÁLOGO DE CAMISETAS</h2>
            <p>Explora nuestra colección de clubes y selecciones nacionales.</p>
        </section>

        <!-- INFORMACIÓN DE LA BÚSQUEDA -->
        <?php if ($buscar !== ""): ?>
            <section class="resultado-busqueda" aria-live="polite">
                <div class="resultado-busqueda-informacion">
                    <span class="resultado-busqueda-etiqueta">
                        Resultados de búsqueda
                    </span>
                    <p class="resultado-busqueda-texto">
                        <?php if ($cantidadResultados === 1): ?>
                            Se encontró <strong>1 producto</strong> relacionado con
                        <?php else: ?>
                            Se encontraron <strong><?= $cantidadResultados ?> productos</strong> relacionados con
                        <?php endif; ?>
                        <span class="termino-busqueda">
                            “<?= htmlspecialchars($buscar, ENT_QUOTES, "UTF-8") ?>”
                        </span>
                    </p>
                </div>

                <a href="catalogo.php" class="limpiar-busqueda" aria-label="Limpiar búsqueda y mostrar todo el catálogo">
                    <span aria-hidden="true">×</span> Limpiar búsqueda
                </a>
            </section>
        <?php endif; ?>

        <!-- PRODUCTOS -->
        <section class="productos">

            <?php if ($cantidadResultados > 0): ?>

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

                        <!-- IMAGEN DEL PRODUCTO -->
                        <?php if (!empty($producto["imagen"])): ?>
                            <a href="descripcion_del_producto.php?id=<?= urlencode($producto["slug"]) ?>" class="enlace-imagen-producto">
                                <img src="<?= htmlspecialchars($producto["imagen"], ENT_QUOTES, "UTF-8") ?>"
                                     alt="<?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>"
                                     loading="lazy">
                            </a>
                        <?php else: ?>
                            <div class="producto-sin-imagen">
                                Sin imagen disponible
                            </div>
                        <?php endif; ?>

                        <!-- NOMBRE -->
                        <h3><?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?></h3>

                        <!-- EQUIPO -->
                        <?php if (!empty($producto["equipo"])): ?>
                            <p class="equipo-producto">
                                <?= htmlspecialchars($producto["equipo"], ENT_QUOTES, "UTF-8") ?>
                            </p>
                        <?php endif; ?>

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

                        <!-- BOTÓN -->
                        <a href="descripcion_del_producto.php?id=<?= urlencode($producto["slug"]) ?>" class="boton-producto">
                            Ver producto
                        </a>

                    </article>
                <?php endwhile; ?>

            <?php else: ?>

                <!-- CATÁLOGO SIN RESULTADOS -->
                <div class="catalogo-vacio">
                    <?php if ($buscar !== ""): ?>
                        <div class="catalogo-vacio-icono" aria-hidden="true">🔍</div>
                        <h3>No se encontraron productos</h3>
                        <p>
                            No existen jerseys relacionados con
                            <strong>“<?= htmlspecialchars($buscar, ENT_QUOTES, "UTF-8") ?>”</strong>
                        </p>
                        <p class="catalogo-vacio-sugerencia">
                            Verifica la escritura o intenta buscar por equipo, modelo o nombre del producto.
                        </p>
                        <a href="catalogo.php" class="boton-ver-catalogo">
                            Ver todo el catálogo
                        </a>
                    <?php else: ?>
                        <h3>No hay productos disponibles</h3>
                        <p>Actualmente no existen productos registrados en el catálogo.</p>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </section>

    </main>

    <!-- PIE DE PÁGINA -->
    <footer>
        <h3>LEGACY JERSEYS</h3>
        <p>Encuentra camisetas originales, retro y ediciones especiales de los mejores clubes y selecciones.</p>
        <p>© 2026 Legacy Jerseys. Todos los derechos reservados.</p>
    </footer>

</body>
</html>

<?php

$resultadoProductos->free();
$consultaProductos->close();
$conexion->close();
?>