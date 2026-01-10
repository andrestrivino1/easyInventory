-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 09, 2026 at 11:30 AM
-- Server version: 10.11.15-MariaDB-cll-lve
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Desactivar verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vidriosj_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `containers`
--

CREATE TABLE `containers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reference` varchar(255) NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `containers`
--

INSERT INTO `containers` (`id`, `reference`, `warehouse_id`, `note`, `created_at`, `updated_at`) VALUES
(12, 'FCIU530927-1', 28, 'VENTA A 185.000 LAMINA', NULL, NULL),
(13, 'FCIU5309271', 17, NULL, NULL, NULL),
(14, 'FCIU530927*1', 21, NULL, NULL, NULL),
(15, 'FTAU156769-0', 17, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `container_product`
--

CREATE TABLE `container_product` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `container_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `boxes` int(11) NOT NULL,
  `sheets_per_box` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `container_product`
--

INSERT INTO `container_product` (`id`, `container_id`, `product_id`, `boxes`, `sheets_per_box`, `created_at`, `updated_at`) VALUES
(12, 12, 30, 4, 31, '2026-01-03 22:17:51', '2026-01-03 22:17:51'),
(13, 13, 30, 4, 31, '2026-01-04 03:39:52', '2026-01-04 03:39:52'),
(14, 14, 30, 2, 31, '2026-01-04 03:40:23', '2026-01-04 03:40:23'),
(15, 15, 25, 10, 31, '2026-01-04 03:42:17', '2026-01-04 03:42:17');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `identity` varchar(20) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `vehicle_plate` varchar(20) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `name`, `identity`, `phone`, `vehicle_plate`, `active`, `created_at`, `updated_at`) VALUES
(2, 'CESAR VANEGAS SERRANO', '79916566', '3114599926', 'WOM166', 1, NULL, NULL),
(3, 'FABIAN VIVAS PARADA', '1115911634', '3206615093', 'NYO322', 1, NULL, NULL),
(4, 'NICOLAS ZULUAGA CANO', '1007530763', '3145656161', 'FST609', 1, NULL, NULL),
(5, 'OSWALDO RENE PATINO CONTRERAS', '87716645', '3164527942', 'GUB993', 1, NULL, NULL),
(6, 'FABIAN RAMIRO GONZALEZ VELEZ', '70411833', '3008881605', 'SXS986', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2025_12_09_000001_create_warehouses_table', 1),
(6, '2025_12_09_153223_create_products_table', 1),
(7, '2025_12_09_183125_create_transfer_orders_table', 1),
(8, '2025_12_09_183142_create_transfer_order_products_table', 1),
(9, '2025_12_09_211752_add_user_fields_to_users_table', 1),
(10, '2025_12_12_000000_create_drivers_table', 1),
(11, '2025_12_12_000001_update_transfer_orders_driver_relation', 1),
(12, '2025_12_12_142218_create_conductors_table', 1),
(13, '2025_12_12_180000_add_tipo_medida_y_equivalencia_to_products', 1),
(14, '2025_12_12_200000_create_containers_table', 1),
(15, '2025_12_13_100001_drop_container_id_from_products', 1),
(16, '2025_12_13_200000_add_container_id_to_products', 1),
(17, '2025_12_27_130207_create_container_product_table', 1),
(18, '2025_12_27_130227_remove_container_fields_from_products_and_containers', 1),
(19, '2025_12_28_093611_add_medidas_to_products_table', 1),
(20, '2025_12_28_102819_add_container_id_to_transfer_order_products_table', 1),
(21, '2025_12_28_134808_update_user_rol_from_usuario_to_clientes', 1),
(22, '2025_12_30_112713_add_salida_destino_to_transfer_orders_table', 1),
(23, '2025_12_30_112730_rename_buenaventura_to_pablo_rojas_in_warehouses_table', 1),
(24, '2025_12_30_124348_create_salidas_table', 2),
(25, '2025_12_30_130310_change_codigo_unique_to_per_warehouse_in_products_table', 3),
(26, '2025_12_31_114545_create_user_warehouse_table', 4),
(27, '2025_12_31_115425_add_ciudad_to_warehouses_table', 5),
(28, '2025_12_31_205439_create_product_warehouse_stock_table', 6),
(29, '2025_12_31_205516_make_products_global_and_code_unique', 6),
(30, '2025_12_31_211424_make_almacen_id_nullable_and_tipo_medida_optional_in_products', 7),
(31, '2025_12_31_212136_add_warehouse_id_to_containers_table', 8),
(32, '2026_01_02_133149_add_user_id_to_salidas_table', 9),
(33, '2026_01_02_133810_add_aprobo_and_ciudad_destino_to_salidas_table', 9),
(34, '2026_01_02_141157_add_aprobo_and_ciudad_destino_to_transfer_orders_table', 9),
(35, '2026_01_02_152849_add_driver_id_to_salidas_table', 9),
(36, '2026_01_05_000000_create_imports_table', 9),
(37, '2026_01_05_000002_add_do_code_to_imports_table', 9),
(38, '2026_01_05_000003_add_import_fields_to_imports_table', 9),
(39, '2026_01_05_000004_create_import_containers_table', 9),
(40, '2026_01_07_110635_add_additional_document_fields_to_imports_table', 9),
(41, '2026_01_07_113858_add_sheets_quality_to_transfer_order_products_table', 10),
(42, '2026_01_09_111157_update_imports_table_structure', 11),
(43, '2026_01_09_111219_remove_images_from_import_containers', 12),
(44, '2026_01_09_112345_add_image_pdf_to_import_containers_table', 13);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `codigo` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(12,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `estado` tinyint(1) DEFAULT 1,
  `tipo_medida` varchar(255) DEFAULT NULL,
  `unidades_por_caja` int(11) DEFAULT NULL,
  `medidas` varchar(255) DEFAULT NULL,
  `almacen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `nombre`, `codigo`, `descripcion`, `precio`, `stock`, `estado`, `tipo_medida`, `unidades_por_caja`, `medidas`, `almacen_id`, `created_at`, `updated_at`) VALUES
