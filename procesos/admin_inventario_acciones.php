<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

requerirAdministrador(
    "../inicio_de_sesion.php",
    "../index.php"
);


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin/inventario.php");
    exit;
}



function regresarInventario(
    string $mensaje,
    string $tipo = "error",
    string $buscar = ""
): void {

    $_SESSION["mensaje_admin"] =
        $mensaje;

    $_SESSION["tipo_mensaje_admin"] =
        $tipo;

    $ruta =
        "../admin/inventario.php";

    if ($buscar !== "") {
        $ruta .=
            "?buscar=" . urlencode($buscar);
    }

    header("Location: " . $ruta);
    exit;
}


$accion =
    trim($_POST["accion"] ?? "");

$buscar =
    trim($_POST["buscar"] ?? "");

if (mb_strlen($buscar) > 100) {
    $buscar = mb_substr($buscar, 0, 100);
}


if ($accion !== "actualizar") {
    regresarInventario(
        "La acción solicitada no es válida.",
        "error",
        $buscar
    );
}



$tokenRecibido =
    $_POST["csrf_token"] ?? "";

$tokenGuardado =
    $_SESSION["csrf_admin"] ?? "";

if (
    !is_string($tokenRecibido) ||
    !is_string($tokenGuardado) ||
    $tokenGuardado === "" ||
    !hash_equals(
        $tokenGuardado,
        $tokenRecibido
    )
) {
    regresarInventario(
        "La solicitud no es válida. Recarga la página e inténtalo nuevamente.",
        "error",
        $buscar
    );
}


$idProducto = filter_var(
    $_POST["id_producto"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$idProducto || $idProducto <= 0) {
    regresarInventario(
        "El identificador del producto no es válido.",
        "error",
        $buscar
    );
}


$consultaProducto = $conexion->prepare(
    "SELECT id_producto
     FROM productos
     WHERE id_producto = ?
     LIMIT 1"
);

if (!$consultaProducto) {
    regresarInventario(
        "No fue posible consultar el producto.",
        "error",
        $buscar
    );
}

$consultaProducto->bind_param(
    "i",
    $idProducto
);

$consultaProducto->execute();

$resultadoProducto =
    $consultaProducto->get_result();

$productoExiste =
    $resultadoProducto->fetch_assoc();

$resultadoProducto->free();
$consultaProducto->close();


if (!$productoExiste) {
    regresarInventario(
        "El producto solicitado ya no existe.",
        "error",
        $buscar
    );
}


$stocksRecibidos =
    $_POST["stock"] ?? [];

if (!is_array($stocksRecibidos)) {
    $stocksRecibidos = [];
}


$tallasPermitidas = [
    "XS",
    "S",
    "M",
    "L",
    "XL",
    "XXL"
];

$stocksValidos = [];


foreach ($tallasPermitidas as $talla) {

    $stockRecibido =
        $stocksRecibidos[$talla] ?? "";

    if ($stockRecibido === "") {
        continue;
    }

    $stockValidado = filter_var(
        $stockRecibido,
        FILTER_VALIDATE_INT
    );

    if (
        $stockValidado === false ||
        $stockValidado < 0 ||
        $stockValidado > 100000
    ) {
        regresarInventario(
            "Las existencias deben ser números enteros entre 0 y 100000.",
            "error",
            $buscar
        );
    }

    $stocksValidos[$talla] =
        (int) $stockValidado;
}


try {

    $conexion->begin_transaction();


    /* Eliminar tallas anteriores */

    $eliminarInventario = $conexion->prepare(
        "DELETE FROM producto_tallas
         WHERE id_producto = ?"
    );

    if (!$eliminarInventario) {
        throw new RuntimeException(
            "No fue posible preparar la actualización del inventario."
        );
    }

    $eliminarInventario->bind_param(
        "i",
        $idProducto
    );

    if (!$eliminarInventario->execute()) {
        throw new RuntimeException(
            "No fue posible eliminar las existencias anteriores."
        );
    }

    $eliminarInventario->close();


    /* Insertar tallas actualizadas */

    if (count($stocksValidos) > 0) {

        $insertarInventario = $conexion->prepare(
            "INSERT INTO producto_tallas (
                id_producto,
                talla,
                stock
            )
            VALUES (?, ?, ?)"
        );

        if (!$insertarInventario) {
            throw new RuntimeException(
                "No fue posible preparar las nuevas existencias."
            );
        }


        foreach ($stocksValidos as $talla => $stock) {

            $tallaActual =
                (string) $talla;

            $stockActual =
                (int) $stock;

            $insertarInventario->bind_param(
                "isi",
                $idProducto,
                $tallaActual,
                $stockActual
            );

            if (!$insertarInventario->execute()) {
                throw new RuntimeException(
                    "No fue posible guardar las nuevas existencias."
                );
            }
        }

        $insertarInventario->close();
    }


    $conexion->commit();


    $_SESSION["csrf_admin"] =
        bin2hex(random_bytes(32));


    regresarInventario(
        "El inventario se actualizó correctamente.",
        "exito",
        $buscar
    );

} catch (Throwable $error) {

    $conexion->rollback();

    regresarInventario(
        $error->getMessage(),
        "error",
        $buscar
    );
}