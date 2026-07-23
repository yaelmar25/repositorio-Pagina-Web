<?php
session_start();

/* =========================================================
   OBTENER INFORMACIÓN DEL USUARIO
========================================================= */
$usuario = $_SESSION["usuario"] ?? null;
$nombreUsuario = "";

if ($usuario) {
    $nombreCompleto = trim($usuario["nombre"] ?? "");
    $partesNombre = preg_split("/\s+/", $nombreCompleto);
    $nombreUsuario = $partesNombre[0] ?? "";
}

/* =========================================================
   CALCULAR PRODUCTOS DEL CARRITO
========================================================= */
$carrito = $_SESSION["carrito"] ?? [];
$cantidadCarrito = 0;

foreach ($carrito as $item) {
    $cantidadCarrito += (int) ($item["cantidad"] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEGACY JERSEYS</title>
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
            <?php if ($usuario): ?>
                <span class="saludo-usuario">
                    Hola, <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, "UTF-8") ?>
                </span>
                <a href="cerrar_sesion.php">Cerrar sesión</a>
            <?php else: ?>
                <a href="inicio_de_sesion.php">Inicio de sesión</a>
            <?php endif; ?>

            <a href="carrito_de_compras.php" class="enlace-carrito">
                🛒 Carrito
                <?php if ($cantidadCarrito > 0): ?>
                    <span class="contador-carrito"><?= $cantidadCarrito ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <!-- ================= MENÚ ================= -->
    <nav>
        <a href="ofertas.php">Ofertas</a>
        <a href="catalogo.php">Catálogo</a>
    </nav>

    <!-- ================= BANNER ================= -->
    <section class="banner">
        <div class="texto-banner">
            <h1>LEGACY JERSEYS</h1>
            <p>Encuentra los mejores jerseys nacionales e internacionales.</p>
        </div>

        <div class="imagen-banner">
            <img src="pictures/banner.jpg" alt="Banner de Legacy Jerseys">
        </div>
    </section>

    <!-- ================= PRODUCTOS ================= -->
    <section class="destacados">
        <h2>⭐ Productos destacados</h2>

        <div class="contenedor-productos">

            <!-- MÉXICO -->
            <div class="producto">
                <img src="pictures/mexico.png" alt="Jersey de México">
                <h3>México</h3>
                <p>Tallas: S, M, L, XL</p>
                <div class="info">
                    <span class="precio">$899 MXN</span>
                    <span class="stock">Disponible</span>
                </div>
                <a href="descripcion_del_producto.php?id=mexico" class="boton-producto">Ver producto</a>
            </div>

            <!-- ESPAÑA -->
            <div class="producto">
                <img src="pictures/espana.png" alt="Jersey de España">
                <h3>España</h3>
                <p>Tallas: S, M, L, XL</p>
                <div class="info">
                    <span class="precio">$949 MXN</span>
                    <span class="stock">Disponible</span>
                </div>
                <a href="catalogo.php" class="boton-producto">Ver producto</a>
            </div>

            <!-- REAL MADRID -->
            <div class="producto">
                <img src="pictures/realmadrid.png" alt="Jersey del Real Madrid">
                <h3>Real Madrid</h3>
                <p>Tallas: S, M, L, XL</p>
                <div class="info">
                    <span class="precio">$1299 MXN</span>
                    <span class="stock">Disponible</span>
                </div>
                <a href="descripcion_del_producto.php?id=real-madrid" class="boton-producto">Ver producto</a>
            </div>

            <!-- BARCELONA -->
            <div class="producto">
                <img src="pictures/barcelona.png" alt="Jersey del FC Barcelona">
                <h3>FC Barcelona</h3>
                <p>Tallas: S, M, L, XL</p>
                <div class="info">
                    <span class="precio">$1299 MXN</span>
                    <span class="stock">Disponible</span>
                </div>
                <a href="descripcion_del_producto.php?id=barcelona" class="boton-producto">Ver producto</a>
            </div>

        </div>
    </section>

    <!-- ================= FOOTER ================= -->
    <footer>
        <div>🔒 Pago seguro</div>
        <div>🚚 Envíos a todo México</div>
        <div>✔ Productos originales</div>
        <div>📞 +52 777 447 7773</div>
        <div class="copyright">© 2026 LEGACY JERSEYS</div>
    </footer>

    <script src="script/index.js"></script>

</body>

</html>