<?php
require_once __DIR__ . "/config/conexion.php";

/* =========================================================
   1. OBTENER EL PRODUCTO DESDE LA URL
========================================================= */
$slug = trim($_GET["id"] ?? "");

if ($slug === "") {
    http_response_code(400);
    die("No se indicó ningún producto.");
}

/* =========================================================
   2. CONSULTAR LA INFORMACIÓN DEL PRODUCTO
========================================================= */
$consultaProducto = $conexion->prepare(
    "SELECT
        id_producto,
        slug,
        nombre,
        equipo,
        modelo,
        descripcion,
        precio,
        descuento
    FROM productos
    WHERE slug = ?"
);

if (!$consultaProducto) {
    die("Error al preparar la consulta del producto: " . $conexion->error);
}

$consultaProducto->bind_param("s", $slug);
$consultaProducto->execute();

$resultadoProducto = $consultaProducto->get_result();
$producto = $resultadoProducto->fetch_assoc();

if (!$producto) {
    http_response_code(404);
    die("El producto solicitado no existe.");
}

/* =========================================================
   3. CONSULTAR LAS IMÁGENES DEL PRODUCTO
========================================================= */
$consultaImagenes = $conexion->prepare(
    "SELECT ruta_imagen
    FROM imagenes_producto
    WHERE id_producto = ?
    ORDER BY id_imagen"
);

if (!$consultaImagenes) {
    die("Error al preparar la consulta de imágenes: " . $conexion->error);
}

$consultaImagenes->bind_param("i", $producto["id_producto"]);
$consultaImagenes->execute();

$resultadoImagenes = $consultaImagenes->get_result();
$imagenes = [];

while ($registroImagen = $resultadoImagenes->fetch_assoc()) {
    $imagenes[] = $registroImagen["ruta_imagen"];
}

/* =========================================================
   4. CONSULTAR TALLAS E INVENTARIO
========================================================= */
$consultaTallas = $conexion->prepare(
    "SELECT
        talla,
        stock
    FROM producto_tallas
    WHERE id_producto = ?
    ORDER BY id_producto_talla"
);

if (!$consultaTallas) {
    die("Error al preparar la consulta de tallas: " . $conexion->error);
}

$consultaTallas->bind_param("i", $producto["id_producto"]);
$consultaTallas->execute();

$resultadoTallas = $consultaTallas->get_result();
$tallas = [];
$stockTotal = 0;

while ($registroTalla = $resultadoTallas->fetch_assoc()) {
    $registroTalla["stock"] = (int) $registroTalla["stock"];
    $tallas[] = $registroTalla;
    $stockTotal += $registroTalla["stock"];
}

/* =========================================================
   5. CALCULAR PRECIO Y DESCUENTO
========================================================= */
$precioNormal = (float) $producto["precio"];
$descuento = (float) $producto["descuento"];
$precioFinal = $precioNormal;

