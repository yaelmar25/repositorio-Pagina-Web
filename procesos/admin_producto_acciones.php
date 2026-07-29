<?php

require_once __DIR__ . "/../config/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

requerirAdministrador(
    "../inicio_de_sesion.php",
    "../index.php"
);



if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin/productos.php");
    exit;
}


$accion = trim($_POST["accion"] ?? "");



function regresarProducto(
    string $mensaje,
    array $datos = []
): void {

    $_SESSION["mensaje_admin"] = $mensaje;
    $_SESSION["tipo_mensaje_admin"] = "error";
    $_SESSION["datos_producto"] = $datos;

    header("Location: ../admin/producto_nuevo.php");
    exit;
}



function regresarProductoEditar(
    int $idProducto,
    string $mensaje,
    array $datos = []
): void {

    $_SESSION["mensaje_admin"] = $mensaje;
    $_SESSION["tipo_mensaje_admin"] = "error";
    $_SESSION["datos_producto_editar"] = $datos;

    header(
        "Location: ../admin/producto_editar.php?id=" .
        $idProducto
    );

    exit;
}


function generarSlugBase(string $texto): string
{
    $texto = trim($texto);

    $textoConvertido = iconv(
        "UTF-8",
        "ASCII//TRANSLIT//IGNORE",
        $texto
    );

    if ($textoConvertido !== false) {
        $texto = $textoConvertido;
    }

    $texto = strtolower($texto);

    $texto = preg_replace(
        "/[^a-z0-9]+/",
        "-",
        $texto
    ) ?? "";

    $texto = trim($texto, "-");

    if ($texto === "") {
        return "producto";
    }

    return $texto;
}


function obtenerSlugUnico(
    mysqli $conexion,
    string $nombre
): string {

    $slugBase = generarSlugBase($nombre);
    $slug = $slugBase;
    $numero = 2;

    $consultaSlug = $conexion->prepare(
        "SELECT id_producto
         FROM productos
         WHERE slug = ?
         LIMIT 1"
    );

    if (!$consultaSlug) {
        throw new RuntimeException(
            "No fue posible validar el identificador del producto."
        );
    }

    $consultaSlug->bind_param(
        "s",
        $slug
    );

    while (true) {

        $consultaSlug->execute();

        $resultadoSlug =
            $consultaSlug->get_result();

        $existe =
            $resultadoSlug->fetch_assoc();

        $resultadoSlug->free();

        if (!$existe) {
            break;
        }

        $slug =
            $slugBase . "-" . $numero;

        $numero++;
    }

    $consultaSlug->close();

    return $slug;
}



function obtenerMensajeErrorImagen(int $codigo): string
{
    return match ($codigo) {

        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE =>
            "La imagen supera el tamaño permitido.",

        UPLOAD_ERR_PARTIAL =>
            "La imagen solamente se subió parcialmente.",

        UPLOAD_ERR_NO_FILE =>
            "Selecciona una imagen para el producto.",

        UPLOAD_ERR_NO_TMP_DIR =>
            "No se encontró la carpeta temporal del servidor.",

        UPLOAD_ERR_CANT_WRITE =>
            "El servidor no pudo guardar la imagen.",

        UPLOAD_ERR_EXTENSION =>
            "La subida de la imagen fue bloqueada por el servidor.",

        default =>
            "Ocurrió un error al subir la imagen."
    };
}


