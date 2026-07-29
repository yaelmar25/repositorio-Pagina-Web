<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

requerirAdministrador(
    "../inicio_de_sesion.php",
    "../index.php"
);

$usuarioActual = obtenerUsuarioActual();


$idProducto = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$idProducto || $idProducto <= 0) {
    header("Location: productos.php");
    exit;
}


$consultaProducto = $conexion->prepare(
    "SELECT
        p.id_producto,
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
    WHERE p.id_producto = ?
    LIMIT 1"
);

if (!$consultaProducto) {
    die("Error al preparar la consulta del producto: " . $conexion->error);
}

$consultaProducto->bind_param("i", $idProducto);
$consultaProducto->execute();

$resultadoProducto = $consultaProducto->get_result();
$producto = $resultadoProducto->fetch_assoc();
$consultaProducto->close();

if (!$producto) {
    $_SESSION["mensaje_admin"] = "El producto solicitado no existe.";
    $_SESSION["tipo_mensaje_admin"] = "error";

    header("Location: productos.php");
    exit;
}


$stocksActuales = [];

$consultaStocks = $conexion->prepare(
    "SELECT talla, stock
     FROM producto_tallas
     WHERE id_producto = ?"
);

if (!$consultaStocks) {
    die("Error al consultar las existencias: " . $conexion->error);
}

$consultaStocks->bind_param("i", $idProducto);
$consultaStocks->execute();

$resultadoStocks = $consultaStocks->get_result();

while ($filaStock = $resultadoStocks->fetch_assoc()) {
    $stocksActuales[$filaStock["talla"]] = (int) $filaStock["stock"];
}

$resultadoStocks->free();
$consultaStocks->close();


if (!isset($_SESSION["csrf_admin"]) || !is_string($_SESSION["csrf_admin"])) {
    $_SESSION["csrf_admin"] = bin2hex(random_bytes(32));
}

$tokenCsrf = $_SESSION["csrf_admin"];


$mensaje = $_SESSION["mensaje_admin"] ?? "";
$tipoMensaje = $_SESSION["tipo_mensaje_admin"] ?? "";
$datosAnteriores = $_SESSION["datos_producto_editar"] ?? [];

unset(
    $_SESSION["mensaje_admin"],
    $_SESSION["tipo_mensaje_admin"],
    $_SESSION["datos_producto_editar"]
);

/*
 * Recuperar los datos escritos si hubo un error.
 */
if (
    isset($datosAnteriores["id_producto"]) &&
    (int) $datosAnteriores["id_producto"] === $idProducto
) {
    $producto = array_merge($producto, $datosAnteriores);

    if (isset($datosAnteriores["stock"]) && is_array($datosAnteriores["stock"])) {
        $stocksActuales = $datosAnteriores["stock"];
    }
}

