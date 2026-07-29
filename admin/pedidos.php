<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

requerirAdministrador("../inicio_de_sesion.php", "../index.php");

$usuarioActual = obtenerUsuarioActual();


$mensajeAdmin = $_SESSION["mensaje_admin"] ?? "";
$tipoMensajeAdmin = $_SESSION["tipo_mensaje_admin"] ?? "";

unset($_SESSION["mensaje_admin"], $_SESSION["tipo_mensaje_admin"]);


$buscar = "";

if (isset($_GET["buscar"]) && is_string($_GET["buscar"])) {
    $buscar = trim($_GET["buscar"]);
}

$buscar = preg_replace("/\s+/u", " ", $buscar) ?? "";

if (mb_strlen($buscar) > 100) {
    $buscar = mb_substr($buscar, 0, 100);
}


$estadoFiltro = "";

if (isset($_GET["estado"]) && is_string($_GET["estado"])) {
    $estadoFiltro = strtolower(trim($_GET["estado"]));
}

$estadosPermitidos = [
    "pendiente",
    "confirmado",
    "enviado",
    "entregado",
    "cancelado"
];

if ($estadoFiltro !== "" && !in_array($estadoFiltro, $estadosPermitidos, true)) {
    $estadoFiltro = "";
}


$sql = "
    SELECT
        pe.id_pedido,
        pe.id_usuario,
        pe.direccion,
        pe.total,
        pe.estado,
        pe.fecha_pedido,
        u.nombre AS nombre_usuario,
        u.correo,
        COUNT(DISTINCT dp.id_producto) AS productos_diferentes,
        COALESCE(SUM(dp.cantidad), 0) AS total_unidades
    FROM pedidos pe
    INNER JOIN usuarios u ON u.id_usuario = pe.id_usuario
    LEFT JOIN detalle_pedido dp ON dp.id_pedido = pe.id_pedido
";

if ($buscar !== "" && $estadoFiltro !== "") {
    $sql .= "
        WHERE (
            CAST(pe.id_pedido AS CHAR) LIKE ?
            OR u.nombre LIKE ?
            OR u.correo LIKE ?
            OR pe.direccion LIKE ?
        )
        AND pe.estado = ?
    ";
} elseif ($buscar !== "") {
    $sql .= "
        WHERE (
            CAST(pe.id_pedido AS CHAR) LIKE ?
            OR u.nombre LIKE ?
            OR u.correo LIKE ?
            OR pe.direccion LIKE ?
        )
    ";
} elseif ($estadoFiltro !== "") {
    $sql .= "
        WHERE pe.estado = ?
    ";
}

$sql .= "
    GROUP BY
        pe.id_pedido,
        pe.id_usuario,
        pe.direccion,
        pe.total,
        pe.estado,
        pe.fecha_pedido,
        u.nombre,
        u.correo
    ORDER BY
        pe.fecha_pedido DESC,
        pe.id_pedido DESC
";

$consultaPedidos = $conexion->prepare($sql);

if (!$consultaPedidos) {
    die("Error al preparar la consulta de pedidos: " . $conexion->error);
}


if ($buscar !== "" && $estadoFiltro !== "") {
    $terminoBusqueda = "%" . $buscar . "%";
    $consultaPedidos->bind_param(
        "sssss",
        $terminoBusqueda,
        $terminoBusqueda,
        $terminoBusqueda,
        $terminoBusqueda,
        $estadoFiltro
    );
} elseif ($buscar !== "") {
    $terminoBusqueda = "%" . $buscar . "%";
    $consultaPedidos->bind_param(
        "ssss",
        $terminoBusqueda,
        $terminoBusqueda,
        $terminoBusqueda,
        $terminoBusqueda
    );
} elseif ($estadoFiltro !== "") {
    $consultaPedidos->bind_param("s", $estadoFiltro);
}

$consultaPedidos->execute();

$resultadoPedidos = $consultaPedidos->get_result();
$cantidadPedidos  = $resultadoPedidos->num_rows;


$consultaContadores = $conexion->query(
    "SELECT
        COUNT(*) AS total_pedidos,
        COALESCE(SUM(estado = 'pendiente'), 0) AS pendientes,
        COALESCE(SUM(estado = 'confirmado'), 0) AS confirmados,
        COALESCE(SUM(estado = 'enviado'), 0) AS enviados,
        COALESCE(SUM(estado = 'entregado'), 0) AS entregados
     FROM pedidos"
);