if ($descuento > 0) {
    $precioFinal = $precioNormal - ($precioNormal * $descuento / 100);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?> | Legacy Jerseys</title>
    <link rel="stylesheet" href="CSS/estilos.css">
    <link rel="stylesheet" href="CSS/descripcion_producto.css">
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

    <!-- ================= CONTENIDO PRINCIPAL ================= -->
    <main class="product-page">

        <!-- RUTA DE NAVEGACIÓN -->
        <p class="breadcrumb">
            <a href="catalogo.php">Catálogo</a> ➜ 
            <span id="miga-producto"><?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?></span>
        </p>

        <!-- DETALLE DEL PRODUCTO -->
        <section class="product-detail">

            <!-- ================= GALERÍA ================= -->
            <div class="product-gallery">
                <?php if (!empty($imagenes)): ?>
                    <img id="producto-imagen" src="<?= htmlspecialchars($imagenes[0], ENT_QUOTES, "UTF-8") ?>" alt="<?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>" class="main-product-image">
                    
                    <div class="product-thumbnails">
                        <?php foreach ($imagenes as $indice => $rutaImagen): ?>
                            <img src="<?= htmlspecialchars($rutaImagen, ENT_QUOTES, "UTF-8") ?>" 
                                 alt="Vista <?= $indice + 1 ?> de <?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>" 
                                 data-ruta="<?= htmlspecialchars($rutaImagen, ENT_QUOTES, "UTF-8") ?>" 
                                 class="miniatura-producto <?= $indice === 0 ? "selected-image" : "" ?>">
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="sin-imagen">
                        No hay imágenes registradas para este producto.
                    </div>
                <?php endif; ?>
            </div>

            <!-- ================= INFORMACIÓN ================= -->
            <div class="product-info-detail">
                <h1 id="producto-titulo"><?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?></h1>

                <h3>Equipo</h3>
                <p><?= htmlspecialchars($producto["equipo"], ENT_QUOTES, "UTF-8") ?></p>

                <h3>Modelo</h3>
                <p id="producto-modelo"><?= htmlspecialchars($producto["modelo"], ENT_QUOTES, "UTF-8") ?></p>

                <h3>Descripción</h3>
                <p id="producto-descripcion"><?= nl2br(htmlspecialchars($producto["descripcion"], ENT_QUOTES, "UTF-8")) ?></p>

                <!-- ================= PRECIO ================= -->
                <div id="producto-precio">
                    <?php if ($descuento > 0): ?>
                        <p class="precio-normal">
                            Antes: <del>$<?= number_format($precioNormal, 2) ?> MXN</del>
                        </p>
                        <strong class="precio-final">
                            $<?= number_format($precioFinal, 2) ?> MXN
                        </strong>
                        <p class="porcentaje-descuento">
                            Descuento: <?= number_format($descuento, 0) ?>%
                        </p>
                    <?php else: ?>
                        <strong class="precio-final">
                            $<?= number_format($precioNormal, 2) ?> MXN
                        </strong>
                    <?php endif; ?>
                </div>

                <!-- ================= TALLAS ================= -->
                <h3>Tallas disponibles</h3>
                <div class="sizes">
                    <?php if (!empty($tallas)): ?>
                        <?php foreach ($tallas as $registroTalla): ?>
                            <button type="button" 
                                    class="size-button" 
                                    data-talla="<?= htmlspecialchars($registroTalla["talla"], ENT_QUOTES, "UTF-8") ?>" 
                                    data-stock="<?= (int) $registroTalla["stock"] ?>" 
                                    <?= $registroTalla["stock"] <= 0 ? "disabled" : "" ?>>
                                <?= htmlspecialchars($registroTalla["talla"], ENT_QUOTES, "UTF-8") ?> 
                                (<?= (int) $registroTalla["stock"] ?>)
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No hay tallas registradas para este producto.</p>
                    <?php endif; ?>
                </div>

                <!-- ================= INVENTARIO ================= -->
                <p class="stock">
                    Disponibles: 
                    <strong id="producto-inventario"><?= $stockTotal ?> piezas en total</strong>
                </p>

                <!-- ================= CANTIDAD ================= -->
                <div class="quantity">
                    <button type="button" id="restar-cantidad" aria-label="Disminuir cantidad">−</button>
                    <span id="cantidad-producto">1</span>
                    <button type="button" id="sumar-cantidad" aria-label="Aumentar cantidad">+</button>
                </div>

                <!-- ================= FORMULARIO DEL CARRITO ================= -->
                <form action="procesos/carrito_acciones.php" method="POST" id="formulario-carrito">
                    <input type="hidden" name="accion" value="agregar">
                    <input type="hidden" name="id_producto" value="<?= (int) $producto["id_producto"] ?>">
                    <input type="hidden" name="talla" id="talla-seleccionada" value="">
                    <input type="hidden" name="cantidad" id="cantidad-seleccionada" value="1">

                    <button type="submit" class="primary-button" id="agregar-carrito" <?= $stockTotal <= 0 ? "disabled" : "" ?>>
                        🛒 Agregar al carrito
                    </button>
                </form>

            </div>

        </section>
    </main>

    <script src="script/descripcion_producto.js"></script>
</body>
</html>