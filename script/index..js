
console.log("LEGACY JERSEYS");
console.log("JavaScript externo cargado correctamente.");
console.log("Bienvenido a la tienda.");

window.onload = function () {
    if (!sessionStorage.getItem("bienvenidaMostrada")) {
        alert("¡Bienvenido a LEGACY JERSEYS!");
        sessionStorage.setItem("bienvenidaMostrada", "true");
    }
};