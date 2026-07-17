function mostrarRegistro() {
    const registro = document.getElementById("registro");
    const estaVisible = registro.style.display === "block";

    registro.style.display = estaVisible ? "none" : "block";
}

function registrar() {
    const nombre = document.getElementById("nombre").value.trim();
    const correo = document.getElementById("correo").value.trim();
    const contrasena = document.getElementById("contrasena").value;
    const confirmar = document.getElementById("confirmar").value;

    if (!nombre || !correo || !contrasena || !confirmar) {
        alert("Todos los campos son obligatorios.");
        return;
    }

    if (nombre.length < 3 || /\d/.test(nombre)) {
        alert("Ingresa un nombre válido de al menos 3 caracteres y sin números.");
        return;
    }

    if (!correo.includes("@") || !correo.includes(".")) {
        alert("Ingresa un correo válido.");
        return;
    }

    if (contrasena.length < 6 || contrasena.includes(" ")) {
        alert("La contraseña debe tener al menos 6 caracteres y no contener espacios.");
        return;
    }

    if (contrasena !== confirmar) {
        alert("Las contraseñas no coinciden.");
        return;
    }

    localStorage.setItem("usuario", correo);
    localStorage.setItem("password", contrasena);
    localStorage.setItem("nombre", nombre);

    alert("¡Registro exitoso!");

    ["nombre", "correo", "contrasena", "confirmar"].forEach((id) => {
        document.getElementById(id).value = "";
    });

    document.getElementById("registro").style.display = "none";
}

function iniciarSesion() {
    const correo = document.getElementById("correoLogin").value.trim();
    const contrasena = document.getElementById("contrasenaLogin").value;

    if (!correo || !contrasena) {
        alert("Ingresa tu correo y contraseña.");
        return;
    }

    const correoGuardado = localStorage.getItem("usuario");
    const contrasenaGuardada = localStorage.getItem("password");
    const nombre = localStorage.getItem("nombre");

    if (!correoGuardado || !contrasenaGuardada) {
        alert("No hay ninguna cuenta registrada. Crea una cuenta primero.");
        return;
    }

    if (correo === correoGuardado && contrasena === contrasenaGuardada) {
        alert(`¡Bienvenido ${nombre}!`);
        window.location.href = "pagina_principal.html";
        return;
    }

    alert("Correo o contraseña incorrectos.");
}