$contadores = [
    "total_pedidos" => 0,
    "pendientes"    => 0,
    "confirmados"   => 0,
    "enviados"      => 0,
    "entregados"    => 0
];

if ($consultaContadores) {
    $filaContadores = $consultaContadores->fetch_assoc();

    if ($filaContadores) {
        $contadores = array_merge($contadores, $filaContadores);
    }

    $consultaContadores->free();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos | Legacy Jerseys</title>
    <link rel="stylesheet" href="../CSS/admin.css?v=10">
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
                <a href="inventario.php" class="enlace-admin">
                    <span>▤</span> Inventario
                </a>
                <a href="pedidos.php" class="enlace-admin activo">
                    <span>✓</span> Pedidos
                </a>
            </nav>

            <div class="menu-lateral-inferior">
                <a href="../index.php" class="enlace-tienda">Ver tienda</a>
                <form action="../procesos/usuario_acciones.php" method="POST">
                    <input type="hidden" name="accion" value="cerrar">
                    <button type="submit" class="boton-cerrar-sesion">Cerrar sesión</button>
                </form>
            </div>
        </aside>


        <main class="contenido-admin">

            <!-- ENCABEZADO -->
            <header class="encabezado-admin">
                <div>
                    <p class="encabezado-etiqueta">Administración</p>
                    <h2>Pedidos</h2>
                </div>

                <div class="administrador-actual">
                    <div class="administrador-avatar">
                        <?= htmlspecialchars(strtoupper(mb_substr($usuarioActual["nombre"] ?? "A", 0, 1)), ENT_QUOTES, "UTF-8") ?>
                    </div>
                    <div>
                        <strong>
                            <?= htmlspecialchars($usuarioActual["nombre"] ?? "Administrador", ENT_QUOTES, "UTF-8") ?>
                        </strong>
                        <span>Administrador</span>
                    </div>
                </div>
            </header>


            <!-- TÍTULO DE LA SECCIÓN -->
            <section class="encabezado-seccion-admin">
                <div>
                    <span class="seccion-etiqueta">Ventas</span>
                    <h3>Administración de pedidos</h3>
                    <p>Consulta los pedidos, revisa sus productos y actualiza su estado.</p>
                </div>
            </section>


            <!-- MENSAJE -->
            <?php if ($mensajeAdmin !== ""): ?>
                <div class="mensaje-admin <?= htmlspecialchars($tipoMensajeAdmin, ENT_QUOTES, "UTF-8") ?>" role="alert">
                    <?= htmlspecialchars($mensajeAdmin, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>


            <!-- RESUMEN -->
            <section class="resumen-pedidos-admin">
                <article>
                    <span>Total de pedidos</span>
                    <strong><?= (int) $contadores["total_pedidos"] ?></strong>
                </article>
                <article>
                    <span>Pendientes</span>
                    <strong><?= (int) $contadores["pendientes"] ?></strong>
                </article>
                <article>
                    <span>Confirmados</span>
                    <strong><?= (int) $contadores["confirmados"] ?></strong>
                </article>
                <article>
                    <span>Enviados</span>
                    <strong><?= (int) $contadores["enviados"] ?></strong>
                </article>
                <article>
                    <span>Entregados</span>
                    <strong><?= (int) $contadores["entregados"] ?></strong>
                </article>
            </section>


            <!-- BÚSQUEDA Y FILTRO -->
            <section class="barra-herramientas-admin">
                <form action="pedidos.php" method="GET" class="formulario-filtros-pedidos">
                    <input 
                        type="search" 
                        name="buscar" 
                        value="<?= htmlspecialchars($buscar, ENT_QUOTES, "UTF-8") ?>" 
                        placeholder="Buscar número, cliente, correo o dirección" 
                        maxlength="100"
                    >

                    <select name="estado" aria-label="Filtrar pedidos por estado">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estadosPermitidos as $estado): ?>
                            <option value="<?= htmlspecialchars($estado, ENT_QUOTES, "UTF-8") ?>" <?= $estadoFiltro === $estado ? "selected" : "" ?>>
                                <?= htmlspecialchars(ucfirst($estado), ENT_QUOTES, "UTF-8") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Filtrar</button>
                </form>

                <?php if ($buscar !== "" || $estadoFiltro !== ""): ?>
                    <a href="pedidos.php" class="limpiar-admin">Limpiar filtros</a>
                <?php endif; ?>
            </section>


            <!-- RESULTADO DE FILTROS -->
            <?php if ($buscar !== "" || $estadoFiltro !== ""): ?>
                <div class="mensaje-resultados-admin">
                    Se encontraron <strong><?= $cantidadPedidos ?></strong> <?= $cantidadPedidos === 1 ? "pedido" : "pedidos" ?> con los filtros seleccionados.
                </div>
            <?php endif; ?>


            <!-- TABLA DE RESULTADOS -->
            <section class="contenedor-tabla-admin">
                <?php if ($cantidadPedidos > 0): ?>
                    <div class="tabla-responsive">
                        <table class="tabla-admin tabla-pedidos-admin">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Dirección</th>
                                    <th>Productos</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pedido = $resultadoPedidos->fetch_assoc()): ?>
                                    <?php
                                    $estadoPedido = strtolower(trim($pedido["estado"] ?? ""));
                                    $claseEstado  = in_array($estadoPedido, $estadosPermitidos, true) ? $estadoPedido : "otro";

                                    $fechaPedido     = $pedido["fecha_pedido"] ?? "";
                                    $marcaTiempo     = strtotime($fechaPedido);
                                    $fechaFormateada = ($marcaTiempo !== false) ? date("d/m/Y H:i", $marcaTiempo) : "Sin fecha";

                                    $totalUnidades       = (int) $pedido["total_unidades"];
                                    $productosDiferentes = (int) $pedido["productos_diferentes"];
                                    ?>
                                    <tr>
                                        <!-- PEDIDO -->
                                        <td>
                                            <strong class="numero-pedido-admin">
                                                #<?= (int) $pedido["id_pedido"] ?>
                                            </strong>
                                        </td>

                                        <!-- FECHA -->
                                        <td>
                                            <span class="fecha-pedido-admin">
                                                <?= htmlspecialchars($fechaFormateada, ENT_QUOTES, "UTF-8") ?>
                                            </span>
                                        </td>

                                        <!-- CLIENTE -->
                                        <td>
                                            <strong class="texto-tabla-principal">
                                                <?= htmlspecialchars($pedido["nombre_usuario"], ENT_QUOTES, "UTF-8") ?>
                                            </strong>
                                            <span class="texto-tabla-secundario">
                                                <?= htmlspecialchars($pedido["correo"], ENT_QUOTES, "UTF-8") ?>
                                            </span>
                                        </td>

                                        <!-- DIRECCIÓN -->
                                        <td>
                                            <span class="direccion-pedido-admin">
                                                <?= htmlspecialchars($pedido["direccion"], ENT_QUOTES, "UTF-8") ?>
                                            </span>
                                        </td>

                                        <!-- PRODUCTOS -->
                                        <td>
                                            <strong class="texto-tabla-principal">
                                                <?= $totalUnidades ?> <?= $totalUnidades === 1 ? "unidad" : "unidades" ?>
                                            </strong>
                                            <span class="texto-tabla-secundario">
                                                <?= $productosDiferentes ?> <?= $productosDiferentes === 1 ? "producto diferente" : "productos diferentes" ?>
                                            </span>
                                        </td>

                                        <!-- TOTAL -->
                                        <td>
                                            <strong class="precio-actual-admin">
                                                $<?= number_format((float) $pedido["total"], 2) ?> MXN
                                            </strong>
                                        </td>

                                        <!-- ESTADO -->
                                        <td>
                                            <span class="estado-pedido-admin <?= htmlspecialchars($claseEstado, ENT_QUOTES, "UTF-8") ?>">
                                                <?= htmlspecialchars(ucfirst($estadoPedido), ENT_QUOTES, "UTF-8") ?>
                                            </span>
                                        </td>

                                        <!-- ACCIÓN -->
                                        <td>
                                            <a href="pedido_detalle.php?id=<?= (int) $pedido["id_pedido"] ?>" class="boton-ver-pedido">
                                                Ver detalle
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="admin-sin-resultados">
                        <div class="admin-sin-resultados-icono">📦</div>
                        <h3>No se encontraron pedidos</h3>
                        <?php if ($buscar !== "" || $estadoFiltro !== ""): ?>
                            <p>No existen pedidos relacionados con los filtros seleccionados.</p>
                            <a href="pedidos.php" class="boton-admin-principal">Mostrar todos</a>
                        <?php else: ?>
                            <p>Todavía no existen pedidos registrados en el sistema.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

        </main>
    </div>

</body>
</html>

<?php
$resultadoPedidos->free();
$consultaPedidos->close();
$conexion->close();
?>