$imagenActual = $producto["imagen"] ?? "";

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto | Legacy Jerseys</title>
    <link rel="stylesheet" href="../CSS/admin.css?v=7">
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
                <a href="productos.php" class="enlace-admin activo">
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
                    <h2>Editar producto</h2>
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
                    <span class="seccion-etiqueta">Catálogo</span>
                    <h3>Modificar jersey</h3>
                    <p>Actualiza la información, imagen y existencias del producto.</p>
                </div>

                <a href="productos.php" class="boton-admin-secundario">
                    ← Volver a productos
                </a>
            </section>


            <?php if ($mensaje !== ""): ?>
                <div class="mensaje-admin <?= htmlspecialchars($tipoMensaje, ENT_QUOTES, "UTF-8") ?>" role="alert">
                    <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>


            <section class="formulario-admin-contenedor">

                <form action="../procesos/admin_producto_acciones.php" method="POST" enctype="multipart/form-data" class="formulario-admin">

                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_producto" value="<?= $idProducto ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, "UTF-8") ?>">

                    <!-- INFORMACIÓN GENERAL -->
                    <div class="formulario-admin-seccion">
                        <div class="formulario-admin-titulo">
                            <span>1</span>
                            <div>
                                <h3>Información del producto</h3>
                                <p>Modifica los datos mostrados en la tienda.</p>
                            </div>
                        </div>

                        <div class="formulario-admin-grid">
                            <div class="campo-admin campo-completo">
                                <label for="nombre">Nombre del producto</label>
                                <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($producto["nombre"] ?? "", ENT_QUOTES, "UTF-8") ?>" minlength="3" maxlength="150" required>
                            </div>

                            <div class="campo-admin">
                                <label for="equipo">Equipo o selección</label>
                                <input type="text" name="equipo" id="equipo" value="<?= htmlspecialchars($producto["equipo"] ?? "", ENT_QUOTES, "UTF-8") ?>" maxlength="100" required>
                            </div>

                            <div class="campo-admin">
                                <label for="modelo">Modelo o temporada</label>
                                <input type="text" name="modelo" id="modelo" value="<?= htmlspecialchars($producto["modelo"] ?? "", ENT_QUOTES, "UTF-8") ?>" maxlength="150" required>
                            </div>

                            <div class="campo-admin campo-completo">
                                <label for="descripcion">Descripción</label>
                                <textarea name="descripcion" id="descripcion" rows="5" maxlength="1000" required><?= htmlspecialchars($producto["descripcion"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>
                            </div>
                        </div>
                    </div>


                    <!-- PRECIO Y DESCUENTO -->
                    <div class="formulario-admin-seccion">
                        <div class="formulario-admin-titulo">
                            <span>2</span>
                            <div>
                                <h3>Precio y descuento</h3>
                                <p>Actualiza el precio normal y la promoción.</p>
                            </div>
                        </div>

                        <div class="formulario-admin-grid">
                            <div class="campo-admin">
                                <label for="precio">Precio normal</label>
                                <div class="campo-con-prefijo">
                                    <span>$</span>
                                    <input type="number" name="precio" id="precio" value="<?= htmlspecialchars((string) ($producto["precio"] ?? ""), ENT_QUOTES, "UTF-8") ?>" min="0.01" step="0.01" required>
                                </div>
                            </div>

                            <div class="campo-admin">
                                <label for="descuento">Descuento</label>
                                <div class="campo-con-sufijo">
                                    <input type="number" name="descuento" id="descuento" value="<?= htmlspecialchars((string) ($producto["descuento"] ?? "0"), ENT_QUOTES, "UTF-8") ?>" min="0" max="100" step="0.01" required>
                                    <span>%</span>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- IMAGEN -->
                    <div class="formulario-admin-seccion">
                        <div class="formulario-admin-titulo">
                            <span>3</span>
                            <div>
                                <h3>Imagen del producto</h3>
                                <p>Selecciona otra imagen solamente si deseas reemplazar la actual.</p>
                            </div>
                        </div>

                        <div class="carga-imagen-contenedor">
                            <div class="campo-admin">
                                <label for="imagen">Nueva imagen</label>
                                <label for="imagen" class="selector-imagen-admin">
                                    <span class="selector-imagen-icono">↑</span>
                                    <span class="selector-imagen-texto">
                                        <strong>Seleccionar nueva imagen</strong>
                                        <small>JPG, PNG o WebP. Máximo 5 MB.</small>
                                    </span>
                                </label>
                                <input type="file" name="imagen" id="imagen" class="input-imagen-admin" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                <p class="nombre-imagen-admin" id="nombre-imagen">
                                    Se conservará la imagen actual
                                </p>
                            </div>

                            <div class="vista-previa-admin" id="contenedor-vista-previa">
                                <?php if ($imagenActual !== ""): ?>
                                    <img src="../<?= htmlspecialchars($imagenActual, ENT_QUOTES, "UTF-8") ?>" alt="Imagen actual del producto" id="vista-previa-imagen">
                                    <span id="texto-vista-previa" hidden>Vista previa</span>
                                <?php else: ?>
                                    <span id="texto-vista-previa">El producto no tiene una imagen registrada</span>
                                    <img src="" alt="Vista previa del producto" id="vista-previa-imagen" hidden>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>


                    <!-- EXISTENCIAS -->
                    <div class="formulario-admin-seccion">
                        <div class="formulario-admin-titulo">
                            <span>4</span>
                            <div>
                                <h3>Existencias por talla</h3>
                                <p>Deja vacío el campo de una talla que ya no quieras manejar.</p>
                            </div>
                        </div>

                        <div class="grupo-stock-admin">
                            <?php $tallas = ["XS", "S", "M", "L", "XL", "XXL"]; ?>
                            <?php foreach ($tallas as $talla): ?>
                                <div class="campo-stock-admin">
                                    <label for="stock-<?= $talla ?>"><?= $talla ?></label>
                                    <input type="number" name="stock[<?= $talla ?>]" id="stock-<?= $talla ?>" value="<?= htmlspecialchars(isset($stocksActuales[$talla]) ? (string) $stocksActuales[$talla] : "", ENT_QUOTES, "UTF-8") ?>" min="0" step="1" placeholder="0">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>


                    <div class="acciones-formulario-admin">
                        <a href="productos.php" class="boton-cancelar-admin">Cancelar</a>
                        <button type="submit" class="boton-guardar-admin">Guardar cambios</button>
                    </div>

                </form>

            </section>

        </main>

    </div>


    <script>
        const inputImagen = document.getElementById("imagen");
        const nombreImagen = document.getElementById("nombre-imagen");
        const vistaPrevia = document.getElementById("vista-previa-imagen");
        const textoVistaPrevia = document.getElementById("texto-vista-previa");

        let urlTemporal = null;

        inputImagen.addEventListener("change", function () {
            const archivo = this.files[0];

            if (urlTemporal !== null) {
                URL.revokeObjectURL(urlTemporal);
                urlTemporal = null;
            }

            if (!archivo) {
                nombreImagen.textContent = "Se conservará la imagen actual";
                return;
            }

            nombreImagen.textContent = archivo.name;
            urlTemporal = URL.createObjectURL(archivo);
            vistaPrevia.src = urlTemporal;
            vistaPrevia.hidden = false;
            textoVistaPrevia.hidden = true;
        });
    </script>

</body>

</html>

<?php
$conexion->close();
?>