if ($accion === "crear") {


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
        regresarProducto(
            "La solicitud no es válida. Recarga la página e inténtalo nuevamente."
        );
    }


    $nombre =
        trim($_POST["nombre"] ?? "");

    $equipo =
        trim($_POST["equipo"] ?? "");

    $modelo =
        trim($_POST["modelo"] ?? "");

    $descripcion =
        trim($_POST["descripcion"] ?? "");

    $precioRecibido =
        trim($_POST["precio"] ?? "");

    $descuentoRecibido =
        trim($_POST["descuento"] ?? "0");

    $stocksRecibidos =
        $_POST["stock"] ?? [];


    if (!is_array($stocksRecibidos)) {
        $stocksRecibidos = [];
    }


    $datosFormulario = [
        "nombre" => $nombre,
        "equipo" => $equipo,
        "modelo" => $modelo,
        "descripcion" => $descripcion,
        "precio" => $precioRecibido,
        "descuento" => $descuentoRecibido,
        "stock" => $stocksRecibidos
    ];


    if (
        $nombre === "" ||
        $equipo === "" ||
        $modelo === "" ||
        $descripcion === "" ||
        $precioRecibido === ""
    ) {
        regresarProducto(
            "Completa todos los campos obligatorios.",
            $datosFormulario
        );
    }


    if (
        mb_strlen($nombre) < 3 ||
        mb_strlen($nombre) > 150
    ) {
        regresarProducto(
            "El nombre debe tener entre 3 y 150 caracteres.",
            $datosFormulario
        );
    }


    if (mb_strlen($equipo) > 100) {
        regresarProducto(
            "El equipo no puede superar los 100 caracteres.",
            $datosFormulario
        );
    }


    if (mb_strlen($modelo) > 150) {
        regresarProducto(
            "El modelo no puede superar los 150 caracteres.",
            $datosFormulario
        );
    }


    if (mb_strlen($descripcion) > 1000) {
        regresarProducto(
            "La descripción no puede superar los 1000 caracteres.",
            $datosFormulario
        );
    }


    if (!is_numeric($precioRecibido)) {
        regresarProducto(
            "El precio ingresado no es válido.",
            $datosFormulario
        );
    }

    $precio = (float) $precioRecibido;

    if ($precio <= 0) {
        regresarProducto(
            "El precio debe ser mayor que cero.",
            $datosFormulario
        );
    }



    if (!is_numeric($descuentoRecibido)) {
        regresarProducto(
            "El descuento ingresado no es válido.",
            $datosFormulario
        );
    }

    $descuento =
        (float) $descuentoRecibido;

    if (
        $descuento < 0 ||
        $descuento > 100
    ) {
        regresarProducto(
            "El descuento debe encontrarse entre 0 y 100.",
            $datosFormulario
        );
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
            $stockValidado < 0
        ) {
            regresarProducto(
                "Las existencias deben ser números enteros iguales o mayores que cero.",
                $datosFormulario
            );
        }

        $stocksValidos[$talla] =
            (int) $stockValidado;
    }


    if (count($stocksValidos) === 0) {
        regresarProducto(
            "Registra al menos una talla para el producto.",
            $datosFormulario
        );
    }


    if (
        !isset($_FILES["imagen"]) ||
        !is_array($_FILES["imagen"])
    ) {
        regresarProducto(
            "Selecciona una imagen para el producto.",
            $datosFormulario
        );
    }


    $imagen = $_FILES["imagen"];

    $errorImagen =
        (int) ($imagen["error"] ?? UPLOAD_ERR_NO_FILE);


    if ($errorImagen !== UPLOAD_ERR_OK) {
        regresarProducto(
            obtenerMensajeErrorImagen($errorImagen),
            $datosFormulario
        );
    }


    $rutaTemporal =
        $imagen["tmp_name"] ?? "";

    $tamanoImagen =
        (int) ($imagen["size"] ?? 0);


    if (
        $rutaTemporal === "" ||
        !is_uploaded_file($rutaTemporal)
    ) {
        regresarProducto(
            "El archivo recibido no es una imagen subida correctamente.",
            $datosFormulario
        );
    }


    $tamanoMaximo =
        5 * 1024 * 1024;

    if ($tamanoImagen <= 0) {
        regresarProducto(
            "La imagen seleccionada está vacía.",
            $datosFormulario
        );
    }

    if ($tamanoImagen > $tamanoMaximo) {
        regresarProducto(
            "La imagen no puede superar los 5 MB.",
            $datosFormulario
        );
    }


    $finfo =
        new finfo(FILEINFO_MIME_TYPE);

    $tipoMime =
        $finfo->file($rutaTemporal);


    $tiposPermitidos = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];


    if (
        !is_string($tipoMime) ||
        !isset($tiposPermitidos[$tipoMime])
    ) {
        regresarProducto(
            "La imagen debe estar en formato JPG, PNG o WebP.",
            $datosFormulario
        );
    }


    if (@getimagesize($rutaTemporal) === false) {
        regresarProducto(
            "El archivo seleccionado no contiene una imagen válida.",
            $datosFormulario
        );
    }


    $extension =
        $tiposPermitidos[$tipoMime];

    $nombreBaseImagen =
        generarSlugBase($nombre);

    try {

        $identificadorImagen =
            bin2hex(random_bytes(8));

    } catch (Throwable $error) {

        regresarProducto(
            "No fue posible generar un nombre seguro para la imagen.",
            $datosFormulario
        );
    }


    $nombreArchivo =
        $nombreBaseImagen .
        "-" .
        $identificadorImagen .
        "." .
        $extension;


    $directorioImagenes =
        __DIR__ . "/../pictures/productos";


    if (
        !is_dir($directorioImagenes) &&
        !mkdir(
            $directorioImagenes,
            0755,
            true
        )
    ) {
        regresarProducto(
            "No fue posible crear la carpeta para guardar las imágenes.",
            $datosFormulario
        );
    }


    $rutaFisicaImagen =
        $directorioImagenes .
        "/" .
        $nombreArchivo;


    $rutaBaseDatos =
        "pictures/productos/" .
        $nombreArchivo;


    if (
        !move_uploaded_file(
            $rutaTemporal,
            $rutaFisicaImagen
        )
    ) {
        regresarProducto(
            "No fue posible guardar la imagen dentro del proyecto.",
            $datosFormulario
        );
    }


    $transaccionIniciada = false;

    try {

        $conexion->begin_transaction();
        $transaccionIniciada = true;


        $slug = obtenerSlugUnico(
            $conexion,
            $nombre
        );


        /* INSERTAR PRODUCTO */

        $insertarProducto = $conexion->prepare(
            "INSERT INTO productos (
                slug,
                nombre,
                equipo,
                modelo,
                descripcion,
                precio,
                descuento
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );


        if (!$insertarProducto) {
            throw new RuntimeException(
                "No fue posible preparar el registro del producto."
            );
        }


        $insertarProducto->bind_param(
            "sssssdd",
            $slug,
            $nombre,
            $equipo,
            $modelo,
            $descripcion,
            $precio,
            $descuento
        );


        if (!$insertarProducto->execute()) {
            throw new RuntimeException(
                "No fue posible guardar el producto."
            );
        }


        $idProducto =
            $insertarProducto->insert_id;

        $insertarProducto->close();


        /* INSERTAR IMAGEN */

        $insertarImagen = $conexion->prepare(
            "INSERT INTO imagenes_producto (
                id_producto,
                ruta_imagen
            )
            VALUES (?, ?)"
        );


        if (!$insertarImagen) {
            throw new RuntimeException(
                "No fue posible preparar el registro de la imagen."
            );
        }


        $insertarImagen->bind_param(
            "is",
            $idProducto,
            $rutaBaseDatos
        );


        if (!$insertarImagen->execute()) {
            throw new RuntimeException(
                "No fue posible guardar la información de la imagen."
            );
        }


        $insertarImagen->close();


        /* INSERTAR TALLAS */

        $insertarTalla = $conexion->prepare(
            "INSERT INTO producto_tallas (
                id_producto,
                talla,
                stock
            )
            VALUES (?, ?, ?)"
        );


        if (!$insertarTalla) {
            throw new RuntimeException(
                "No fue posible preparar el registro del inventario."
            );
        }


        foreach ($stocksValidos as $talla => $stock) {

            $tallaActual =
                (string) $talla;

            $stockActual =
                (int) $stock;

            $insertarTalla->bind_param(
                "isi",
                $idProducto,
                $tallaActual,
                $stockActual
            );


            if (!$insertarTalla->execute()) {
                throw new RuntimeException(
                    "No fue posible guardar las existencias del producto."
                );
            }
        }


        $insertarTalla->close();


        /* CONFIRMAR TRANSACCIÓN */

        $conexion->commit();
        $transaccionIniciada = false;


        /* RENOVAR TOKEN */

        $_SESSION["csrf_admin"] =
            bin2hex(random_bytes(32));


        /* MENSAJE */

        $_SESSION["mensaje_admin"] =
            "El producto y su imagen se registraron correctamente.";

        $_SESSION["tipo_mensaje_admin"] =
            "exito";


        header(
            "Location: ../admin/productos.php"
        );

        exit;


    } catch (Throwable $error) {

        if ($transaccionIniciada) {
            $conexion->rollback();
        }

        /*
         * Eliminar la imagen si no se pudo
         * completar el registro en MySQL.
         */

        if (is_file($rutaFisicaImagen)) {
            unlink($rutaFisicaImagen);
        }

        regresarProducto(
            $error->getMessage(),
            $datosFormulario
        );
    }

}


