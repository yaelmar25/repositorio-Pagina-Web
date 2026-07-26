document.addEventListener("DOMContentLoaded", function () {

    const formularioPago = document.getElementById("payment-form");

    if (!formularioPago) {
        console.error("No se encontró el formulario de pago.");
        return;
    }

    
    const direccionEntrega  = document.getElementById("shipping-address");
    const nombreTarjeta     = document.getElementById("card-name");
    const numeroTarjeta     = document.getElementById("card-number");
    const fechaVencimiento  = document.getElementById("expiry-date");
    const codigoSeguridad   = document.getElementById("security-code");
    const metodoPago        = document.getElementById("payment-method");
    const estadoTransaccion = document.getElementById("transaction-status");
    const mensajeFormulario = document.getElementById("form-message");
    const botonConfirmar    = document.getElementById("pay-button");

    let formularioEnviado = false;

    
    function soloNumeros(valor) {
        return String(valor).replace(/\D/g, "");
    }

    function establecerEstado(estado, mensaje = "") {
        if (estadoTransaccion) {
            estadoTransaccion.className = "status-badge";
        }

        if (mensajeFormulario) {
            mensajeFormulario.className = "form-message";
        }

        if (estado === "aprobado") {
            if (estadoTransaccion) {
                estadoTransaccion.classList.add("approved");
                estadoTransaccion.textContent = "Aprobado";
            }
            if (mensajeFormulario) {
                mensajeFormulario.classList.add("success");
                mensajeFormulario.textContent = mensaje || "Datos correctos. Procesando la compra...";
            }
            return;
        }

        if (estado === "rechazado") {
            if (estadoTransaccion) {
                estadoTransaccion.classList.add("rejected");
                estadoTransaccion.textContent = "Rechazado";
            }
            if (mensajeFormulario) {
                mensajeFormulario.classList.add("error");
                mensajeFormulario.textContent = mensaje;
            }
            return;
        }

        if (estadoTransaccion) {
            estadoTransaccion.classList.add("pending");
            estadoTransaccion.textContent = "Pendiente";
        }

        if (mensajeFormulario) {
            mensajeFormulario.textContent = "";
        }
    }

    function mostrarError(mensaje, campo = null) {
        establecerEstado("rechazado", mensaje);

        if (campo) {
            campo.focus();
        }
    }

    function reiniciarEstado() {
        if (!formularioEnviado) {
            establecerEstado("pendiente");
        }
    }

    
    if (nombreTarjeta) {
        nombreTarjeta.addEventListener("input", function () {
            nombreTarjeta.value = nombreTarjeta.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'-]/g, "");
            reiniciarEstado();
        });
    }

    if (numeroTarjeta) {
        numeroTarjeta.addEventListener("input", function () {
            const numeros = soloNumeros(numeroTarjeta.value).slice(0, 16);
            numeroTarjeta.value = numeros.replace(/(\d{4})(?=\d)/g, "$1 ");
            reiniciarEstado();
        });
    }

    if (fechaVencimiento) {
        fechaVencimiento.addEventListener("input", function () {
            const numeros = soloNumeros(fechaVencimiento.value).slice(0, 4);

            if (numeros.length > 2) {
                fechaVencimiento.value = numeros.slice(0, 2) + " / " + numeros.slice(2);
            } else {
                fechaVencimiento.value = numeros;
            }

            reiniciarEstado();
        });
    }

    if (codigoSeguridad) {
        codigoSeguridad.addEventListener("input", function () {
            codigoSeguridad.value = soloNumeros(codigoSeguridad.value).slice(0, 3);
            reiniciarEstado();
        });
    }

    if (direccionEntrega) {
        direccionEntrega.addEventListener("input", reiniciarEstado);
    }

    if (metodoPago) {
        metodoPago.addEventListener("change", reiniciarEstado);
    }

    
    function fechaEsValida(fechaLimpia) {
        if (fechaLimpia.length !== 4) {
            return false;
        }

        const mes  = Number(fechaLimpia.slice(0, 2));
        const anio = Number(fechaLimpia.slice(2, 4));

        if (Number.isNaN(mes) || Number.isNaN(anio)) {
            return false;
        }

        if (mes < 1 || mes > 12) {
            return false;
        }

        const fechaActual = new Date();
        const mesActual   = fechaActual.getMonth() + 1;
        const anioActual  = fechaActual.getFullYear() % 100;

        if (anio < anioActual) {
            return false;
        }

        if (anio === anioActual && mes < mesActual) {
            return false;
        }

        return true;
    }

    
    formularioPago.addEventListener("submit", function (evento) {
        evento.preventDefault();

        if (formularioEnviado) {
            return;
        }

        const direccion = direccionEntrega ? direccionEntrega.value.trim() : "";
        const nombre    = nombreTarjeta ? nombreTarjeta.value.trim() : "";
        const numero    = numeroTarjeta ? soloNumeros(numeroTarjeta.value) : "";
        const fecha     = fechaVencimiento ? soloNumeros(fechaVencimiento.value) : "";
        const codigo    = codigoSeguridad ? soloNumeros(codigoSeguridad.value) : "";
        const metodo    = metodoPago ? metodoPago.value : "";

        if (direccion.length < 10) {
            mostrarError("La dirección debe contener al menos 10 caracteres.", direccionEntrega);
            return;
        }

        if (direccion.length > 255) {
            mostrarError("La dirección no puede superar los 255 caracteres.", direccionEntrega);
            return;
        }

        if (nombre.length < 3) {
            mostrarError("Ingresa el nombre completo del titular.", nombreTarjeta);
            return;
        }

        if (numero.length !== 16) {
            mostrarError("El número de tarjeta debe contener 16 dígitos.", numeroTarjeta);
            return;
        }

        if (!fechaEsValida(fecha)) {
            mostrarError("Ingresa una fecha de vencimiento válida.", fechaVencimiento);
            return;
        }

        if (codigo.length !== 3) {
            mostrarError("El código de seguridad debe contener 3 dígitos.", codigoSeguridad);
            return;
        }

        if (metodo === "") {
            mostrarError("Selecciona un método de pago.", metodoPago);
            return;
        }

        /
        formularioEnviado = true;
        establecerEstado("aprobado", "Datos correctos. Registrando la compra...");

        if (botonConfirmar) {
            botonConfirmar.disabled = true;
            botonConfirmar.textContent = "Procesando compra...";
        }

        
        setTimeout(function () {
            HTMLFormElement.prototype.submit.call(formularioPago);
        }, 150);
    });

    establecerEstado("pendiente");

});