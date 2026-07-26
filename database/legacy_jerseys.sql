-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-07-2026 a las 05:41:14
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `legacy_jerseys`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `talla` varchar(10) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id_detalle`, `id_pedido`, `id_producto`, `talla`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 2, 'S', 1, 1359.20),
(2, 1, 5, 'S', 1, 1349.25),
(3, 2, 3, 'M', 1, 1799.00),
(4, 3, 3, 'L', 1, 1799.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `imagenes_producto`
--

CREATE TABLE `imagenes_producto` (
  `id_imagen` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `ruta_imagen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `imagenes_producto`
--

INSERT INTO `imagenes_producto` (`id_imagen`, `id_producto`, `ruta_imagen`) VALUES
(1, 1, 'pictures/madrid.jpg'),
(2, 2, 'pictures/barsa.jpg'),
(3, 3, 'pictures/man.jpg'),
(4, 4, 'pictures/liverpool.jpg'),
(5, 5, 'pictures/psg.jpg'),
(6, 6, 'pictures/bayern.jpg'),
(7, 7, 'pictures/juventus.jpg'),
(8, 8, 'pictures/chelsea.jpg'),
(9, 9, 'pictures/mexico.jpg'),
(10, 10, 'pictures/arg.jpg'),
(11, 11, 'pictures/brasil.jpg'),
(12, 12, 'pictures/francia.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `id_usuario`, `direccion`, `total`, `estado`) VALUES
(1, 2, 'Zacatepec Col Centro', 2708.45, 'pendiente'),
(2, 1, 'Zacatepec Col Centro', 1799.00, 'pendiente'),
(3, 1, 'Zacatepec Col Centro', 1799.00, 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `equipo` varchar(100) NOT NULL,
  `modelo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `descuento` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `slug`, `nombre`, `equipo`, `modelo`, `descripcion`, `precio`, `descuento`) VALUES
(1, 'real-madrid', 'Real Madrid 24/25', 'Real Madrid', 'Modelo Local Versión Jugador', 'Jersey oficial del Real Madrid temporada 2024/2025, fabricado con tela transpirable.', 1699.00, 25.00),
(2, 'barcelona', 'Barcelona 24/25', 'Barcelona', 'Modelo Local Oficial', 'Jersey del FC Barcelona con diseño clásico blaugrana y materiales cómodos.', 1699.00, 20.00),
(3, 'manchester-city', 'Manchester City 24/25', 'Manchester City', 'Modelo Local Oficial', 'Jersey oficial del Manchester City con tecnología de ventilación avanzada.', 1799.00, 0.00),
(4, 'liverpool', 'Liverpool 24/25', 'Liverpool', 'Modelo Local Oficial', 'Camiseta clásica del Liverpool FC con detalles icónicos.', 1699.00, 0.00),
(5, 'psg', 'PSG 24/25', 'Paris Saint-Germain', 'Modelo Local Versión Estadio', 'Jersey oficial del Paris Saint-Germain con diseño urbano.', 1799.00, 25.00),
(6, 'bayern', 'Bayern Múnich 24/25', 'Bayern Múnich', 'Modelo Local Oficial', 'Jersey del Bayern Múnich con el emblemático color rojo.', 1699.00, 0.00),
(7, 'juventus', 'Juventus 24/25', 'Juventus', 'Modelo Local Oficial', 'Camiseta oficial de la Juventus con franjas blancas y negras.', 1699.00, 0.00),
(8, 'chelsea', 'Chelsea 24/25', 'Chelsea', 'Modelo Local Oficial', 'Jersey del Chelsea con tejido transpirable.', 1699.00, 0.00),
(9, 'mexico', 'México 2026', 'México', 'Edición Nacional Especial', 'Jersey oficial de la Selección Mexicana.', 1499.00, 15.00),
(10, 'argentina', 'Argentina 24/25', 'Argentina', 'Modelo Oficial Campeón', 'Jersey oficial de Argentina con tres estrellas.', 1599.00, 0.00),
(11, 'brasil', 'Brasil 24/25', 'Brasil', 'Modelo Local Oficial', 'Camiseta oficial de Brasil con el clásico color amarillo.', 1599.00, 0.00),
(12, 'francia', 'Francia 24/25', 'Francia', 'Modelo Local Oficial', 'Jersey oficial de Francia con diseño sofisticado.', 1599.00, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_tallas`
--

CREATE TABLE `producto_tallas` (
  `id_producto_talla` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `talla` varchar(10) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_tallas`
--

INSERT INTO `producto_tallas` (`id_producto_talla`, `id_producto`, `talla`, `stock`) VALUES
(1, 1, 'S', 3),
(2, 1, 'M', 4),
(3, 1, 'L', 3),
(4, 1, 'XL', 2),
(49, 2, 'S', 0),
(50, 2, 'M', 3),
(51, 2, 'L', 2),
(52, 2, 'XL', 2),
(53, 3, 'S', 2),
(54, 3, 'M', 2),
(55, 3, 'L', 2),
(56, 3, 'XL', 2),
(57, 4, 'S', 3),
(58, 4, 'M', 4),
(59, 4, 'L', 4),
(60, 4, 'XL', 3),
(61, 5, 'S', 1),
(62, 5, 'M', 3),
(63, 5, 'L', 2),
(64, 5, 'XL', 2),
(65, 6, 'S', 1),
(66, 6, 'M', 2),
(67, 6, 'L', 2),
(68, 6, 'XL', 2),
(69, 7, 'S', 2),
(70, 7, 'M', 3),
(71, 7, 'L', 4),
(72, 7, 'XL', 2),
(73, 8, 'S', 1),
(74, 8, 'M', 2),
(75, 8, 'L', 2),
(76, 8, 'XL', 1),
(77, 9, 'S', 4),
(78, 9, 'M', 6),
(79, 9, 'L', 6),
(80, 9, 'XL', 4),
(81, 10, 'S', 3),
(82, 10, 'M', 5),
(83, 10, 'L', 4),
(84, 10, 'XL', 3),
(85, 11, 'S', 4),
(86, 11, 'M', 5),
(87, 11, 'L', 5),
(88, 11, 'XL', 4),
(89, 12, 'S', 3),
(90, 12, 'M', 4),
(91, 12, 'L', 3),
(92, 12, 'XL', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `contrasena`) VALUES
(1, 'Gian Uribe', 'gian@gmail.com', '$2y$10$ozk.dHtTwE4JS/.2B9J0aeNFFIjrsiudpC9gyb7dAdFJ59GDSmM8O'),
(2, 'Maxmiliano Valle', 'max@gmail.com', '$2y$10$6SB1Umtf.L8s0Qy6IIxvxOl8O/7.IAG0Ue7R8v.q1t.GsAA/nvT/e'),
(3, 'Yael Marin', 'yael@gmail.com', '$2y$10$/MGwQJSGj6LqovmLuj9OkemgzFoYulUIZVd6VKZilTRls72VII/Iq');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `fk_detalle_pedido` (`id_pedido`),
  ADD KEY `fk_detalle_producto` (`id_producto`);

--
-- Indices de la tabla `imagenes_producto`
--
ALTER TABLE `imagenes_producto`
  ADD PRIMARY KEY (`id_imagen`),
  ADD KEY `fk_imagen_producto` (`id_producto`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `fk_pedido_usuario` (`id_usuario`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `producto_tallas`
--
ALTER TABLE `producto_tallas`
  ADD PRIMARY KEY (`id_producto_talla`),
  ADD UNIQUE KEY `uk_producto_talla` (`id_producto`,`talla`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `imagenes_producto`
--
ALTER TABLE `imagenes_producto`
  MODIFY `id_imagen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `producto_tallas`
--
ALTER TABLE `producto_tallas`
  MODIFY `id_producto_talla` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `imagenes_producto`
--
ALTER TABLE `imagenes_producto`
  ADD CONSTRAINT `fk_imagen_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `producto_tallas`
--
ALTER TABLE `producto_tallas`
  ADD CONSTRAINT `fk_talla_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