if ($accion === "editar") {



    $idProducto = filter_var(
        $_POST["id_producto"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$idProducto || $idProducto <= 0) {

        $_SESSION["mensaje_admin"] =
            "El identificador del producto no es válido.";

        $_SESSION["tipo_mensaje_admin"] =
            "error";

        header("Location: ../admin/productos.php");
        exit;
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
        regresarProductoEditar(
            $idProducto,
            "La solicitud no es válida. Recarga la página e inténtalo nuevamente.",
            [
                "id_producto" => $idProducto
            ]
        );
    }


    $nombre =
        trim($_POST["nombre"] ?? "");

    $equipo =
        trim($_POST["equipo"] ?? "");

    $modelo =
        trim($_POST["modelo"] ?? "");

    $descripcion =
        trim($_POST["descripcion"] ?? "");

    $precioRecibido =
        trim($_POST["precio"] ?? "");

    $descuentoRecibido =
        trim($_POST["descuento"] ?? "0");

    $stocksRecibidos =
        $_POST["stock"] ?? [];


    if (!is_array($stocksRecibidos)) {
        $stocksRecibidos = [];
    }


    $datosFormulario = [
        "id_producto" => $idProducto,
        "nombre" => $nombre,
        "equipo" => $equipo,
        "modelo" => $modelo,
        "descripcion" => $descripcion,
        "precio" => $precioRecibido,
        "descuento" => $descuentoRecibido,
        "stock" => $stocksRecibidos
    ];



    if (
        $nombre === "" ||
        $equipo === "" ||
        $modelo === "" ||
        $descripcion === "" ||
        $precioRecibido === ""
    ) {
        regresarProductoEditar(
            $idProducto,
            "Completa todos los campos obligatorios.",
            $datosFormulario
        );
    }


    if (
        mb_strlen($nombre) < 3 ||
        mb_strlen($nombre) > 150
    ) {
        regresarProductoEditar(
            $idProducto,
            "El nombre debe tener entre 3 y 150 caracteres.",
            $datosFormulario
        );
    }


    if (mb_strlen($equipo) > 100) {
        regresarProductoEditar(
            $idProducto,
            "El equipo no puede superar los 100 caracteres.",
            $datosFormulario
        );
    }


    if (mb_strlen($modelo) > 150) {
        regresarProductoEditar(
            $idProducto,
            "El modelo no puede superar los 150 caracteres.",
            $datosFormulario
        );
    }


    if (mb_strlen($descripcion) > 1000) {
        regresarProductoEditar(
            $idProducto,
            "La descripción no puede superar los 1000 caracteres.",
            $datosFormulario
        );
    }


    if (!is_numeric($precioRecibido)) {
        regresarProductoEditar(
            $idProducto,
            "El precio ingresado no es válido.",
            $datosFormulario
        );
    }

    $precio =
        (float) $precioRecibido;

    if ($precio <= 0) {
        regresarProductoEditar(
            $idProducto,
            "El precio debe ser mayor que cero.",
            $datosFormulario
        );
    }


    if (!is_numeric($descuentoRecibido)) {
        regresarProductoEditar(
            $idProducto,
            "El descuento ingresado no es válido.",
            $datosFormulario
        );
    }

    $descuento =
        (float) $descuentoRecibido;

    if (
        $descuento < 0 ||
        $descuento > 100
    ) {
        regresarProductoEditar(
            $idProducto,
            "El descuento debe encontrarse entre 0 y 100.",
            $datosFormulario
        );
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
            $stockValidado < 0
        ) {
            regresarProductoEditar(
                $idProducto,
                "Las existencias deben ser números enteros iguales o mayores que cero.",
                $datosFormulario
            );
        }

        $stocksValidos[$talla] =
            (int) $stockValidado;
    }


    if (count($stocksValidos) === 0) {
        regresarProductoEditar(
            $idProducto,
            "Registra al menos una talla para el producto.",
            $datosFormulario
        );
    }


    $consultaActual = $conexion->prepare(
        "SELECT
            p.id_producto,

            (
                SELECT ip.id_imagen
                FROM imagenes_producto ip
                WHERE ip.id_producto = p.id_producto
                ORDER BY ip.id_imagen
                LIMIT 1
            ) AS id_imagen,

            (
                SELECT ip.ruta_imagen
                FROM imagenes_producto ip
                WHERE ip.id_producto = p.id_producto
                ORDER BY ip.id_imagen
                LIMIT 1
            ) AS ruta_imagen

        FROM productos p
        WHERE p.id_producto = ?
        LIMIT 1"
    );

    if (!$consultaActual) {
        regresarProductoEditar(
            $idProducto,
            "No fue posible consultar el producto.",
            $datosFormulario
        );
    }

    $consultaActual->bind_param(
        "i",
        $idProducto
    );

    $consultaActual->execute();

    $resultadoActual =
        $consultaActual->get_result();

    $productoActual =
        $resultadoActual->fetch_assoc();

    $resultadoActual->free();
    $consultaActual->close();


    if (!$productoActual) {

        $_SESSION["mensaje_admin"] =
            "El producto ya no existe.";

        $_SESSION["tipo_mensaje_admin"] =
            "error";

        header("Location: ../admin/productos.php");
        exit;
    }


    $idImagenActual =
        isset($productoActual["id_imagen"])
            ? (int) $productoActual["id_imagen"]
            : 0;

    $rutaImagenAnterior =
        $productoActual["ruta_imagen"] ?? "";


    $hayNuevaImagen = (
        isset($_FILES["imagen"]) &&
        is_array($_FILES["imagen"]) &&
        (int) (
            $_FILES["imagen"]["error"] ??
            UPLOAD_ERR_NO_FILE
        ) !== UPLOAD_ERR_NO_FILE
    );

    $rutaNuevaImagen = "";
    $rutaFisicaNuevaImagen = "";


    if ($hayNuevaImagen) {

        $imagen =
            $_FILES["imagen"];

        $errorImagen =
            (int) (
                $imagen["error"] ??
                UPLOAD_ERR_NO_FILE
            );


        if ($errorImagen !== UPLOAD_ERR_OK) {
            regresarProductoEditar(
                $idProducto,
                obtenerMensajeErrorImagen(
                    $errorImagen
                ),
                $datosFormulario
            );
        }


        $rutaTemporal =
            $imagen["tmp_name"] ?? "";

        $tamanoImagen =
            (int) ($imagen["size"] ?? 0);


        if (
            $rutaTemporal === "" ||
            !is_uploaded_file($rutaTemporal)
        ) {
            regresarProductoEditar(
                $idProducto,
                "El archivo recibido no es válido.",
                $datosFormulario
            );
        }


        if ($tamanoImagen <= 0) {
            regresarProductoEditar(
                $idProducto,
                "La imagen seleccionada está vacía.",
                $datosFormulario
            );
        }

        if ($tamanoImagen > 5 * 1024 * 1024) {
            regresarProductoEditar(
                $idProducto,
                "La imagen no puede superar los 5 MB.",
                $datosFormulario
            );
        }


        $finfo =
            new finfo(FILEINFO_MIME_TYPE);

        $tipoMime =
            $finfo->file($rutaTemporal);


        $tiposPermitidos = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];


        if (
            !is_string($tipoMime) ||
            !isset($tiposPermitidos[$tipoMime])
        ) {
            regresarProductoEditar(
                $idProducto,
                "La imagen debe estar en formato JPG, PNG o WebP.",
                $datosFormulario
            );
        }


        if (@getimagesize($rutaTemporal) === false) {
            regresarProductoEditar(
                $idProducto,
                "El archivo seleccionado no contiene una imagen válida.",
                $datosFormulario
            );
        }


        $extension =
            $tiposPermitidos[$tipoMime];

        try {

            $identificadorImagen =
                bin2hex(random_bytes(8));

        } catch (Throwable $error) {

            regresarProductoEditar(
                $idProducto,
                "No fue posible generar un nombre seguro para la imagen.",
                $datosFormulario
            );
        }

        $nombreArchivo =
            generarSlugBase($nombre) .
            "-" .
            $identificadorImagen .
            "." .
            $extension;


        $directorioImagenes =
            __DIR__ . "/../pictures/productos";


        if (
            !is_dir($directorioImagenes) &&
            !mkdir(
                $directorioImagenes,
                0755,
                true
            )
        ) {
            regresarProductoEditar(
                $idProducto,
                "No fue posible crear la carpeta de imágenes.",
                $datosFormulario
            );
        }


        $rutaFisicaNuevaImagen =
            $directorioImagenes .
            "/" .
            $nombreArchivo;


        $rutaNuevaImagen =
            "pictures/productos/" .
            $nombreArchivo;


        if (
            !move_uploaded_file(
                $rutaTemporal,
                $rutaFisicaNuevaImagen
            )
        ) {
            regresarProductoEditar(
                $idProducto,
                "No fue posible guardar la nueva imagen.",
                $datosFormulario
            );
        }
    }


    $transaccionIniciada = false;

    try {

        $conexion->begin_transaction();
        $transaccionIniciada = true;


        /* ACTUALIZAR INFORMACIÓN */

        $actualizarProducto = $conexion->prepare(
            "UPDATE productos
             SET
                nombre = ?,
                equipo = ?,
                modelo = ?,
                descripcion = ?,
                precio = ?,
                descuento = ?
             WHERE id_producto = ?"
        );

        if (!$actualizarProducto) {
            throw new RuntimeException(
                "No fue posible preparar la actualización."
            );
        }


        $actualizarProducto->bind_param(
            "ssssddi",
            $nombre,
            $equipo,
            $modelo,
            $descripcion,
            $precio,
            $descuento,
            $idProducto
        );


        if (!$actualizarProducto->execute()) {
            throw new RuntimeException(
                "No fue posible actualizar el producto."
            );
        }

        $actualizarProducto->close();


        /* REEMPLAZAR IMAGEN */

        if ($hayNuevaImagen) {

            if ($idImagenActual > 0) {

                $actualizarImagen = $conexion->prepare(
                    "UPDATE imagenes_producto
                     SET ruta_imagen = ?
                     WHERE id_imagen = ?"
                );

                if (!$actualizarImagen) {
                    throw new RuntimeException(
                        "No fue posible preparar la nueva imagen."
                    );
                }

                $actualizarImagen->bind_param(
                    "si",
                    $rutaNuevaImagen,
                    $idImagenActual
                );

            } else {

                $actualizarImagen = $conexion->prepare(
                    "INSERT INTO imagenes_producto (
                        id_producto,
                        ruta_imagen
                    )
                    VALUES (?, ?)"
                );

                if (!$actualizarImagen) {
                    throw new RuntimeException(
                        "No fue posible preparar la nueva imagen."
                    );
                }

                $actualizarImagen->bind_param(
                    "is",
                    $idProducto,
                    $rutaNuevaImagen
                );
            }


            if (!$actualizarImagen->execute()) {
                throw new RuntimeException(
                    "No fue posible guardar la nueva imagen."
                );
            }

            $actualizarImagen->close();
        }


        /* REEMPLAZAR TALLAS Y STOCK */

        $eliminarStocks = $conexion->prepare(
            "DELETE FROM producto_tallas
             WHERE id_producto = ?"
        );

        if (!$eliminarStocks) {
            throw new RuntimeException(
                "No fue posible preparar el inventario."
            );
        }

        $eliminarStocks->bind_param(
            "i",
            $idProducto
        );

        if (!$eliminarStocks->execute()) {
            throw new RuntimeException(
                "No fue posible actualizar el inventario."
            );
        }

        $eliminarStocks->close();


        $insertarStock = $conexion->prepare(
            "INSERT INTO producto_tallas (
                id_producto,
                talla,
                stock
            )
            VALUES (?, ?, ?)"
        );

        if (!$insertarStock) {
            throw new RuntimeException(
                "No fue posible preparar las existencias."
            );
        }


        foreach ($stocksValidos as $talla => $stock) {

            $tallaActual =
                (string) $talla;

            $stockActual =
                (int) $stock;

            $insertarStock->bind_param(
                "isi",
                $idProducto,
                $tallaActual,
                $stockActual
            );

            if (!$insertarStock->execute()) {
                throw new RuntimeException(
                    "No fue posible guardar las existencias."
                );
            }
        }

        $insertarStock->close();


        $conexion->commit();
        $transaccionIniciada = false;


        /*
         * Eliminar la imagen anterior únicamente
         * después de confirmar la actualización.
         */

        if (
            $hayNuevaImagen &&
            $rutaImagenAnterior !== "" &&
            str_starts_with(
                $rutaImagenAnterior,
                "pictures/productos/"
            )
        ) {
            $rutaFisicaAnterior =
                __DIR__ .
                "/../" .
                $rutaImagenAnterior;

            if (is_file($rutaFisicaAnterior)) {
                unlink($rutaFisicaAnterior);
            }
        }


        $_SESSION["csrf_admin"] =
            bin2hex(random_bytes(32));

        $_SESSION["mensaje_admin"] =
            "El producto se actualizó correctamente.";

        $_SESSION["tipo_mensaje_admin"] =
            "exito";

        header("Location: ../admin/productos.php");
        exit;


    } catch (Throwable $error) {

        if ($transaccionIniciada) {
            $conexion->rollback();
        }

        /*
         * Eliminar la nueva imagen si la
         * actualización no se completó.
         */

        if (
            $rutaFisicaNuevaImagen !== "" &&
            is_file($rutaFisicaNuevaImagen)
        ) {
            unlink($rutaFisicaNuevaImagen);
        }

        regresarProductoEditar(
            $idProducto,
            $error->getMessage(),
            $datosFormulario
        );
    }
}


header("Location: ../admin/productos.php");
exit;
