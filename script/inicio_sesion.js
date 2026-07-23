document.addEventListener("DOMContentLoaded", function () {

    const botonMostrarRegistro = document.getElementById("boton-mostrar-registro");
    const panelRegistro = document.getElementById("registro");
    const formularioLogin = document.getElementById("formulario-login");
    const formularioRegistro = document.getElementById("formulario-registro");

    /* =====================================================
       MOSTRAR U OCULTAR EL FORMULARIO DE REGISTRO
    ===================================================== */
    function actualizarEstadoRegistro() {
        if (!botonMostrarRegistro || !panelRegistro) {
            return;
        }

        const estaVisible = panelRegistro.classList.contains("registro-visible");

        botonMostrarRegistro.textContent = estaVisible ? "Ocultar registro" : "Crear una cuenta";
        botonMostrarRegistro.setAttribute("aria-expanded", estaVisible ? "true" : "false");
    }

    if (botonMostrarRegistro && panelRegistro) {
        botonMostrarRegistro.addEventListener("click", function () {
            panelRegistro.classList.toggle("registro-visible");
            actualizarEstadoRegistro();
        });

        actualizarEstadoRegistro();
    }

    /* =====================================================
       VALIDAR INICIO DE SESIÓN
    ===================================================== */
    if (formularioLogin) {
        formularioLogin.addEventListener("submit", function (evento) {
            const correoLogin = document.getElementById("correoLogin").value.trim();
            const contrasenaLogin = document.getElementById("contrasenaLogin").value;

            if (correoLogin === "" || contrasenaLogin === "") {
                evento.preventDefault();
                alert("Ingresa tu correo y contraseña.");
            }
        });
    }

    /* =====================================================
       VALIDAR REGISTRO
    ===================================================== */
    if (formularioRegistro) {
        formularioRegistro.addEventListener("submit", function (evento) {
            const nombre = document.getElementById("nombre").value.trim();
            const correo = document.getElementById("correo").value.trim();
            const contrasena = document.getElementById("contrasena").value;
            const confirmar = document.getElementById("confirmar").value;

            if (nombre === "" || correo === "" || contrasena === "" || confirmar === "") {
                evento.preventDefault();
                alert("Todos los campos son obligatorios.");
                return;
            }

            if (nombre.length < 3) {
                evento.preventDefault();
                alert("El nombre debe tener al menos 3 caracteres.");
                return;
            }

            if (/\d/.test(nombre)) {
                evento.preventDefault();
                alert("El nombre no puede contener números.");
                return;
            }

            const expresionNombre = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s'-]+$/;
            if (!expresionNombre.test(nombre)) {
                evento.preventDefault();
                alert("El nombre solamente puede contener letras y espacios.");
                return;
            }

            if (contrasena.length < 6) {
                evento.preventDefault();
                alert("La contraseña debe tener al menos 6 caracteres.");
                return;
            }

            if (contrasena.includes(" ")) {
                evento.preventDefault();
                alert("La contraseña no puede contener espacios.");
                return;
            }

            if (contrasena !== confirmar) {
                evento.preventDefault();
                alert("Las contraseñas no coinciden.");
            }
        });
    }

});