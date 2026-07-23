

        /*
        =========================================================
        1. GALERÍA DE IMÁGENES
        =========================================================
        */

        const imagenPrincipal =
            document.getElementById("producto-imagen");

        const miniaturas =
            document.querySelectorAll(".miniatura-producto");

        miniaturas.forEach(function (miniatura) {

            miniatura.addEventListener("click", function () {

                if (!imagenPrincipal) {
                    return;
                }

                imagenPrincipal.src =
                    miniatura.dataset.ruta;

                miniaturas.forEach(function (otraMiniatura) {

                    otraMiniatura.classList.remove(
                        "selected-image"
                    );

                });

                miniatura.classList.add(
                    "selected-image"
                );

            });

        });


        /*
        =========================================================
        2. ELEMENTOS DE TALLA, CANTIDAD Y CARRITO
        =========================================================
        */

        const botonesTalla =
            document.querySelectorAll(".size-button");

        const inventario =
            document.getElementById("producto-inventario");

        const botonRestar =
            document.getElementById("restar-cantidad");

        const botonSumar =
            document.getElementById("sumar-cantidad");

        const cantidadTexto =
            document.getElementById("cantidad-producto");

        const formularioCarrito =
            document.getElementById("formulario-carrito");

        const campoTalla =
            document.getElementById("talla-seleccionada");

        const campoCantidad =
            document.getElementById("cantidad-seleccionada");

        const botonFavoritos =
            document.getElementById("agregar-favoritos");


        /*
        =========================================================
        3. VARIABLES DE CONTROL
        =========================================================
        */

        let tallaSeleccionada = "";
        let stockSeleccionado = 0;
        let cantidad = 1;


        /*
        =========================================================
        4. SELECCIONAR TALLA
        =========================================================
        */

        botonesTalla.forEach(function (boton) {

            boton.addEventListener("click", function () {

                botonesTalla.forEach(function (otroBoton) {

                    otroBoton.classList.remove(
                        "selected"
                    );

                });

                boton.classList.add("selected");

                tallaSeleccionada =
                    boton.dataset.talla;

                stockSeleccionado =
                    Number(boton.dataset.stock);

                cantidad = 1;

                campoTalla.value =
                    tallaSeleccionada;

                campoCantidad.value =
                    cantidad;

                cantidadTexto.textContent =
                    cantidad;

                inventario.textContent =
                    stockSeleccionado +
                    " piezas disponibles en talla " +
                    tallaSeleccionada;

            });

        });


        /*
        =========================================================
        5. DISMINUIR CANTIDAD
        =========================================================
        */

        botonRestar.addEventListener(
            "click",
            function () {

                if (cantidad > 1) {

                    cantidad--;

                    cantidadTexto.textContent =
                        cantidad;

                    campoCantidad.value =
                        cantidad;
                }

            }
        );


        /*
        =========================================================
        6. AUMENTAR CANTIDAD
        =========================================================
        */

        botonSumar.addEventListener(
            "click",
            function () {

                if (tallaSeleccionada === "") {

                    alert(
                        "Selecciona una talla primero."
                    );

                    return;
                }

                if (cantidad < stockSeleccionado) {

                    cantidad++;

                    cantidadTexto.textContent =
                        cantidad;

                    campoCantidad.value =
                        cantidad;

                } else {

                    alert(
                        "No hay más piezas disponibles en esa talla."
                    );
                }

            }
        );


        /*
        =========================================================
        7. VALIDAR EL ENVÍO AL CARRITO
        =========================================================
        */

        formularioCarrito.addEventListener(
            "submit",
            function (evento) {

                if (tallaSeleccionada === "") {

                    evento.preventDefault();

                    alert(
                        "Selecciona una talla antes de agregar el producto."
                    );

                    return;
                }

                campoTalla.value =
                    tallaSeleccionada;

                campoCantidad.value =
                    cantidad;

            }
        );


     
      

