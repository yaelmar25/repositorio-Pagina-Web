<?php

require_once __DIR__ . "/../config/sesion.php";


/* =========================================================
   DATOS DEL ENCABEZADO
========================================================= */

$usuarioEncabezado = obtenerUsuarioActual();

$nombreUsuarioEncabezado =
    obtenerPrimerNombreUsuario();

$cantidadCarritoEncabezado =
    obtenerCantidadCarrito();

$paginaActual = basename(
    $_SERVER["PHP_SELF"] ?? ""
);

?>

<!-- ================= HEADER ================= -->

<header>

    <!-- LOGOTIPO -->

    <div class="logo">

        <a href="index.php">
            LEGACY JERSEYS
        </a>

    </div>


    <!-- BUSCADOR -->

    <div class="buscador">

        <input
            type="search"
            id="buscador-jerseys"
            placeholder="🔍 Buscar jerseys"
            aria-label="Buscar jerseys"
        >

    </div>


    <!-- SESIÓN Y CARRITO -->

    <div class="acciones">

        <?php if ($usuarioEncabezado !== null): ?>

            <!-- USUARIO AUTENTICADO -->

            <div class="sesion-usuario">

                <span
                    class="icono-usuario"
                    aria-hidden="true"
                >
                    👤
                </span>

                <span class="saludo-usuario">

                    Hola,

                    <strong>
                        <?= htmlspecialchars(
                            $nombreUsuarioEncabezado,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>

                </span>

            </div>


            <!-- CERRAR SESIÓN -->

            <form
                action="procesos/usuario_acciones.php"
                method="POST"
                class="formulario-cerrar-sesion"
            >

                <input
                    type="hidden"
                    name="accion"
                    value="cerrar"
                >

                <button
                    type="submit"
                    class="boton-cerrar-sesion"
                >
                    Cerrar sesión
                </button>

            </form>

        <?php else: ?>

            <!-- INICIAR SESIÓN -->

            <a
                href="inicio_de_sesion.php"
                class="boton-iniciar-sesion"
            >
                Inicio de sesión
            </a>

        <?php endif; ?>


        <!-- CARRITO -->

        <a
            href="carrito_de_compras.php"
            class="enlace-carrito"
        >

            <span aria-hidden="true">
                🛒
            </span>

            <span>
                Carrito
            </span>

            <?php if ($cantidadCarritoEncabezado > 0): ?>

                <span
                    class="contador-carrito"
                    aria-label="<?= (int) $cantidadCarritoEncabezado ?> productos en el carrito"
                >
                    <?= (int) $cantidadCarritoEncabezado ?>
                </span>

            <?php endif; ?>

        </a>

    </div>

</header>


<!-- ================= MENÚ ================= -->

<nav>

    <a
        href="index.php"
        class="<?= $paginaActual === "index.php"
            ? "enlace-activo"
            : "" ?>"
    >
        Inicio
    </a>

    <a
        href="ofertas.php"
        class="<?= $paginaActual === "ofertas.php"
            ? "enlace-activo"
            : "" ?>"
    >
        Ofertas
    </a>

    <a
        href="catalogo.php"
        class="<?= $paginaActual === "catalogo.php"
            ? "enlace-activo"
            : "" ?>"
    >
        Catálogo
    </a>

</nav>