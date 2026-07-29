<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

requerirAdministrador(
    "../inicio_de_sesion.php",
    "../index.php"
);

$usuarioActual = obtenerUsuarioActual();


/* =========================================================
   TOKEN DE SEGURIDAD (CSRF)
========================================================= */

if (
    !isset($_SESSION["csrf_admin"]) ||
    !is_string($_SESSION["csrf_admin"])
) {
    $_SESSION["csrf_admin"] = bin2hex(random_bytes(32));
}

$tokenCsrf = $_SESSION["csrf_admin"];


/* =========================================================
   MENSAJES DE NOTIFICACIÓN
========================================================= */

$mensajeAdmin = $_SESSION["mensaje_admin"] ?? "";
$tipoMensajeAdmin = $_SESSION["tipo_mensaje_admin"] ?? "";

unset(
    $_SESSION["mensaje_admin"],
    $_SESSION["tipo_mensaje_admin"]
);


/* =========================================================
   OBTENER Y PROCESAR PARÁMETRO DE BÚSQUEDA
========================================================= */

$buscar = "";

if (
    isset($_GET["buscar"]) &&
    is_string($_GET["buscar"])
) {
    $buscar = trim($_GET["buscar"]);
}

$buscar = preg_replace("/\s+/u", " ", $buscar) ?? "";

if (mb_strlen($buscar) > 100) {
    $buscar = mb_substr($buscar, 0, 100);
}


/* =========================================================
   CONSULTAR PRODUCTOS Y SUS EXISTENCIAS
========================================================= */

$sql = "
    SELECT
        p.id_producto,
        p.nombre,
        p.equipo,
        p.modelo,

        (
            SELECT ip.ruta_imagen
            FROM imagenes_producto ip
            WHERE ip.id_producto = p.id_producto
            ORDER BY ip.id_imagen ASC
            LIMIT 1
        ) AS imagen,

        pt.talla,
        pt.stock

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
    ORDER BY
        p.nombre ASC,
        FIELD(
            pt.talla,
            'XS',
            'S',
            'M',
            'L',
            'XL',
            'XXL'
        )
";

$consultaInventario = $conexion->prepare($sql);

if (!$consultaInventario) {
    die(
        "Error al preparar la consulta del inventario: " .
        $conexion->error
    );
}

if ($buscar !== "") {
    $terminoBusqueda = "%" . $buscar . "%";
    $consultaInventario->bind_param(
        "sss",
        $terminoBusqueda,
        $terminoBusqueda,
        $terminoBusqueda
    );
}

$consultaInventario->execute();
$resultadoInventario = $consultaInventario->get_result();


/* =========================================================
   ORGANIZAR DATOS EN ESTRUCTURA DE MATRIZ
========================================================= */

$productos = [];

while ($fila = $resultadoInventario->fetch_assoc()) {
    $idProducto = (int) $fila["id_producto"];

    if (!isset($productos[$idProducto])) {
        $productos[$idProducto] = [
            "id_producto" => $idProducto,
            "nombre"      => $fila["nombre"],
            "equipo"      => $fila["equipo"],
            "modelo"      => $fila["modelo"],
            "imagen"      => $fila["imagen"],
            "stocks"      => []
        ];
    }

    if ($fila["talla"] !== null) {
        $productos[$idProducto]["stocks"][$fila["talla"]] = (int) $fila["stock"];
    }
}

$resultadoInventario->free();

$cantidadProductos = count($productos);

$tallasPermitidas = [
    "XS",
    "S",
    "M",
    "L",
    "XL",
    "XXL"
];

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario | Legacy Jerseys</title>
    <link rel="stylesheet" href="../CSS/admin.css?v=8">
</head>

