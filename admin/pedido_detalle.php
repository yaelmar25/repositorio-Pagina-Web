<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

requerirAdministrador(
    "../inicio_de_sesion.php",
    "../index.php"
);

$usuarioActual = obtenerUsuarioActual();


$idPedido = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$idPedido || $idPedido <= 0) {
    header("Location: pedidos.php");
    exit;
}


if (
    !isset($_SESSION["csrf_admin"]) ||
    !is_string($_SESSION["csrf_admin"]) ||
    $_SESSION["csrf_admin"] === ""
) {
    $_SESSION["csrf_admin"] = bin2hex(random_bytes(32));
}

$tokenCsrf = $_SESSION["csrf_admin"];



$mensajeAdmin = $_SESSION["mensaje_admin"] ?? "";
$tipoMensajeAdmin = $_SESSION["tipo_mensaje_admin"] ?? "";

unset(
    $_SESSION["mensaje_admin"],
    $_SESSION["tipo_mensaje_admin"]
);



$consultaPedido = $conexion->prepare(
    "SELECT
        pe.id_pedido,
        pe.id_usuario,
        pe.direccion,
        pe.total,
        pe.estado,
        pe.fecha_pedido,
        u.nombre AS nombre_usuario,
        u.correo
     FROM pedidos pe
     INNER JOIN usuarios u
        ON u.id_usuario = pe.id_usuario
     WHERE pe.id_pedido = ?
     LIMIT 1"
);

if (!$consultaPedido) {
    die("Error al consultar el pedido: " . $conexion->error);
}

$consultaPedido->bind_param("i", $idPedido);
$consultaPedido->execute();

$resultadoPedido = $consultaPedido->get_result();
$pedido = $resultadoPedido->fetch_assoc();

$resultadoPedido->free();
$consultaPedido->close();

if (!$pedido) {
    $_SESSION["mensaje_admin"] = "El pedido solicitado no existe.";
    $_SESSION["tipo_mensaje_admin"] = "error";

    header("Location: pedidos.php");
    exit;
}


$fechaPedidoFormateada = "Sin fecha registrada";
$fechaPedido = trim((string) ($pedido["fecha_pedido"] ?? ""));

if ($fechaPedido !== "") {
    $marcaTiempoPedido = strtotime($fechaPedido);

    if ($marcaTiempoPedido !== false) {
        $fechaPedidoFormateada = date("d/m/Y H:i", $marcaTiempoPedido);
    }
}

$estadosPermitidos = [
    "pendiente",
    "confirmado",
    "enviado",
    "entregado",
    "cancelado"
];

$estadoPedido = strtolower(trim((string) ($pedido["estado"] ?? "pendiente")));

$claseEstado = in_array($estadoPedido, $estadosPermitidos, true) 
    ? $estadoPedido 
    : "otro";


$consultaDetalles = $conexion->prepare(
    "SELECT
        dp.id_detalle,
        dp.id_producto,
        dp.talla,
        dp.cantidad,
        dp.precio_unitario,
        p.nombre,
        p.equipo,
        p.modelo,
        p.slug,
        (
            SELECT ip.ruta_imagen
            FROM imagenes_producto ip
            WHERE ip.id_producto = p.id_producto
            ORDER BY ip.id_imagen
            LIMIT 1
        ) AS imagen
     FROM detalle_pedido dp
     INNER JOIN productos p
        ON p.id_producto = dp.id_producto
     WHERE dp.id_pedido = ?
     ORDER BY dp.id_detalle"
);

if (!$consultaDetalles) {
    die("Error al consultar los productos del pedido: " . $conexion->error);
}

$consultaDetalles->bind_param("i", $idPedido);
$consultaDetalles->execute();

$resultadoDetalles = $consultaDetalles->get_result();

$detallesPedido = [];
$totalUnidades = 0;
$totalCalculado = 0.0;

while ($detalle = $resultadoDetalles->fetch_assoc()) {
    $cantidad = (int) $detalle["cantidad"];
    $precioUnitario = (float) $detalle["precio_unitario"];
    $subtotal = round($precioUnitario * $cantidad, 2);

    $detalle["cantidad"] = $cantidad;
    $detalle["precio_unitario"] = $precioUnitario;
    $detalle["subtotal"] = $subtotal;

    $detallesPedido[] = $detalle;
    $totalUnidades += $cantidad;
    $totalCalculado += $subtotal;
}

$totalCalculado = round($totalCalculado, 2);