(4, 'VIDRIO CLARO 4 MM', 'PRD-000001', NULL, 0.00, 0, 1, 'caja', 36, '330*225', NULL, '2026-01-02 03:32:41', '2026-01-02 03:35:31'),
(5, 'VIDRIO CLARO 6 MM', 'PRD-000002', NULL, 0.00, 0, 1, 'caja', 25, '330*214', NULL, '2026-01-02 03:32:55', '2026-01-02 03:36:14'),
(6, 'VIDRIO CLARO 10 MM', 'PRD-000003', NULL, 0.00, 0, 1, 'caja', 15, '330*214', NULL, '2026-01-03 01:54:53', '2026-01-03 03:34:19'),
(7, 'VIDRIO CLARO 8 MM', 'PRD-000004', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 01:55:16', '2026-01-03 01:55:16'),
(8, 'ESPEJO COPPER FREE 4 MM', 'PRD-000005', NULL, 0.00, 0, 1, 'caja', 40, '330*214', NULL, '2026-01-03 01:55:43', '2026-01-03 02:19:33'),
(9, 'VIDRIO CLARO 4 MM', 'PRD-000006', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 15:42:38', '2026-01-03 15:42:38'),
(10, 'VIDRIO CLARO 5 MM', 'PRD-000007', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 15:42:52', '2026-01-03 15:42:52'),
(11, 'VIDRIO CLARO 5 MM', 'PRD-000008', NULL, 0.00, 0, 1, NULL, NULL, '330*225', NULL, '2026-01-03 15:43:02', '2026-01-03 15:43:02'),
(12, 'VIDRIO CLARO 6 MM', 'PRD-000009', NULL, 0.00, 0, 1, NULL, NULL, '330*225', NULL, '2026-01-03 15:43:19', '2026-01-03 15:43:19'),
(13, 'VIDRIO LAMINADO 3+3 GRIS', 'PRD-000010', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 15:43:38', '2026-01-03 15:43:38'),
(14, 'VIDRIO LAMINADO 3+3 BRONCE', 'PRD-000011', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 15:43:57', '2026-01-03 15:43:57'),
(15, 'VIDRIO LAMINADO 3+3 HIELO', 'PRD-000012', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 16:20:52', '2026-01-03 16:20:52'),
(16, 'VIDRIO LAMINADO 3+3 MILKY WHITE', 'PRD-000013', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 16:21:11', '2026-01-03 16:21:11'),
(17, 'VIDRIO LAMINADO CLARO 3+3', 'PRD-000014', NULL, 0.00, 0, 1, NULL, NULL, '330*244', NULL, '2026-01-03 20:24:21', '2026-01-03 20:24:21'),
(18, 'VIDRIO LAMINADO CLARO 3+3', 'PRD-000015', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 20:24:35', '2026-01-03 20:24:35'),
(19, 'VIDRIO LAMINADO CLARO 4+4', 'PRD-000016', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 20:25:01', '2026-01-03 20:25:01'),
(20, 'VIDRIO LAMINADO CLARO 4+4', 'PRD-000017', NULL, 0.00, 0, 1, NULL, NULL, '330*225', NULL, '2026-01-03 20:25:50', '2026-01-03 20:25:50'),
(21, 'VIDRIO LAMINADO CLARO 3+3', 'PRD-000018', NULL, 0.00, 0, 1, NULL, NULL, '330*225', NULL, '2026-01-03 20:28:04', '2026-01-03 20:28:04'),
(22, 'VIDRIO LAMINADO CLARO 4+4', 'PRD-000019', NULL, 0.00, 0, 1, NULL, NULL, '330*244', NULL, '2026-01-03 20:28:29', '2026-01-03 20:28:29'),
(23, 'VIDRIO LAMINADO BRONCE REFLECTIVO 3+3', 'PRD-000020', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 20:29:14', '2026-01-03 20:29:14'),
(24, 'VIDRIO LAMINADO AZUL DARK REFLECTIVO 3+3', 'PRD-000021', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 20:29:39', '2026-01-03 20:29:39'),
(25, 'VIDRIO FLOTADO BRONCE 5 MM', 'PRD-000022', NULL, 0.00, 0, 1, 'caja', 31, '330*214', NULL, '2026-01-03 20:30:05', '2026-01-04 03:42:17'),
(26, 'VIDRIO FLOTADO BRONCE 4 MM', 'PRD-000023', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 20:30:15', '2026-01-03 20:30:15'),
(27, 'VIDRIO FLOTADO BRONCE 6 MM', 'PRD-000024', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 20:30:49', '2026-01-03 20:30:49'),
(28, 'VIDRIO FLOTADO AZUL DARK 4 MM', 'PRD-000025', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 20:31:07', '2026-01-03 20:31:07'),
(29, 'VIDRIO REFLECTIVO BRONCE 4 MM', 'PRD-000026', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 20:49:48', '2026-01-03 20:49:48'),
(30, 'VIDRIO REFLECTIVO BRONCE 5 MM', 'PRD-000027', NULL, 0.00, 0, 1, 'caja', 31, '330*214', NULL, '2026-01-03 20:50:02', '2026-01-03 22:17:51'),
(31, 'VIDRIO REFLECTIVO AZUL DARK 4 MM', 'PRD-000028', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-03 20:50:21', '2026-01-03 20:50:21'),
(32, 'VIDRIO GRABADO MISTILITE - MINIBOREAL 4 MM', 'PRD-000029', NULL, 0.00, 0, 1, NULL, NULL, '183*244', NULL, '2026-01-03 20:52:02', '2026-01-03 20:52:02');

-- --------------------------------------------------------

--
-- Table structure for table `product_warehouse_stock`
--

CREATE TABLE `product_warehouse_stock` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salidas`
--

CREATE TABLE `salidas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `salida_number` varchar(255) NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha` date NOT NULL,
  `a_nombre_de` varchar(255) NOT NULL,
  `nit_cedula` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `aprobo` varchar(255) DEFAULT NULL,
  `ciudad_destino` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salida_products`
--

CREATE TABLE `salida_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `salida_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `container_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer_orders`
--

CREATE TABLE `transfer_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_from_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_to_id` bigint(20) UNSIGNED NOT NULL,
  `salida` varchar(255) DEFAULT NULL,
  `destino` varchar(255) DEFAULT NULL,
  `order_number` varchar(255) NOT NULL,
  `status` varchar(255) DEFAULT 'en_transito',
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL,
  `aprobo` varchar(255) DEFAULT NULL,
  `ciudad_destino` varchar(255) DEFAULT NULL,
  `driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer_order_products`
--

CREATE TABLE `transfer_order_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transfer_order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `good_sheets` int(11) DEFAULT NULL COMMENT 'Láminas en buen estado recibidas',
  `bad_sheets` int(11) DEFAULT NULL COMMENT 'Láminas en mal estado recibidas',
  `container_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(255) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `almacen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol` varchar(255) DEFAULT 'clientes',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `nombre_completo`, `telefono`, `almacen_id`, `rol`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'gerencia@vidriosjyp.com', 'gerencia@vidriosjyp.com', NULL, '$2y$10$MCKVAasC9eX.aM3Q1gc4e.3KYnL8rpM/SZRWmTxr651mKNj.2Hs1G', 'PABLO ANDRES ROJAS', '3173049853', NULL, 'admin', NULL, '2025-12-30 16:54:12', '2026-01-03 01:52:06'),
(9, 'vidriomaster.sas@hotmail.com', 'vidriomaster.sas@hotmail.com', NULL, '$2y$10$CtVtjdG8e7EFxsA4m74Dn.FO3fmj2SboK420AQQ0Nkh0MAQDTanOm', 'VIDRIO MASTER COLOMBIA', '3138353922', NULL, 'clientes', NULL, '2026-01-02 03:33:42', '2026-01-03 02:25:47'),
(11, 'vidriosjyp@gmail.com', 'vidriosjyp@gmail.com', NULL, '$2y$10$JdzGYRkHcsIU9ZmwhfPfUuvWxUQytsiIijl0YuZblPBbSEuBd18XO', 'GHEIDY YOHANA ISAZA', '3135250178', NULL, 'funcionario', NULL, '2026-01-02 15:12:31', '2026-01-03 15:39:01'),
(12, 'gerencia@serviglassgirardot.com', 'gerencia@serviglassgirardot.com', NULL, '$2y$10$g5uFgXhjdN1PpV9n7nAKDeEAKZnqsFCBm4qLXTSKvJVSdyodZ9jLy', 'MARIO FERNANDO DOMINGUEZ PRIETO', '3138349415', NULL, 'clientes', NULL, '2026-01-02 16:28:08', '2026-01-03 02:11:37'),
(13, 'funcionario@local.com', 'funcionario@local.com', NULL, '$2y$10$RZDJcVQd8CjM8k/4U4qaSeb0vGVfiEZry6ahVlYv4KBY25ACSgCyi', 'test funcionario', '56635464563', NULL, 'funcionario', NULL, '2026-01-03 14:27:21', '2026-01-03 14:27:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_warehouse`
--

CREATE TABLE `user_warehouse` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_warehouse`
--

INSERT INTO `user_warehouse` (`id`, `user_id`, `warehouse_id`, `created_at`, `updated_at`) VALUES
(11, 9, 15, '2026-01-02 03:33:42', '2026-01-02 03:33:42'),
(12, 9, 14, '2026-01-02 03:33:42', '2026-01-02 03:33:42'),
(13, 11, 16, '2026-01-02 15:12:31', '2026-01-02 15:12:31'),
(14, 11, 14, '2026-01-02 15:12:31', '2026-01-02 15:12:31'),
(16, 11, 25, '2026-01-03 02:09:43', '2026-01-03 02:09:43'),
(17, 11, 19, '2026-01-03 02:09:43', '2026-01-03 02:09:43'),
(18, 11, 24, '2026-01-03 02:09:43', '2026-01-03 02:09:43'),
(19, 11, 21, '2026-01-03 02:09:43', '2026-01-03 02:09:43'),
(20, 11, 17, '2026-01-03 02:09:43', '2026-01-03 02:09:43'),
(21, 12, 22, '2026-01-03 02:11:37', '2026-01-03 02:11:37'),
(22, 12, 21, '2026-01-03 02:11:37', '2026-01-03 02:11:37'),
(23, 12, 18, '2026-01-03 02:11:37', '2026-01-03 02:11:37'),
(24, 9, 18, '2026-01-03 02:25:47', '2026-01-03 02:25:47'),
(25, 9, 17, '2026-01-03 02:25:47', '2026-01-03 02:25:47'),
(26, 13, 25, '2026-01-03 14:27:21', '2026-01-03 14:27:21'),
(27, 13, 19, '2026-01-03 14:27:21', '2026-01-03 14:27:21'),
(28, 13, 16, '2026-01-03 14:27:21', '2026-01-03 14:27:21'),
(29, 13, 24, '2026-01-03 14:27:21', '2026-01-03 14:27:21'),
(30, 13, 21, '2026-01-03 14:27:21', '2026-01-03 14:27:21'),
(31, 13, 17, '2026-01-03 14:27:21', '2026-01-03 14:27:21'),
(32, 13, 14, '2026-01-03 14:27:21', '2026-01-03 14:27:21');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `nombre`, `direccion`, `ciudad`, `created_at`, `updated_at`) VALUES
(14, 'VIDRIO MASTER SC BUN', 'CR 66 12 SUR 104', 'BUENAVENTURA', '2026-01-02 03:31:58', '2026-01-02 03:31:58'),
(15, 'VIDRIO MASTER BOGOTA', NULL, 'BOGOTA', '2026-01-02 03:32:14', '2026-01-02 03:32:14'),
(16, 'CARDOVIDRIOS SC BUN', 'BUENAVENTURA', 'BUENAVENTURA', '2026-01-02 03:34:11', '2026-01-02 03:34:24'),
(17, 'VIDRIO MASTER OPERACIONES JC', NULL, 'BUENAVENTURA', '2026-01-03 01:56:37', '2026-01-03 01:56:37'),
(18, 'VIDRIO MASTER GIRARDOT', 'CRA 9 14 30', 'GIRARDOT', '2026-01-03 02:05:27', '2026-01-03 02:05:27'),
(19, 'CARDOVIDRIOS OPERACIONES JC', 'BUN', 'BUENAVENTURA', '2026-01-03 02:06:09', '2026-01-03 02:06:09'),
(20, 'CARDOVIDRIOS LTDA', 'BOGOTA', 'BOGOTA', '2026-01-03 02:06:23', '2026-01-03 02:06:23'),
(21, 'SERVIGLASS GIRARDOT OPERACIONES JC', 'BUN', 'BUENAVENTURA', '2026-01-03 02:06:49', '2026-01-03 02:06:49'),
(22, 'SERVIGLASS GIRARDOT', 'GIRARDOT', 'GIRARDOT', '2026-01-03 02:07:11', '2026-01-03 02:07:11'),
(23, 'ROBERTO PADILLA', 'BOGOTA', 'BOGOTA', '2026-01-03 02:07:36', '2026-01-03 02:07:36'),
(24, 'ROBERTO PADILLA OPERACIONES JC', 'BUENAVENTURA', 'BUENAVENTURA', '2026-01-03 02:07:55', '2026-01-03 02:07:55'),
(25, 'ARQUIDIAMANTE LOGISER 365', 'BUENAVENTURA', 'BUENAVENTURA', '2026-01-03 02:08:41', '2026-01-03 02:08:41'),
(26, 'ARQUIDIAMANTE BOGOTA', 'BOGOTA', 'BOGOTA', '2026-01-03 02:08:53', '2026-01-03 02:08:53'),
(27, 'PRESTAMO VIDRIOS LA FANIA OPERACIONES JC', 'BUN', 'BUENAVENTURA', '2026-01-03 22:15:24', '2026-01-03 22:15:24'),
(28, 'VENTA VIDRIOS DEL ORIENTE OPERACIONES JC', 'BUENAVENTURA', 'BUENAVENTURA', '2026-01-03 22:15:44', '2026-01-03 22:15:44');

-- --------------------------------------------------------

--
-- Table structure for table `imports`
--
DROP TABLE IF EXISTS `import_containers`;
DROP TABLE IF EXISTS `imports`;

CREATE TABLE `imports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `do_code` varchar(20) NOT NULL,
  `commercial_invoice_number` varchar(255) DEFAULT NULL,
  `proforma_invoice_number` varchar(255) DEFAULT NULL,
  `bl_number` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `origin` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `departure_date` date NOT NULL,
  `arrival_date` date DEFAULT NULL,
  `actual_arrival_date` date DEFAULT NULL COMMENT 'Fecha real de llegada',
  `received_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha y hora cuando se marcó como recibido',
  `status` varchar(255) DEFAULT 'pending',
  `files` text DEFAULT NULL,
  `credits` decimal(10,2) DEFAULT NULL,
  `proforma_pdf` varchar(255) DEFAULT NULL,
  `proforma_invoice_low_pdf` varchar(255) DEFAULT NULL,
  `invoice_pdf` varchar(255) DEFAULT NULL,
  `commercial_invoice_low_pdf` varchar(255) DEFAULT NULL,
  `bl_pdf` varchar(255) DEFAULT NULL,
  `packing_list_pdf` varchar(255) DEFAULT NULL,
  `apostillamiento_pdf` varchar(255) DEFAULT NULL,
  `other_documents_pdf` varchar(255) DEFAULT NULL,
  `shipping_company` varchar(255) DEFAULT NULL,
  `free_days_at_dest` int(11) DEFAULT NULL,
  `credit_time` enum('15','30','45') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `import_containers`
--

CREATE TABLE `import_containers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `import_id` bigint(20) UNSIGNED NOT NULL,
  `reference` varchar(255) NOT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `image_pdf_path` varchar(255) DEFAULT NULL COMMENT 'PDF con imágenes del contenedor',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `containers`
--
ALTER TABLE `containers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `containers_warehouse_id_foreign` (`warehouse_id`);

--
-- Indexes for table `container_product`
--
ALTER TABLE `container_product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_container_product` (`container_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_codigo_unique` (`codigo`),
  ADD KEY `almacen_id` (`almacen_id`);

--
-- Indexes for table `product_warehouse_stock`
--
ALTER TABLE `product_warehouse_stock`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `salidas`
--
ALTER TABLE `salidas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `salidas_salida_number_unique` (`salida_number`),
  ADD KEY `salidas_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `salidas_user_id_foreign` (`user_id`),
  ADD KEY `salidas_driver_id_foreign` (`driver_id`);

--
-- Indexes for table `salida_products`
--
ALTER TABLE `salida_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salida_products_salida_id_foreign` (`salida_id`),
  ADD KEY `salida_products_product_id_foreign` (`product_id`),
  ADD KEY `salida_products_container_id_foreign` (`container_id`);

--
-- Indexes for table `transfer_orders`
--
ALTER TABLE `transfer_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `warehouse_from_id` (`warehouse_from_id`),
  ADD KEY `warehouse_to_id` (`warehouse_to_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `transfer_order_products`
--
ALTER TABLE `transfer_order_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transfer_order_id` (`transfer_order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `container_id` (`container_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `almacen_id` (`almacen_id`);

--
-- Indexes for table `user_warehouse`
--
ALTER TABLE `user_warehouse`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_warehouse_user_id_warehouse_id_unique` (`user_id`,`warehouse_id`),
  ADD KEY `user_warehouse_warehouse_id_foreign` (`warehouse_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `imports`
--
ALTER TABLE `imports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `imports_do_code_unique` (`do_code`),
  ADD KEY `imports_user_id_foreign` (`user_id`);

--
-- Indexes for table `import_containers`
--
ALTER TABLE `import_containers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `import_containers_import_id_foreign` (`import_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `containers`
--
ALTER TABLE `containers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `container_product`
--
ALTER TABLE `container_product`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `product_warehouse_stock`
--
ALTER TABLE `product_warehouse_stock`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salidas`
--
ALTER TABLE `salidas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `salida_products`
--
ALTER TABLE `salida_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transfer_orders`
--
ALTER TABLE `transfer_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transfer_order_products`
--
ALTER TABLE `transfer_order_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_warehouse`
--
ALTER TABLE `user_warehouse`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `imports`
--
ALTER TABLE `imports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_containers`
--
ALTER TABLE `import_containers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `containers`
--
ALTER TABLE `containers`
  ADD CONSTRAINT `containers_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `container_product`
--
ALTER TABLE `container_product`
  ADD CONSTRAINT `container_product_ibfk_1` FOREIGN KEY (`container_id`) REFERENCES `containers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `container_product_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`almacen_id`) REFERENCES `warehouses` (`id`);

--
-- Constraints for table `salidas`
--
ALTER TABLE `salidas`
  ADD CONSTRAINT `salidas_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `salidas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `salidas_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`);

--
-- Constraints for table `salida_products`
--
ALTER TABLE `salida_products`
  ADD CONSTRAINT `salida_products_container_id_foreign` FOREIGN KEY (`container_id`) REFERENCES `containers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `salida_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salida_products_salida_id_foreign` FOREIGN KEY (`salida_id`) REFERENCES `salidas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transfer_orders`
--
ALTER TABLE `transfer_orders`
  ADD CONSTRAINT `transfer_orders_ibfk_1` FOREIGN KEY (`warehouse_from_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `transfer_orders_ibfk_2` FOREIGN KEY (`warehouse_to_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `transfer_orders_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transfer_order_products`
--
ALTER TABLE `transfer_order_products`
  ADD CONSTRAINT `transfer_order_products_ibfk_1` FOREIGN KEY (`transfer_order_id`) REFERENCES `transfer_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transfer_order_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transfer_order_products_ibfk_3` FOREIGN KEY (`container_id`) REFERENCES `containers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`almacen_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_warehouse`
--
ALTER TABLE `user_warehouse`
  ADD CONSTRAINT `user_warehouse_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_warehouse_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `imports`
--
ALTER TABLE `imports`
  ADD CONSTRAINT `imports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `import_containers`
--
ALTER TABLE `import_containers`
  ADD CONSTRAINT `import_containers_import_id_foreign` FOREIGN KEY (`import_id`) REFERENCES `imports` (`id`) ON DELETE CASCADE;

-- Reactivar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
