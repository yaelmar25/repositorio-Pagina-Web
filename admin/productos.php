<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

requerirAdministrador(
    "../inicio_de_sesion.php",
    "../index.php"
);

$usuarioActual = obtenerUsuarioActual();

$mensajeAdmin = $_SESSION["mensaje_admin"] ?? "";
$tipoMensajeAdmin = $_SESSION["tipo_mensaje_admin"] ?? "";

unset($_SESSION["mensaje_admin"], $_SESSION["tipo_mensaje_admin"]);


$buscar = "";

if (isset($_GET["buscar"]) && is_string($_GET["buscar"])) {
    $buscar = trim($_GET["buscar"]);
}

$buscar = preg_replace("/\s+/u", " ", $buscar) ?? "";

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
        p.precio,
        p.descuento,
        (
            SELECT ip.ruta_imagen
            FROM imagenes_producto ip
            WHERE ip.id_producto = p.id_producto
            ORDER BY ip.id_imagen
            LIMIT 1
        ) AS imagen,
        COALESCE(SUM(pt.stock), 0) AS stock_total,
        COUNT(pt.id_producto_talla) AS total_tallas
    FROM productos p
    LEFT JOIN producto_tallas pt 
        ON pt.id_producto = p.id_producto
";

if ($buscar !== "") {
    $sql .= "
        WHERE (
            p.nombre LIKE ?
            OR p.equipo LIKE ?
            OR p.modelo LIKE ?
        )
    ";
}

$sql .= "
    GROUP BY
        p.id_producto,
        p.slug,
        p.nombre,
        p.equipo,
        p.modelo,
        p.precio,
        p.descuento
    ORDER BY p.id_producto DESC
";

$consultaProductos = $conexion->prepare($sql);

if (!$consultaProductos) {
    die("Error al preparar la consulta: " . $conexion->error);
}

if ($buscar !== "") {
    $terminoBusqueda = "%" . $buscar . "%";
    $consultaProductos->bind_param("sss", $terminoBusqueda, $terminoBusqueda, $terminoBusqueda);
}

$consultaProductos->execute();

$resultadoProductos = $consultaProductos->get_result();
$cantidadProductos = $resultadoProductos->num_rows;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos | Legacy Jerseys</title>
    <link rel="stylesheet" href="../CSS/admin.css?v=3">
</head>