$resultadoDetalles->free();
$consultaDetalles->close();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?= $idPedido ?> | Legacy Jerseys</title>
    <link rel="stylesheet" href="../CSS/admin.css?v=11">
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
                    <h2>Pedido #<?= $idPedido ?></h2>
                </div>

                <div class="administrador-actual">
                    <div class="administrador-avatar">
                        <?= htmlspecialchars(
                            strtoupper(mb_substr($usuarioActual["nombre"] ?? "A", 0, 1)),
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

            <section class="encabezado-seccion-admin">
                <div>
                    <span class="seccion-etiqueta">Detalle del pedido</span>
                    <h3>Información de la compra</h3>
                    <p>Consulta al cliente, la fecha, la dirección, los productos y el estado del pedido.</p>
                </div>
                <a href="pedidos.php" class="boton-admin-secundario">
                    ← Volver a pedidos
                </a>
            </section>

            <?php if ($mensajeAdmin !== ""): ?>
                <div class="mensaje-admin <?= htmlspecialchars($tipoMensajeAdmin, ENT_QUOTES, "UTF-8") ?>" role="alert">
                    <?= htmlspecialchars($mensajeAdmin, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <section class="pedido-detalle-grid">

                <article class="tarjeta-detalle-pedido">
                    <span class="seccion-etiqueta">Cliente</span>
                    <h3><?= htmlspecialchars($pedido["nombre_usuario"], ENT_QUOTES, "UTF-8") ?></h3>
                    <p><?= htmlspecialchars($pedido["correo"], ENT_QUOTES, "UTF-8") ?></p>

                    <div class="dato-pedido-admin">
                        <span>Dirección de entrega</span>
                        <strong><?= htmlspecialchars($pedido["direccion"], ENT_QUOTES, "UTF-8") ?></strong>
                    </div>
                </article>

                <article class="tarjeta-detalle-pedido">
                    <span class="seccion-etiqueta">Resumen</span>

                    <div class="dato-pedido-admin">
                        <span>Número de pedido</span>
                        <strong>#<?= $idPedido ?></strong>
                    </div>

                    <div class="dato-pedido-admin">
                        <span>Fecha de compra</span>
                        <strong><?= htmlspecialchars($fechaPedidoFormateada, ENT_QUOTES, "UTF-8") ?></strong>
                    </div>

                    <div class="dato-pedido-admin">
                        <span>Productos incluidos</span>
                        <strong>
                            <?= $totalUnidades ?> <?= $totalUnidades === 1 ? "unidad" : "unidades" ?>
                        </strong>
                    </div>

                    <div class="dato-pedido-admin">
                        <span>Total de compra</span>
                        <strong class="total-pedido-destacado">
                            $<?= number_format((float) $pedido["total"], 2) ?> MXN
                        </strong>
                    </div>

                    <div class="dato-pedido-admin">
                        <span>Estado actual</span>
                        <span class="estado-pedido-admin <?= $claseEstado ?>">
                            <?= htmlspecialchars(ucfirst($estadoPedido), ENT_QUOTES, "UTF-8") ?>
                        </span>
                    </div>
                </article>

                <article class="tarjeta-detalle-pedido">
                    <span class="seccion-etiqueta">Actualizar estado</span>

                    <form action="../procesos/admin_pedido_acciones.php" method="POST" class="formulario-estado-pedido">
                        <input type="hidden" name="accion" value="actualizar_estado">
                        <input type="hidden" name="id_pedido" value="<?= $idPedido ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, "UTF-8") ?>">

                        <label for="estado">Nuevo estado</label>
                        <select name="estado" id="estado" required>
                            <?php foreach ($estadosPermitidos as $estado): ?>
                                <option value="<?= $estado ?>" <?= $estadoPedido === $estado ? "selected" : "" ?>>
                                    <?= ucfirst($estado) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit">Guardar estado</button>
                    </form>

                    <p class="nota-estado-pedido">
                        Al cancelar un pedido, las unidades regresan al inventario. Si se reactiva, vuelven a descontarse.
                    </p>
                </article>

            </section>

            <section class="contenedor-tabla-admin">

                <div class="titulo-tabla-pedido">
                    <div>
                        <span class="seccion-etiqueta">Productos</span>
                        <h3>Artículos incluidos</h3>
                    </div>
                    <strong class="total-tabla-pedido">
                        Total calculado: $<?= number_format($totalCalculado, 2) ?> MXN
                    </strong>
                </div>

                <?php if (!empty($detallesPedido)): ?>
                    <div class="tabla-responsive">
                        <table class="tabla-admin tabla-detalle-pedido">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Talla</th>
                                    <th>Cantidad</th>
                                    <th>Precio unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detallesPedido as $detalle): ?>
                                    <tr>
                                        <td>
                                            <div class="producto-tabla-admin">
                                                <?php if (!empty($detalle["imagen"])): ?>
                                                    <img src="../<?= htmlspecialchars($detalle["imagen"], ENT_QUOTES, "UTF-8") ?>" 
                                                         alt="<?= htmlspecialchars($detalle["nombre"], ENT_QUOTES, "UTF-8") ?>">
                                                <?php else: ?>
                                                    <div class="imagen-vacia-admin">Sin imagen</div>
                                                <?php endif; ?>

                                                <div>
                                                    <strong><?= htmlspecialchars($detalle["nombre"], ENT_QUOTES, "UTF-8") ?></strong>
                                                    <span>
                                                        <?= htmlspecialchars($detalle["equipo"], ENT_QUOTES, "UTF-8") ?>
                                                        <?php if (!empty($detalle["modelo"])): ?>
                                                            · <?= htmlspecialchars($detalle["modelo"], ENT_QUOTES, "UTF-8") ?>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="talla-pedido-admin">
                                                <?= htmlspecialchars($detalle["talla"], ENT_QUOTES, "UTF-8") ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= (int) $detalle["cantidad"] ?>
                                        </td>
                                        <td>
                                            $<?= number_format((float) $detalle["precio_unitario"], 2) ?> MXN
                                        </td>
                                        <td>
                                            <strong class="precio-actual-admin">
                                                $<?= number_format((float) $detalle["subtotal"], 2) ?> MXN
                                            </strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="admin-sin-resultados">
                        <div class="admin-sin-resultados-icono">📦</div>
                        <h3>El pedido no contiene productos</h3>
                        <p>No existen artículos registrados para este pedido.</p>
                    </div>
                <?php endif; ?>

            </section>

        </main>

    </div>

</body>

</html>
<?php

$conexion->close();

?>