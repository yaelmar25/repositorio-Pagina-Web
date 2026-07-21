<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de compras | Legacy Jerseys</title>

    <link rel="stylesheet" href="CSS/estilos.css">

    <!-- Estilos específicos para arreglar la visualización del panel de productos -->
    <style>
        .cart-layout {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }

        .cart-panel {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
            /* Permite desplazamiento si el contenido es muy ancho en pantallas chicas */
        }

        .cart-head,
        .cart-row {
            display: grid;
            grid-template-columns: 2.5fr 1fr 1.5fr 1.2fr 1.2fr 1fr;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            min-width: 650px;
            /* Garantiza que las columnas mantengan su forma y no se aplasten */
        }

        .cart-head {
            font-weight: bold;
            color: #555;
            border-bottom: 2px solid #ddd;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .product-image {
            width: 60px;
            height: auto;
            object-fit: contain;
        }

        .qty-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .qty-control button {
            width: 25px;
            height: 25px;
            cursor: pointer;
        }

        .size-badge {
            display: inline-block;
            padding: 4px 8px;
            border: 1px solid #1a46a0;
            color: #1a46a0;
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
            width: fit-content;
        }

        .remove-button {
            background: none;
            border: none;
            color: #cc0000;
            cursor: pointer;
            font-weight: bold;
        }

        .remove-button:hover {
            text-decoration: underline;
        }
    </style>
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

            <a href="inicio_de_sesion.php">
                Inicio de sesión
            </a>
            <a href="Carrito de compras.php">
                🛒 Carrito
            </a>

        </div>

    </header>

    <!-- ================= MENU ================= -->

    <nav>

        <a href="index.php">Inicio</a>

        <a href="ofertas.php">Ofertas</a>

        <a href="catalogo.php">Catálogo</a>

    </nav>






    <main class="cart-page">



        <section class="cart-title" style="max-width: 1200px; margin: 20px auto 0; padding: 0 20px;">

            <h1>
                Carrito de compras
            </h1>


            <p>
                <span id="item-total">
                    3
                </span>
                productos en tu carrito
            </p>


        </section>







        <section class="cart-layout">





            <div class="cart-panel">



                <div class="cart-head">

                    <span>
                        Producto
                    </span>

                    <span>
                        Talla
                    </span>

                    <span>
                        Cantidad
                    </span>

                    <span>
                        Precio unitario
                    </span>

                    <span>
                        Subtotal
                    </span>

                    <span>
                        Acciones
                    </span>

                </div>









                <!-- REAL MADRID -->

                <article class="cart-row" data-price="24.99">


                    <div class="product-info">


                        <img
                            class="product-image"
                            src="pictures/madrid.jpg"
                            alt="Jersey Real Madrid">



                        <div>

                            <h2 style="font-size: 1rem; margin: 0 0 5px 0;">
                                Jersey Real Madrid
                            </h2>


                            <p style="font-size: 0.85rem; margin: 0; color: #666;">
                                Camisa oficial de fútbol temporada 2025.
                            </p>


                        </div>


                    </div>





                    <span class="size-badge">
                        M
                    </span>





                    <div class="qty-control">


                        <button type="button" class="qty-minus">
                            −
                        </button>


                        <span class="qty-value">
                            1
                        </span>


                        <button type="button" class="qty-plus">
                            +
                        </button>


                    </div>





                    <strong class="unit-price">
                        $24.99
                    </strong>



                    <strong class="line-subtotal">
                        $24.99
                    </strong>





                    <button type="button" class="remove-button">
                        Eliminar
                    </button>



                </article>









                <!-- BARCELONA -->

                <article class="cart-row" data-price="24.99">


                    <div class="product-info">


                        <img
                            class="product-image"
                            src="pictures/barsa.jpg"
                            alt="Jersey Barcelona">



                        <div>

                            <h2 style="font-size: 1rem; margin: 0 0 5px 0;">
                                Jersey Barcelona
                            </h2>


                            <p style="font-size: 0.85rem; margin: 0; color: #666;">
                                Jersey deportivo cómodo para partidos y entrenamiento.
                            </p>


                        </div>


                    </div>





                    <span class="size-badge">
                        L
                    </span>





                    <div class="qty-control">


                        <button type="button" class="qty-minus">
                            −
                        </button>


                        <span class="qty-value">
                            2
                        </span>


                        <button type="button" class="qty-plus">
                            +
                        </button>


                    </div>





                    <strong class="unit-price">
                        $24.99
                    </strong>



                    <strong class="line-subtotal">
                        $49.98
                    </strong>





                    <button type="button" class="remove-button">
                        Eliminar
                    </button>



                </article>









                <!-- MÉXICO -->

                <article class="cart-row" data-price="24.99">


                    <div class="product-info">


                        <img
                            class="product-image"
                            src="pictures/mexico.jpg"
                            alt="Jersey México">



                        <div>

                            <h2 style="font-size: 1rem; margin: 0 0 5px 0;">
                                Jersey México
                            </h2>


                            <p style="font-size: 0.85rem; margin: 0; color: #666;">
                                Camisa ligera con diseño deportivo moderno.
                            </p>


                        </div>


                    </div>





                    <span class="size-badge">
                        S
                    </span>





                    <div class="qty-control">


                        <button type="button" class="qty-minus">
                            −
                        </button>


                        <span class="qty-value">
                            1
                        </span>


                        <button type="button" class="qty-plus">
                            +
                        </button>


                    </div>





                    <strong class="unit-price">
                        $24.99
                    </strong>



                    <strong class="line-subtotal">
                        $24.99
                    </strong>





                    <button type="button" class="remove-button">
                        Eliminar
                    </button>



                </article>








                <div class="secure-note" style="display: flex; align-items: center; gap: 10px; margin-top: 20px; color: #1a46a0;">


                    <span>
                        ♡
                    </span>


                    <div>

                        <strong>
                            Pagos seguros y protegidos
                        </strong>


                        <p style="margin: 0; font-size: 0.85rem; color: #666;">
                            Tus datos están 100% protegidos con encriptación SSL.
                        </p>


                    </div>


                </div>



            </div>









            <aside class="summary-card" style="width: 320px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); box-sizing: border-box;">


                <h2>
                    Resumen del pedido
                </h2>





                <div class="summary-line" style="display: flex; justify-content: space-between; margin-bottom: 10px;">


                    <span>
                        Subtotal (<span id="summary-products">3</span> productos)
                    </span>


                    <strong id="summary-subtotal">
                        $99.96
                    </strong>


                </div>






                <div class="summary-line" style="display: flex; justify-content: space-between; margin-bottom: 15px;">


                    <span>
                        Envío
                    </span>


                    <strong class="free-shipping" style="color: green;">
                        Gratis
                    </strong>


                </div>






                <div class="summary-total" style="display: flex; justify-content: space-between; font-size: 1.2rem; border-top: 1px solid #eee; padding-top: 15px; margin-bottom: 20px;">


                    <span>
                        Total de compra
                    </span>


                    <strong id="summary-total">
                        $99.96
                    </strong>


                </div>






                <div style="display: flex; flex-direction: column; gap: 12px;">

                    <a href="Modulo de pago.php" class="primary-button" style="display: block; text-align: center; width: 100%; box-sizing: border-box; background: #1a46a0; color: white; padding: 12px; border-radius: 5px; text-decoration: none; font-weight: bold;">
                        🛒 Continuar al pago
                    </a>

                    <a href="catalogo.php" class="secondary-button" style="display: block; text-align: center; width: 100%; box-sizing: border-box; background: #f4f4f4; color: #333; padding: 12px; border-radius: 5px; text-decoration: none; font-weight: bold; border: 1px solid #ccc;">
                        Seguir comprando
                    </a>

                </div>





            </aside>







        </section>



    </main>







    <script src="script/script.js"></script>


</body>

</html>