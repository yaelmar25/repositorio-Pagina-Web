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

    
    <nav>
        <a href="index.php">Inicio</a>
        <a href="catalogo.php">Catálogo</a>
    </nav>

    
    <section class="titulo">
        <h2>CATÁLOGO DE CAMISETAS</h2>
        <p>Explora nuestra colección de clubes y selecciones nacionales.</p>
    </section>

    
    <section class="productos">
        <?php if ($resultadoProductos->num_rows > 0): ?>
            <?php while ($producto = $resultadoProductos->fetch_assoc()): ?>
                <div class="producto">
                    <?php if (!empty($producto["imagen"])): ?>
                        <img src="<?= htmlspecialchars($producto["imagen"]) ?>" 
                          alt="<?= htmlspecialchars($producto["nombre"]) ?>">
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($producto["nombre"]) ?></h3>
                    <p>$<?= number_format((float)$producto["precio"], 2) ?> MXN</p>

                    <a href="descripcion_del_producto.php?id=<?= urlencode($producto["slug"]) ?>">
                        <button type="button">Comprar</button>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No hay productos registrados en el catálogo.</p>
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