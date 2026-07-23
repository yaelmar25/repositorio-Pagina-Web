document.addEventListener("DOMContentLoaded", function () {

    const formulariosCantidad = document.querySelectorAll(".qty-form");

    /* =====================================================
       CONFIGURAR CADA CONTROL DE CANTIDAD
    ===================================================== */
    formulariosCantidad.forEach(function (formulario) {

        const campoCantidad = formulario.querySelector(".qty-input");
        const botonRestar = formulario.querySelector(".qty-minus");
        const botonSumar = formulario.querySelector(".qty-plus");

        /*
        | Evitar errores si falta algún elemento.
        */
        if (!campoCantidad || !botonRestar || !botonSumar) {
            return;
        }

        /* =================================================
           OBTENER LÍMITES DE CANTIDAD
        ================================================= */
        function obtenerCantidadMinima() {
            const cantidadMinima = Number(campoCantidad.min);
            return Number.isNaN(cantidadMinima) ? 1 : cantidadMinima;
        }

        function obtenerCantidadMaxima() {
            const cantidadMaxima = Number(campoCantidad.max);
            return Number.isNaN(cantidadMaxima) ? Infinity : cantidadMaxima;
        }

        /* =================================================
           RESTAR CANTIDAD
        ================================================= */
        botonRestar.addEventListener("click", function () {
            const cantidadActual = Number(campoCantidad.value);
            const cantidadMinima = obtenerCantidadMinima();

            if (cantidadActual > cantidadMinima) {
                campoCantidad.value = cantidadActual - 1;
            } else {
                campoCantidad.value = cantidadMinima;
            }
        });

        /* =================================================
           SUMAR CANTIDAD
        ================================================= */
        botonSumar.addEventListener("click", function () {
            const cantidadActual = Number(campoCantidad.value);
            const cantidadMaxima = obtenerCantidadMaxima();

            if (cantidadActual < cantidadMaxima) {
                campoCantidad.value = cantidadActual + 1;
            } else {
                campoCantidad.value = cantidadMaxima;
                alert("No hay más existencias disponibles.");
            }
        });

        /* =================================================
           VALIDAR CANTIDAD ESCRITA MANUALMENTE
        ================================================= */
        campoCantidad.addEventListener("input", function () {
            let cantidad = Number(campoCantidad.value);
            const cantidadMinima = obtenerCantidadMinima();
            const cantidadMaxima = obtenerCantidadMaxima();

            if (campoCantidad.value === "" || Number.isNaN(cantidad)) {
                return;
            }

            cantidad = Math.floor(cantidad);

            if (cantidad < cantidadMinima) {
                campoCantidad.value = cantidadMinima;
                return;
            }

            if (cantidad > cantidadMaxima) {
                campoCantidad.value = cantidadMaxima;
                alert("La cantidad supera el stock disponible.");
                return;
            }

            campoCantidad.value = cantidad;
        });

        /* =================================================
           VALIDAR ANTES DE ACTUALIZAR
        ================================================= */
        formulario.addEventListener("submit", function (evento) {
            let cantidad = Number(campoCantidad.value);
            const cantidadMinima = obtenerCantidadMinima();
            const cantidadMaxima = obtenerCantidadMaxima();

            if (Number.isNaN(cantidad) || cantidad < cantidadMinima) {
                evento.preventDefault();
                campoCantidad.value = cantidadMinima;
                alert("La cantidad seleccionada no es válida.");
                return;
            }

            if (cantidad > cantidadMaxima) {
                evento.preventDefault();
                campoCantidad.value = cantidadMaxima;
                alert("La cantidad supera el stock disponible.");
            }
        });

    });

    /* =====================================================
       CONFIRMAR ELIMINACIÓN DE UN PRODUCTO
    ===================================================== */
    const formulariosEliminar = document.querySelectorAll(".remove-form");

    formulariosEliminar.forEach(function (formulario) {
        formulario.addEventListener("submit", function (evento) {
            const confirmarEliminacion = confirm("¿Deseas eliminar este producto del carrito?");

            if (!confirmarEliminacion) {
                evento.preventDefault();
            }
        });
    });

    /* =====================================================
       CONFIRMAR VACIADO DEL CARRITO
    ===================================================== */
    const formularioVaciar = document.querySelector(".empty-form");

    if (formularioVaciar) {
        formularioVaciar.addEventListener("submit", function (evento) {
            const confirmarVaciado = confirm("¿Deseas eliminar todos los productos del carrito?");

            if (!confirmarVaciado) {
                evento.preventDefault();
            }
        });
    }

});