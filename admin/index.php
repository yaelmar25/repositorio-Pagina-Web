<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

requerirAdministrador(
    "../inicio_de_sesion.php",
    "../index.php"
);

$usuarioActual = obtenerUsuarioActual();



function obtenerTotal(mysqli $conexion, string $sql): int
{
    $resultado = $conexion->query($sql);

    if (!$resultado) {
        return 0;
    }

    $fila = $resultado->fetch_assoc();
    $resultado->free();

    return (int) ($fila["total"] ?? 0);
}



$totalUsuarios = obtenerTotal(
    $conexion,
    "SELECT COUNT(*) AS total FROM usuarios"
);

$totalProductos = obtenerTotal(
    $conexion,
    "SELECT COUNT(*) AS total FROM productos"
);

$totalPedidos = obtenerTotal(
    $conexion,
    "SELECT COUNT(*) AS total FROM pedidos"
);

$pedidosPendientes = obtenerTotal(
    $conexion,
    "SELECT COUNT(*) AS total FROM pedidos WHERE estado = 'pendiente'"
);

$productosStockBajo = obtenerTotal(
    $conexion,
    "SELECT COUNT(*) AS total FROM producto_tallas WHERE stock BETWEEN 1 AND 3"
);

$productosAgotados = obtenerTotal(
    $conexion,
    "SELECT COUNT(*) AS total FROM producto_tallas WHERE stock = 0"
);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administrativo | Legacy Jerseys</title>
    <link rel="stylesheet" href="../CSS/admin.css?v=1">
</head>

<body>

    <div class="panel-administrativo">

        <!-- MENÚ LATERAL -->
        <aside class="menu-lateral">
            <div class="marca-admin">
                <span class="marca-icono">LJ</span>
                <div>
                    <h1>Legacy Jerseys</h1>
                    <p>Administración</p>
                </div>
            </div>

            <nav class="navegacion-admin">
                <a href="index.php" class="enlace-admin activo">
                    <span>⌂</span> Panel principal
                </a>
                <a href="productos.php" class="enlace-admin">
                    <span>▣</span> Productos
                </a>
                <a href="inventario.php" class="enlace-admin">
                    <span>▤</span> Inventario
                </a>
                <a href="pedidos.php" class="enlace-admin">
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

        <!-- CONTENIDO PRINCIPAL -->
        <main class="contenido-admin">

            <!-- ENCABEZADO -->
            <header class="encabezado-admin">
                <div>
                    <p class="encabezado-etiqueta">Panel administrativo</p>
                    <h2>Resumen de la tienda</h2>
                </div>

                <div class="administrador-actual">
                    <div class="administrador-avatar">
                        <?= htmlspecialchars(
                            strtoupper(substr($usuarioActual["nombre"] ?? "A", 0, 1)),
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

            <!-- BIENVENIDA -->
            <section class="bienvenida-admin">
                <div>
                    <span class="bienvenida-etiqueta">Administración</span>
                    <h2>Bienvenido al panel de Legacy Jerseys</h2>
                    <p>Consulta el estado general de la tienda y accede a las principales funciones administrativas.</p>
                </div>

                <a href="../index.php" class="boton-ver-tienda">Visitar tienda</a>
            </section>

            <!-- TARJETAS DE RESUMEN -->
            <section class="tarjetas-resumen">
                <article class="tarjeta-resumen">
                    <div class="tarjeta-icono">▣</div>
                    <div>
                        <span>Productos registrados</span>
                        <strong><?= $totalProductos ?></strong>
                    </div>
                </article>

                <article class="tarjeta-resumen">
                    <div class="tarjeta-icono">✓</div>
                    <div>
                        <span>Pedidos realizados</span>
                        <strong><?= $totalPedidos ?></strong>
                    </div>
                </article>

                <article class="tarjeta-resumen">
                    <div class="tarjeta-icono">◷</div>
                    <div>
                        <span>Pedidos pendientes</span>
                        <strong><?= $pedidosPendientes ?></strong>
                    </div>
                </article>

                <article class="tarjeta-resumen">
                    <div class="tarjeta-icono">♙</div>
                    <div>
                        <span>Usuarios registrados</span>
                        <strong><?= $totalUsuarios ?></strong>
                    </div>
                </article>
            </section>

            <!-- INFORMACIÓN INFERIOR -->
            <section class="secciones-admin">
                <article class="seccion-admin">
                    <div class="seccion-admin-encabezado">
                        <div>
                            <span class="seccion-etiqueta">Inventario</span>
                            <h3>Estado de las existencias</h3>
                        </div>
                        <a href="inventario.php">Administrar</a>
                    </div>

                    <div class="estado-inventario">
                        <div class="estado-item">
                            <span class="estado-indicador advertencia"></span>
                            <div>
                                <strong><?= $productosStockBajo ?></strong>
                                <p>Registros con stock bajo</p>
                            </div>
                        </div>

                        <div class="estado-item">
                            <span class="estado-indicador peligro"></span>
                            <div>
                                <strong><?= $productosAgotados ?></strong>
                                <p>Registros agotados</p>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="seccion-admin">
                    <div class="seccion-admin-encabezado">
                        <div>
                            <span class="seccion-etiqueta">Accesos rápidos</span>
                            <h3>Administrar tienda</h3>
                        </div>
                    </div>

                    <div class="accesos-rapidos">
                        <a href="productos.php">
                            <span>＋</span> Agregar producto
                        </a>
                        <a href="inventario.php">
                            <span>▤</span> Modificar existencias
                        </a>
                        <a href="pedidos.php">
                            <span>✓</span> Revisar pedidos
                        </a>
                    </div>
                </article>
            </section>

        </main>

    </div>

</body>
</html>

<?php
$conexion->close();
?>