
const imagenPrincipal = document.getElementById("producto-imagen");
const miniaturas = document.querySelectorAll(".miniatura-producto");

miniaturas.forEach(function (miniatura) {
    miniatura.addEventListener("click", function () {
        if (!imagenPrincipal) {
            return;
        }

        imagenPrincipal.src = miniatura.dataset.ruta;

        miniaturas.forEach(function (otraMiniatura) {
            otraMiniatura.classList.remove("selected-image");
        });

        miniatura.classList.add("selected-image");
    });
});


const botonesTalla      = document.querySelectorAll(".size-button");
const inventario        = document.getElementById("producto-inventario");
const botonRestar       = document.getElementById("restar-cantidad");
const botonSumar        = document.getElementById("sumar-cantidad");
const cantidadTexto     = document.getElementById("cantidad-producto");
const formularioCarrito = document.getElementById("formulario-carrito");
const campoTalla        = document.getElementById("talla-seleccionada");
const campoCantidad     = document.getElementById("cantidad-seleccionada");
const botonFavoritos    = document.getElementById("agregar-favoritos");


let tallaSeleccionada = "";
let stockSeleccionado = 0;
let cantidad          = 1;


botonesTalla.forEach(function (boton) {
    boton.addEventListener("click", function () {
        botonesTalla.forEach(function (otroBoton) {
            otroBoton.classList.remove("selected");
        });

        boton.classList.add("selected");

        tallaSeleccionada = boton.dataset.talla;
        stockSeleccionado = Number(boton.dataset.stock);
        cantidad          = 1;

        if (campoTalla) {
            campoTalla.value = tallaSeleccionada;
        }

        if (campoCantidad) {
            campoCantidad.value = cantidad;
        }

        if (cantidadTexto) {
            cantidadTexto.textContent = cantidad;
        }

        if (inventario) {
            inventario.textContent = `${stockSeleccionado} piezas disponibles en talla ${tallaSeleccionada}`;
        }
    });
});


if (botonRestar) {
    botonRestar.addEventListener("click", function () {
        if (cantidad > 1) {
            cantidad--;

            if (cantidadTexto) {
                cantidadTexto.textContent = cantidad;
            }

            if (campoCantidad) {
                campoCantidad.value = cantidad;
            }
        }
    });
}


if (botonSumar) {
    botonSumar.addEventListener("click", function () {
        if (tallaSeleccionada === "") {
            alert("Selecciona una talla primero.");
            return;
        }

        if (cantidad < stockSeleccionado) {
            cantidad++;

            if (cantidadTexto) {
                cantidadTexto.textContent = cantidad;
            }

            if (campoCantidad) {
                campoCantidad.value = cantidad;
            }
        } else {
            alert("No hay más piezas disponibles en esa talla.");
        }
    });
}


if (formularioCarrito) {
    formularioCarrito.addEventListener("submit", function (evento) {
        if (tallaSeleccionada === "") {
            evento.preventDefault();
            alert("Selecciona una talla antes de agregar el producto.");
            return;
        }

        if (campoTalla) {
            campoTalla.value = tallaSeleccionada;
        }

        if (campoCantidad) {
            campoCantidad.value = cantidad;
        }
    });
}