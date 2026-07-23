document.addEventListener("DOMContentLoaded", function () {

    const formularioPago = document.getElementById("payment-form");

    /*
    | Si la página no contiene el formulario de pago,
    | no se ejecuta el resto del código.
    */
    if (!formularioPago) {
        return;
    }

    const nombreTarjeta = document.getElementById("card-name");
    const numeroTarjeta = document.getElementById("card-number");
    const fechaVencimiento = document.getElementById("expiry-date");
    const codigoSeguridad = document.getElementById("security-code");
    const estadoTransaccion = document.getElementById("transaction-status");
    const mensajeFormulario = document.getElementById("form-message");

    /* =====================================================
       CONSERVAR SOLAMENTE NÚMEROS
    ===================================================== */
    function soloNumeros(valor) {
        return valor.replace(/\D/g, "");
    }

    /* =====================================================
       CAMBIAR EL ESTADO VISUAL DEL PAGO
    ===================================================== */
    function establecerEstado(estado) {
        if (!estadoTransaccion || !mensajeFormulario) {
            return;
        }

        estadoTransaccion.className = "status-badge";
        mensajeFormulario.className = "form-message";

        if (estado === "aprobado") {
            estadoTransaccion.classList.add("approved");
            estadoTransaccion.textContent = "Aprobado";
            mensajeFormulario.classList.add("success");
            mensajeFormulario.textContent = "Pago confirmado correctamente.";
            return;
        }

        if (estado === "rechazado") {
            estadoTransaccion.classList.add("rejected");
            estadoTransaccion.textContent = "Rechazado";
            mensajeFormulario.classList.add("error");
            mensajeFormulario.textContent = "Revisa los datos de la tarjeta e intenta nuevamente.";
            return;
        }

        estadoTransaccion.classList.add("pending");
        estadoTransaccion.textContent = "Pendiente";
        mensajeFormulario.textContent = "";
    }

    /* =====================================================
       MOSTRAR UN ERROR ESPECÍFICO
    ===================================================== */
    function mostrarError(mensaje) {
        if (!estadoTransaccion || !mensajeFormulario) {
            alert(mensaje);
            return;
        }

        estadoTransaccion.className = "status-badge rejected";
        estadoTransaccion.textContent = "Rechazado";
        mensajeFormulario.className = "form-message error";
        mensajeFormulario.textContent = mensaje;
    }

    /* =====================================================
       FORMATEAR NOMBRE DEL TITULAR
    ===================================================== */
    if (nombreTarjeta) {
        nombreTarjeta.addEventListener("input", function () {
            /*
            | Permite letras, espacios, acentos,
            | apóstrofes y guiones.
            */
            nombreTarjeta.value = nombreTarjeta.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'-]/g, "");
            establecerEstado("pendiente");
        });
    }

    /* =====================================================
       FORMATEAR NÚMERO DE TARJETA
    ===================================================== */
    if (numeroTarjeta) {
        numeroTarjeta.addEventListener("input", function () {
            const numeros = soloNumeros(numeroTarjeta.value).slice(0, 16);

            /*
            | Separa el número en grupos de cuatro.
            | Ejemplo: 1234 5678 9012 3456
            */
            numeroTarjeta.value = numeros.replace(/(\d{4})(?=\d)/g, "$1 ");
            establecerEstado("pendiente");
        });
    }

    /* =====================================================
       FORMATEAR FECHA DE VENCIMIENTO
    ===================================================== */
    if (fechaVencimiento) {
        fechaVencimiento.addEventListener("input", function () {
            const numeros = soloNumeros(fechaVencimiento.value).slice(0, 4);

            if (numeros.length > 2) {
                fechaVencimiento.value = numeros.slice(0, 2) + " / " + numeros.slice(2);
            } else {
                fechaVencimiento.value = numeros;
            }

            establecerEstado("pendiente");
        });
    }

    /* =====================================================
       FORMATEAR CÓDIGO DE SEGURIDAD
    ===================================================== */
    if (codigoSeguridad) {
        codigoSeguridad.addEventListener("input", function () {
            codigoSeguridad.value = soloNumeros(codigoSeguridad.value).slice(0, 3);
            establecerEstado("pendiente");
        });
    }

    /* =====================================================
       VALIDAR FECHA DE VENCIMIENTO
    ===================================================== */
    function fechaEsValida(fechaLimpia) {
        if (fechaLimpia.length !== 4) {
            return false;
        }

        const mes = Number(fechaLimpia.slice(0, 2));
        const anioCorto = Number(fechaLimpia.slice(2, 4));

        if (mes < 1 || mes > 12) {
            return false;
        }

        const fechaActual = new Date();
        const anioActual = fechaActual.getFullYear() % 100;
        const mesActual = fechaActual.getMonth() + 1;

        if (anioCorto < anioActual) {
            return false;
        }

        if (anioCorto === anioActual && mes < mesActual) {
            return false;
        }

        return true;
    }

    /* =====================================================
       VALIDAR FORMULARIO DE PAGO
    ==================================================== */
    formularioPago.addEventListener("submit", function (evento) {
        /*
        | Por ahora se evita el envío porque todavía
        | no está conectado con pedidos y detalle_pedido.
        */
        evento.preventDefault();

        const nombre = nombreTarjeta ? nombreTarjeta.value.trim() : "";
        const numeroLimpio = numeroTarjeta ? soloNumeros(numeroTarjeta.value) : "";
        const fechaLimpia = fechaVencimiento ? soloNumeros(fechaVencimiento.value) : "";
        const codigoLimpio = codigoSeguridad ? soloNumeros(codigoSeguridad.value) : "";

        /* Validar nombre */
        if (nombre.length < 3) {
            mostrarError("Ingresa el nombre completo del titular.");
            if (nombreTarjeta) {
                nombreTarjeta.focus();
            }
            return;
        }

        /* Validar número de tarjeta */
        if (numeroLimpio.length !== 16) {
            mostrarError("El número de tarjeta debe contener 16 dígitos.");
            if (numeroTarjeta) {
                numeroTarjeta.focus();
            }
            return;
        }

        /* Validar fecha */
        if (!fechaEsValida(fechaLimpia)) {
            mostrarError("Ingresa una fecha de vencimiento válida.");
            if (fechaVencimiento) {
                fechaVencimiento.focus();
            }
            return;
        }

        /* Validar código de seguridad */
        if (codigoLimpio.length !== 3) {
            mostrarError("El código de seguridad debe contener 3 dígitos.");
            if (codigoSeguridad) {
                codigoSeguridad.focus();
            }
            return;
        }

        /* Simulación temporal de pago correcto */
        establecerEstado("aprobado");
    });

    /* =====================================================
       ESTADO INICIAL
    ===================================================== */
    establecerEstado("pendiente");

});