<body>

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
                    <span>⌂</span> Panel principal
                </a>
                <a href="productos.php" class="enlace-admin">
                    <span>▣</span> Productos
                </a>
                <a href="inventario.php" class="enlace-admin activo">
                    <span>▤</span> Inventario
                </a>
                <a href="pedidos.php" class="enlace-admin">
                    <span>✓</span> Pedidos
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


            <header class="encabezado-admin">
                <div>
                    <p class="encabezado-etiqueta">Administración</p>
                    <h2>Inventario</h2>
                </div>

                <div class="administrador-actual">
                    <div class="administrador-avatar">
                        <?= htmlspecialchars(
                            strtoupper(
                                mb_substr(
                                    $usuarioActual["nombre"] ?? "A",
                                    0,
                                    1
                                )
                            ),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </div>
                    <div>
                        <strong>
                            <?= htmlspecialchars(
                                $usuarioActual["nombre"] ?? "Administrador",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>
                        <span>Administrador</span>
                    </div>
                </div>
            </header>


            <!-- TÍTULO DE LA SECCIÓN Y ACCIONES -->
            <section class="encabezado-seccion-admin">
                <div>
                    <span class="seccion-etiqueta">Existencias</span>
                    <h3>Control de inventario</h3>
                    <p>Modifica la cantidad disponible de cada producto y talla.</p>
                </div>

                <a href="producto_nuevo.php" class="boton-admin-principal">
                    <span>＋</span> Agregar producto
                </a>
            </section>


            <!-- MENSAJES DE ALERTA -->
            <?php if ($mensajeAdmin !== ""): ?>
                <div class="mensaje-admin <?= htmlspecialchars($tipoMensajeAdmin, ENT_QUOTES, "UTF-8") ?>" role="alert">
                    <?= htmlspecialchars($mensajeAdmin, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>


            <!-- BARRA DE BÚSQUEDA -->
            <section class="barra-herramientas-admin">
                <form action="inventario.php" method="GET" class="formulario-busqueda-admin">
                    <input 
                        type="search" 
                        name="buscar" 
                        value="<?= htmlspecialchars($buscar, ENT_QUOTES, "UTF-8") ?>" 
                        placeholder="Buscar por producto, equipo o modelo" 
                        maxlength="100"
                    >
                    <button type="submit">Buscar</button>
                </form>

                <?php if ($buscar !== ""): ?>
                    <a href="inventario.php" class="limpiar-admin">
                        Limpiar búsqueda
                    </a>
                <?php endif; ?>
            </section>


            <!-- INDICADOR DE RESULTADOS -->
            <?php if ($buscar !== ""): ?>
                <div class="mensaje-resultados-admin">
                    Se encontraron <strong><?= $cantidadProductos ?></strong> productos relacionados con <strong>“<?= htmlspecialchars($buscar, ENT_QUOTES, "UTF-8") ?>”</strong>
                </div>
            <?php endif; ?>


            <!-- INDICACIÓN OPERATIVA -->
            <div class="inventario-indicacion">
                <strong>¿Cómo funciona?</strong>
                <span>Escribe la cantidad disponible de cada talla. Deja vacío un campo para eliminar esa talla del producto.</span>
            </div>


            <!-- TABLA DE CONTROL DE STOCK -->
            <section class="contenedor-tabla-admin">
                <?php if ($cantidadProductos > 0): ?>
                    <div class="tabla-responsive">
                        <table class="tabla-admin tabla-inventario-admin">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <?php foreach ($tallasPermitidas as $talla): ?>
                                        <th class="columna-talla"><?= $talla ?></th>
                                    <?php endforeach; ?>
                                    <th>Total</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): ?>
                                    <?php
                                        $idProducto = (int) $producto["id_producto"];
                                        $stockTotal = array_sum($producto["stocks"]);
                                        $idFormulario = "form-stock-" . $idProducto;
                                    ?>
                                    <tr>
                                        <!-- DETALLES DEL PRODUCTO -->
                                        <td>
                                            <div class="producto-tabla-admin producto-inventario-admin">
                                                <?php if (!empty($producto["imagen"])): ?>
                                                    <img 
                                                        src="../<?= htmlspecialchars($producto["imagen"], ENT_QUOTES, "UTF-8") ?>" 
                                                        alt="<?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>"
                                                    >
                                                <?php else: ?>
                                                    <div class="imagen-vacia-admin">Sin imagen</div>
                                                <?php endif; ?>

                                                <div>
                                                    <strong><?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?></strong>
                                                    <span><?= htmlspecialchars($producto["equipo"] ?? "", ENT_QUOTES, "UTF-8") ?></span>
                                                    <span><?= htmlspecialchars($producto["modelo"] ?? "", ENT_QUOTES, "UTF-8") ?></span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- INPUTS DE STOCK POR TALLA -->
                                        <?php foreach ($tallasPermitidas as $talla): ?>
                                            <td class="celda-stock-admin">
                                                <input 
                                                    type="number" 
                                                    name="stock[<?= $talla ?>]" 
                                                    value="<?= isset($producto["stocks"][$talla]) ? (int) $producto["stocks"][$talla] : "" ?>" 
                                                    min="0" 
                                                    max="100000" 
                                                    step="1" 
                                                    class="campo-stock-tabla" 
                                                    aria-label="Stock talla <?= $talla ?> de <?= htmlspecialchars($producto["nombre"], ENT_QUOTES, "UTF-8") ?>" 
                                                    form="<?= $idFormulario ?>"
                                                >
                                            </td>
                                        <?php endforeach; ?>

                                        <!-- INDICADOR DE TOTAL -->
                                        <td>
                                            <?php if ($stockTotal === 0): ?>
                                                <span class="estado-stock agotado">Agotado</span>
                                            <?php elseif ($stockTotal <= 5): ?>
                                                <div class="total-stock-admin stock-bajo-admin">
                                                    <strong><?= $stockTotal ?></strong>
                                                    <span>unidades</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="total-stock-admin">
                                                    <strong><?= $stockTotal ?></strong>
                                                    <span>unidades</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- BOTÓN Y FORMULARIO DE GUARDADO -->
                                        <td>
                                            <form 
                                                action="../procesos/admin_inventario_acciones.php" 
                                                method="POST" 
                                                id="<?= $idFormulario ?>"
                                            >
                                                <input type="hidden" name="accion" value="actualizar">
                                                <input type="hidden" name="id_producto" value="<?= $idProducto ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, "UTF-8") ?>">

                                                <button type="submit" class="boton-admin-secundario">
                                                    Guardar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>