<body>

    <?php if ($mensajeAdmin !== ""): ?>
        <div class="mensaje-admin <?= htmlspecialchars($tipoMensajeAdmin, ENT_QUOTES, "UTF-8") ?>" role="alert">
            <?= htmlspecialchars($mensajeAdmin, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <div class="panel-administrativo">

        <aside class="menu-lateral">

            <div class="marca-admin">
                <span class="marca-icono">LJ</span>
                <div>
                    <h1>Legacy Jerseys</h1>
                    <p>Administración</p>
                </div>
            </div>

            <nav class="navegacion-admin">
                <a href="index.php" class="enlace-admin">
                    <span>⌂</span>
                    Panel principal
                </a>
                <a href="productos.php" class="enlace-admin activo">
                    <span>▣</span>
                    Productos
                </a>
                <a href="inventario.php" class="enlace-admin">
                    <span>▤</span>
                    Inventario
                </a>
                <a href="pedidos.php" class="enlace-admin">
                    <span>✓</span>
                    Pedidos
                </a>
            </nav>

            <div class="menu-lateral-inferior">
                <a href="../index.php" class="enlace-tienda">
                    Ver tienda
                </a>

                <form action="../procesos/usuario_acciones.php" method="POST">
                    <input type="hidden" name="accion" value="cerrar">
                    <button type="submit" class="boton-cerrar-sesion">
                        Cerrar sesión
                    </button>
                </form>
            </div>

        </aside>

        <main class="contenido-admin">

            <!-- ENCABEZADO -->
            <header class="encabezado-admin">
                <div>
                    <p class="encabezado-etiqueta">Administración</p>
                    <h2>Productos</h2>
                </div>

                <div class="administrador-actual">
                    <div class="administrador-avatar">
                        <?= htmlspecialchars(strtoupper(substr($usuarioActual["nombre"] ?? "A", 0, 1)), ENT_QUOTES, "UTF-8") ?>
                    </div>
                    <div>
                        <strong>
                            <?= htmlspecialchars($usuarioActual["nombre"] ?? "Administrador", ENT_QUOTES, "UTF-8") ?>
                        </strong>
                        <span>Administrador</span>
                    </div>
                </div>
            </header>


            <!-- TÍTULO Y BOTÓN -->
            <section class="encabezado-seccion-admin">
                <div>
                    <span class="seccion-etiqueta">Catálogo</span>
                    <h3>Administración de productos</h3>
                    <p>Consulta y administra los jerseys registrados en la tienda.</p>
                </div>

                <a href="producto_nuevo.php" class="boton-admin-principal">
                    <span>＋</span>
                    Agregar producto
                </a>
            </section>


            <!-- BUSCADOR -->
            <section class="barra-herramientas-admin">
                <form action="productos.php" method="GET" class="formulario-busqueda-admin">
                    <input 
                        type="search" 
                        name="buscar" 
                        value="<?= htmlspecialchars($buscar, ENT_QUOTES, "UTF-8") ?>" 
                        placeholder="Buscar por nombre, equipo o modelo" 
                        maxlength="100"
                    >
                    <button type="submit">Buscar</button>
                </form>

                <?php if ($buscar !== ""): ?>
                    <a href="productos.php" class="limpiar-admin">
                        Limpiar búsqueda
                    </a>
                <?php endif; ?>
            </section>


            <!-- RESULTADO DE LA BÚSQUEDA -->
            <?php if ($buscar !== ""): ?>
                <div class="mensaje-resultados-admin">
                    Se encontraron 
                    <strong><?= $cantidadProductos ?></strong> 
                    productos relacionados con 
                    <strong>“<?= htmlspecialchars($buscar, ENT_QUOTES, "UTF-8") ?>”</strong>
                </div>
            <?php endif; ?>


            <!-- TABLA DE PRODUCTOS -->
            <section class="contenedor-tabla-admin">

                <?php if ($cantidadProductos > 0): ?>

                    <div class="tabla-responsive">
                        <table class="tabla-admin">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Equipo y modelo</th>
                                    <th>Precio</th>
                                    <th>Descuento</th>
                                    <th>Existencias</th>
                                    <th>Tallas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($producto = $resultadoProductos->fetch_assoc()): ?>
                                    <?php
                                    $precio = (float) $producto["precio"];
                                    $descuento = (float) $producto["descuento"];
                                    $precioFinal = $precio;

                                    if ($descuento > 0) {
                                        $precioFinal = $precio - ($precio * $descuento / 100);
                                    }

                                    $stockTotal = (int) $producto["stock_total"];
                                    ?>
                                    <tr>
                                        <!-- PRODUCTO -->
                                        <td>
                                            <div class="producto-tabla-admin">
                                                <?php if (!empty($producto["imagen"])): ?>
                                                    <img 
                                                        src="../<?= htmlspecialchars($producto["imagen"], ENT_QUOTES, "UTF-8") ?>" 
                                                        alt="<?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>"
                                                    >
                                                <?php else: ?>
                                                    <div class="imagen-vacia-admin">
                                                        Sin imagen
                                                    </div>
                                                <?php endif; ?>

                                                <div>
                                                    <strong>
                                                        <?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>
                                                    </strong>
                                                    <span>
                                                        ID: <?= (int) $producto["id_producto"] ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- EQUIPO Y MODELO -->
                                        <td>
                                            <strong class="texto-tabla-principal">
                                                <?= htmlspecialchars($producto["equipo"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                            </strong>
                                            <span class="texto-tabla-secundario">
                                                <?= htmlspecialchars($producto["modelo"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                            </span>
                                        </td>

                                        <!-- PRECIO -->
                                        <td>
                                            <?php if ($descuento > 0): ?>
                                                <span class="precio-anterior-admin">
                                                    $<?= number_format($precio, 2) ?>
                                                </span>
                                            <?php endif; ?>

                                            <strong class="precio-actual-admin">
                                                $<?= number_format($precioFinal, 2) ?> MXN
                                            </strong>
                                        </td>

                                        <!-- DESCUENTO -->
                                        <td>
                                            <?php if ($descuento > 0): ?>
                                                <span class="etiqueta-descuento-admin">
                                                    <?= number_format($descuento, 0) ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="texto-sin-descuento">
                                                    Sin descuento
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- STOCK -->
                                        <td>
                                            <?php if ($stockTotal === 0): ?>
                                                <span class="estado-stock agotado">
                                                    Agotado
                                                </span>
                                            <?php elseif ($stockTotal <= 5): ?>
                                                <span class="estado-stock bajo">
                                                    <?= $stockTotal ?> disponibles
                                                </span>
                                            <?php else: ?>
                                                <span class="estado-stock disponible">
                                                    <?= $stockTotal ?> disponibles
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- TALLAS -->
                                        <td>
                                            <strong class="cantidad-tallas-admin">
                                                <?= (int) $producto["total_tallas"] ?>
                                            </strong>
                                            <span class="texto-tabla-secundario">
                                                registradas
                                            </span>
                                        </td>

                                        <!-- ACCIONES -->
                                        <td>
                                            <div class="acciones-tabla-admin">
                                                <a 
                                                    href="../descripcion_del_producto.php?id=<?= urlencode($producto["slug"]) ?>" 
                                                    class="accion-ver-admin" 
                                                    title="Ver producto"
                                                >
                                                    Ver
                                                </a>
                                                <a 
                                                    href="producto_editar.php?id=<?= (int) $producto["id_producto"] ?>" 
                                                    class="accion-editar-admin" 
                                                    title="Editar producto"
                                                >
                                                    Editar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>

                    <div class="admin-sin-resultados">
                        <div class="admin-sin-resultados-icono">🔍</div>
                        <h3>No se encontraron productos</h3>

                        <?php if ($buscar !== ""): ?>
                            <p>
                                No existen productos relacionados con 
                                <strong>“<?= htmlspecialchars($buscar, ENT_QUOTES, "UTF-8") ?>”</strong>
                            </p>
                            <a href="productos.php" class="boton-admin-principal">
                                Mostrar todos
                            </a>
                        <?php else: ?>
                            <p>Todavía no existen productos registrados.</p>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

            </section>

        </main>

    </div>

</body>
</html>

<?php

$resultadoProductos->free();
$consultaProductos->close();
$conexion->close();

?>