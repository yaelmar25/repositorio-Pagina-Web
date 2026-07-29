<?php

require_once __DIR__ . "/../config/sesion.php";

requerirAdministrador(
    "../inicio_de_sesion.php",
    "../index.php"
);

$usuarioActual = obtenerUsuarioActual();


if (!isset($_SESSION["csrf_admin"]) || !is_string($_SESSION["csrf_admin"])) {
    $_SESSION["csrf_admin"] = bin2hex(random_bytes(32));
}

$tokenCsrf = $_SESSION["csrf_admin"];


$mensaje = $_SESSION["mensaje_admin"] ?? "";
$tipoMensaje = $_SESSION["tipo_mensaje_admin"] ?? "";
$datos = $_SESSION["datos_producto"] ?? [];

unset(
    $_SESSION["mensaje_admin"],
    $_SESSION["tipo_mensaje_admin"],
    $_SESSION["datos_producto"]
);

$stocksAnteriores = $datos["stock"] ?? [];

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar producto | Legacy Jerseys</title>
    <link rel="stylesheet" href="../CSS/admin.css?v=6">
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

            <!-- ENCABEZADO -->
            <header class="encabezado-admin">
                <div>
                    <p class="encabezado-etiqueta">Administración</p>
                    <h2>Agregar producto</h2>
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
                            <?= htmlspecialchars($usuarioActual["nombre"] ?? "Administrador", ENT_QUOTES, "UTF-8") ?>
                        </strong>
                        <span>Administrador</span>
                    </div>
                </div>
            </header>

            <!-- TÍTULO DE LA SECCIÓN -->
            <section class="encabezado-seccion-admin">
                <div>
                    <span class="seccion-etiqueta">Catálogo</span>
                    <h3>Registrar un nuevo jersey</h3>
                    <p>Agrega la información, imagen y existencias del producto.</p>
                </div>
                <a href="productos.php" class="boton-admin-secundario">
                    ← Volver a productos
                </a>
            </section>

            <!-- MENSAJE -->
            <?php if ($mensaje !== ""): ?>
                <div class="mensaje-admin <?= htmlspecialchars($tipoMensaje, ENT_QUOTES, "UTF-8") ?>" role="alert">
                    <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <!-- FORMULARIO -->
            <section class="formulario-admin-contenedor">

                <form action="../procesos/admin_producto_acciones.php" method="POST" enctype="multipart/form-data" class="formulario-admin">

                    <input type="hidden" name="accion" value="crear">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($tokenCsrf, ENT_QUOTES, "UTF-8") ?>">

                    <div class="formulario-admin-seccion">

                        <div class="formulario-admin-titulo">
                            <span>1</span>
                            <div>
                                <h3>Información del producto</h3>
                                <p>Datos que se mostrarán dentro de la tienda.</p>
                            </div>
                        </div>

                        <div class="formulario-admin-grid">

                            <div class="campo-admin campo-completo">
                                <label for="nombre">Nombre del producto</label>
                                <input 
                                    type="text" 
                                    name="nombre" 
                                    id="nombre" 
                                    value="<?= htmlspecialchars($datos["nombre"] ?? "", ENT_QUOTES, "UTF-8") ?>" 
                                    placeholder="Ejemplo: Barcelona Local 2025" 
                                    minlength="3" 
                                    maxlength="150" 
                                    required
                                >
                            </div>

                            <div class="campo-admin">
                                <label for="equipo">Equipo o selección</label>
                                <input 
                                    type="text" 
                                    name="equipo" 
                                    id="equipo" 
                                    value="<?= htmlspecialchars($datos["equipo"] ?? "", ENT_QUOTES, "UTF-8") ?>" 
                                    placeholder="Ejemplo: FC Barcelona" 
                                    maxlength="100" 
                                    required
                                >
                            </div>

                            <div class="campo-admin">
                                <label for="modelo">Modelo o temporada</label>
                                <input 
                                    type="text" 
                                    name="modelo" 
                                    id="modelo" 
                                    value="<?= htmlspecialchars($datos["modelo"] ?? "", ENT_QUOTES, "UTF-8") ?>" 
                                    placeholder="Ejemplo: Local 2025/26" 
                                    maxlength="150" 
                                    required
                                >
                            </div>

                            <div class="campo-admin campo-completo">
                                <label for="descripcion">Descripción</label>
                                <textarea 
                                    name="descripcion" 
                                    id="descripcion" 
                                    rows="5" 
                                    maxlength="1000" 
                                    placeholder="Describe las características principales del jersey..." 
                                    required
                                ><?= htmlspecialchars($datos["descripcion"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>
                            </div>

                        </div>

                    </div>

                    <div class="formulario-admin-seccion">

                        <div class="formulario-admin-titulo">
                            <span>2</span>
                            <div>
                                <h3>Precio y descuento</h3>
                                <p>El sistema calculará automáticamente el precio final.</p>
                            </div>
                        </div>

                        <div class="formulario-admin-grid">

                            <div class="campo-admin">
                                <label for="precio">Precio normal</label>
                                <div class="campo-con-prefijo">
                                    <span>$</span>
                                    <input 
                                        type="number" 
                                        name="precio" 
                                        id="precio" 
                                        value="<?= htmlspecialchars($datos["precio"] ?? "", ENT_QUOTES, "UTF-8") ?>" 
                                        min="0.01" 
                                        step="0.01" 
                                        placeholder="1299.00" 
                                        required
                                    >
                                </div>
                            </div>

                            <div class="campo-admin">
                                <label for="descuento">Descuento</label>
                                <div class="campo-con-sufijo">
                                    <input 
                                        type="number" 
                                        name="descuento" 
                                        id="descuento" 
                                        value="<?= htmlspecialchars($datos["descuento"] ?? "0", ENT_QUOTES, "UTF-8") ?>" 
                                        min="0" 
                                        max="100" 
                                        step="0.01" 
                                        required
                                    >
                                    <span>%</span>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="formulario-admin-seccion">

                        <div class="formulario-admin-titulo">
                            <span>3</span>
                            <div>
                                <h3>Imagen del producto</h3>
                                <p>Selecciona una imagen desde tu computadora.</p>
                            </div>
                        </div>

                        <div class="carga-imagen-contenedor">

                            <div class="campo-admin">
                                <label for="imagen">Archivo de imagen</label>
                                
                                <label for="imagen" class="selector-imagen-admin">
                                    <span class="selector-imagen-icono">↑</span>
                                    <span class="selector-imagen-texto">
                                        <strong>Seleccionar imagen</strong>
                                        <small>JPG, JPEG, PNG o WebP. Máximo 5 MB.</small>
                                    </span>
                                </label>

                                <input 
                                    type="file" 
                                    name="imagen" 
                                    id="imagen" 
                                    class="input-imagen-admin" 
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" 
                                    required
                                >

                                <p class="nombre-imagen-admin" id="nombre-imagen">
                                    Ningún archivo seleccionado
                                </p>

                                <small>
                                    Por seguridad, debes volver a seleccionar la imagen si el formulario presenta un error.
                                </small>
                            </div>

                            <div class="vista-previa-admin" id="contenedor-vista-previa">
                                <span id="texto-vista-previa">Vista previa de la imagen</span>
                                <img src="" alt="Vista previa del producto" id="vista-previa-imagen" hidden>
                            </div>

                        </div>

                    </div>

                    <div class="formulario-admin-seccion">

                        <div class="formulario-admin-titulo">
                            <span>4</span>
                            <div>
                                <h3>Existencias por talla</h3>
                                <p>Deja vacío el campo de una talla que no manejará el producto.</p>
                            </div>
                        </div>

                        <div class="grupo-stock-admin">
                            <?php $tallas = ["XS", "S", "M", "L", "XL", "XXL"]; ?>

                            <?php foreach ($tallas as $talla): ?>
                                <div class="campo-stock-admin">
                                    <label for="stock-<?= $talla ?>"><?= $talla ?></label>
                                    <input 
                                        type="number" 
                                        name="stock[<?= $talla ?>]" 
                                        id="stock-<?= $talla ?>" 
                                        value="<?= htmlspecialchars((string) ($stocksAnteriores[$talla] ?? ""), ENT_QUOTES, "UTF-8") ?>" 
                                        min="0" 
                                        step="1" 
                                        placeholder="0"
                                    >
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>

                    <div class="acciones-formulario-admin">
                        <a href="productos.php" class="boton-cancelar-admin">
                            Cancelar
                        </a>
                        <button type="submit" class="boton-guardar-admin">
                            Guardar producto
                        </button>
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
                nombreImagen.textContent = "Ningún archivo seleccionado";
                vistaPrevia.hidden = true;
                vistaPrevia.src = "";
                textoVistaPrevia.hidden = false;
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