-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 25, 2026 at 12:39 PM
-- Server version: 10.11.15-MariaDB-cll-lve
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


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
(16, 'CRSU1450396', 14, 'INV VIEJO', NULL, NULL),
(17, 'ECMU2849972', 14, 'INV VIEJO', NULL, NULL),
(18, 'SEGU1789818', 14, 'INV VIEJO', NULL, NULL),
(19, 'APZU3262209', 14, 'INV VIEJO', NULL, NULL),
(20, 'TIIU2822109', 14, 'INV VIEJO', NULL, NULL),
(21, 'FCIU5309271', 17, 'INV VIEJO', NULL, NULL),
(22, 'FCIU530927-1', 21, 'INV VIEJO', NULL, NULL),
(23, 'FTAU1567690', 17, 'INV VIEJO', NULL, NULL),
(24, 'MEDU5405977', 17, 'INV VIEJO', NULL, NULL),
(25, 'MSMU2632498', 17, 'INV VIEJO INGRESO COMO MILKY PERO LLEGO ETIQUETAS TROCADAS', NULL, NULL),
(26, 'MRSU4281860', 19, 'INV VIEJO', NULL, NULL),
(27, 'MSKU0448388', 19, 'INV VIEJO', NULL, NULL),
(28, 'MSMU3715724', 17, 'INV VIEJO', NULL, NULL),
(29, 'MSMU371572-4', 29, 'INV VIEJO', NULL, NULL),
(30, 'BEAU2963840', 17, 'INV VIEJO', NULL, NULL),
(31, 'MRKU71384222', 17, 'INV VIEJO', NULL, NULL),
(32, 'FCIU530927/1', 28, 'INV VIEJO', NULL, NULL),
(33, 'STOCK GIRARDOT', 17, 'INGRESO STOCK MASTER BUN', NULL, NULL),
(34, 'STOCK GIRARDOTT', 17, 'INGRESO STOCK GIRARDO', NULL, NULL),
(35, 'STOCK GIRARDOTTT', 17, 'INV VIDRIOS', NULL, NULL),
(36, 'HASU4977415', 31, 'ENDOSO ZULUVIDRIOS', NULL, NULL),
(37, 'CAIU3549383', 31, 'VENTA PABLO ROJAS A ZULUVIDRIOS 220.000 LAMINA', NULL, NULL),
(38, 'CAIU3490557', 33, 'DO: VJP26-021  MATERIAL RLW GLASS TEMPLADOS LA TORRE', NULL, NULL),
(39, 'CMAU026715/7', 28, 'VENTA DE DO: 26-001 BL: TJN0773287', NULL, NULL),
(40, 'CMAU0267157', 17, 'DO VJP26-001 INGRESO', NULL, NULL),
(41, 'TCLU735981-3', 28, 'DO VJP26-001 VENTA A ELIECER', NULL, NULL),
(42, 'TCLU7359813', 17, 'DO: VJP26-001', NULL, NULL),
(43, 'SUDU1368362', 21, 'DO: VJP26-011', NULL, NULL),
(44, 'MSKU5652671', 17, 'DO: VJP26-012', NULL, NULL),
(45, 'MSMU2003772', 17, 'DO: VJP26-009', NULL, NULL),
(46, 'PR VIEJO', 29, 'VENTA OLGA HORTENCIA TUPAZ', NULL, NULL),
(47, 'INV VIEJO PR', 29, 'INV VIEJO VENTA Y PRESTAMO A EL MAYORISTA', NULL, NULL);

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
  `weight_per_box` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `container_product`
--

INSERT INTO `container_product` (`id`, `container_id`, `product_id`, `boxes`, `sheets_per_box`, `created_at`, `updated_at`) VALUES
(16, 16, 33, 0, 15, '2026-01-10 19:55:35', '2026-01-10 19:55:35'),
(17, 17, 33, 0, 15, '2026-01-10 19:56:15', '2026-01-10 19:56:15'),
(18, 18, 34, 7, 25, '2026-01-10 19:57:26', '2026-01-10 19:57:26'),
(19, 19, 35, 0, 40, '2026-01-10 20:00:17', '2026-01-10 20:00:17'),
(20, 20, 36, 0, 19, '2026-01-10 20:10:44', '2026-01-10 20:10:44'),
(21, 21, 37, 1, 31, '2026-01-11 00:37:15', '2026-01-16 19:33:52'),
(22, 22, 37, 1, 31, '2026-01-11 00:37:51', '2026-01-16 19:33:40'),
(23, 23, 38, 10, 31, '2026-01-11 00:58:44', '2026-01-11 00:58:44'),
(24, 24, 39, 5, 25, '2026-01-11 01:00:56', '2026-01-11 01:00:56'),
(25, 25, 41, 4, 25, '2026-01-11 01:05:05', '2026-01-11 01:05:05'),
(26, 26, 43, 10, 18, '2026-01-11 01:07:44', '2026-01-11 01:07:44'),
(27, 27, 42, 4, 24, '2026-01-11 01:08:53', '2026-01-11 01:08:53'),
(28, 28, 44, 4, 60, '2026-01-11 01:13:49', '2026-01-11 01:13:49'),
(29, 29, 44, 2, 60, '2026-01-11 01:15:21', '2026-01-11 01:15:21'),
(30, 30, 48, 10, 30, '2026-01-16 19:27:07', '2026-01-16 19:27:07'),
(31, 31, 50, 2, 38, '2026-01-16 19:27:43', '2026-01-16 19:27:43'),
(32, 32, 37, 2, 31, '2026-01-16 19:34:54', '2026-01-16 19:34:54'),
(33, 33, 52, 0, 38, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(34, 33, 46, 0, 57, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(35, 33, 47, 0, 46, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(36, 33, 48, 0, 30, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(37, 33, 49, 0, 26, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(38, 33, 50, 0, 38, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(39, 33, 51, 0, 30, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(40, 33, 42, 0, 23, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(41, 33, 43, 0, 16, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(42, 33, 57, 0, 33, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(43, 33, 36, 0, 19, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(44, 33, 39, 0, 25, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(45, 33, 34, 0, 25, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(46, 33, 53, 0, 35, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(47, 33, 55, 0, 31, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(48, 33, 54, 0, 28, '2026-01-16 19:57:18', '2026-01-16 19:57:18'),
(49, 34, 61, 0, 38, '2026-01-16 20:02:56', '2026-01-16 20:02:56'),
(50, 34, 56, 0, 17, '2026-01-16 20:02:56', '2026-01-16 20:02:56'),
(51, 34, 60, 0, 25, '2026-01-16 20:02:56', '2026-01-16 20:02:56'),
(52, 34, 59, 0, 19, '2026-01-16 20:02:56', '2026-01-16 20:02:56'),
(53, 35, 63, 0, 38, '2026-01-16 20:19:28', '2026-01-16 20:19:28'),
(54, 35, 58, 0, 22, '2026-01-16 20:19:28', '2026-01-16 20:19:28'),
(55, 36, 64, 4, 29, '2026-01-19 23:12:00', '2026-01-19 23:12:00'),
(56, 36, 45, 6, 36, '2026-01-19 23:12:00', '2026-01-19 23:12:00'),
(57, 37, 60, 2, 25, '2026-01-19 23:13:45', '2026-01-19 23:13:45'),
(58, 38, 67, 10, 14, '2026-01-21 20:09:03', '2026-01-21 20:09:03'),
(59, 39, 65, 3, 26, '2026-01-21 22:24:39', '2026-01-25 17:35:48'),
(60, 40, 65, 4, 25, '2026-01-21 22:25:25', '2026-01-21 22:26:34'),
(61, 41, 66, 5, 26, '2026-01-21 22:27:48', '2026-01-21 22:27:48'),
(62, 42, 66, 2, 26, '2026-01-21 22:28:27', '2026-01-25 17:26:26'),
(63, 43, 50, 10, 38, '2026-01-21 23:02:35', '2026-01-21 23:02:35'),
(64, 44, 50, 10, 38, '2026-01-21 23:03:37', '2026-01-21 23:03:37'),
(65, 45, 34, 10, 25, '2026-01-21 23:05:39', '2026-01-21 23:05:39'),
(66, 46, 60, 0, 25, '2026-01-22 14:09:52', '2026-01-22 14:10:41'),
(67, 46, 48, 2, 30, '2026-01-22 14:09:52', '2026-01-22 14:10:41'),
(68, 46, 34, 2, 25, '2026-01-22 14:09:52', '2026-01-22 14:10:41'),
(69, 46, 61, 1, 38, '2026-01-22 14:09:52', '2026-01-22 14:10:41'),
(70, 46, 63, 1, 38, '2026-01-22 14:09:52', '2026-01-22 14:10:41'),
(71, 46, 50, 1, 38, '2026-01-22 14:09:52', '2026-01-22 14:10:41'),
(72, 47, 68, 1, 23, '2026-01-22 15:59:48', '2026-01-22 15:59:48'),
(73, 47, 67, 1, 13, '2026-01-22 15:59:48', '2026-01-22 15:59:48');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `identity` varchar(20) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `vehicle_plate` varchar(20) NOT NULL,
  `vehicle_photo_path` varchar(255) DEFAULT NULL,
  `social_security_date` date DEFAULT NULL COMMENT 'Fecha de seguridad social',
  `social_security_pdf` varchar(255) DEFAULT NULL COMMENT 'PDF de seguridad social',
  `vehicle_owner` varchar(255) DEFAULT NULL COMMENT 'Propietario del vehículo',
  `capacity` decimal(10,2) DEFAULT NULL COMMENT 'Capacidad de carga en kg',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `name`, `identity`, `phone`, `photo_path`, `vehicle_plate`, `vehicle_photo_path`, `social_security_date`, `social_security_pdf`, `vehicle_owner`, `active`, `created_at`, `updated_at`) VALUES
(7, 'FABIAN VIVAS PARADA', '1115911364', '3206615093', 'drivers/lV2DKsTjA5YkVeV7leBwUDQMr9DqKOVxg7ZjTinZ.jpg', 'NYO322', 'drivers/vJDt5trPbQA4XclsOdcqtDIIknj5sokThGu1SWy6.jpg', '2026-02-13', 'drivers/1Nu3c7huzSjzqvGyM0QWRmWOEP21vubER4uVKZCr.pdf', 'PABLO ANDRES ROJAS CC1111806533', 1, NULL, NULL),
(8, 'CESAR VANEGASS SERRANO', '79916566', '311459996', NULL, 'WOM166', NULL, '2026-02-09', 'drivers/pyTzD3zEduDcNlmLzAuxppjEZcPMGtQqq81ZItBH.pdf', 'PABLO ANTONIO RIOS SUAREZ CC 13905229', 1, NULL, NULL),
(9, 'NICOLAS CANO', '1007530763', '3145656161', 'drivers/YJqQpOPDxZ3H7XCngC8H2mGWtFwFQ0FIJSmtlTBV.jpg', 'FST609', 'drivers/8KGfq2x6zCE2w5v5Yn5nkSIS2kJcaq0nxlAE83jo.jpg', '2026-02-09', 'drivers/ifG33NBQwGagyACOFoefAqKFHPPbkKcl2AEQhNMy.pdf', 'LA ÉLITE COMPRAVENTA Y TRANSPORTE', 1, NULL, NULL),
(10, 'WILLIAM TORRES', '79328216', '3017858368', NULL, 'CTU437', NULL, NULL, NULL, 'TEMPLADOS LA TORRE', 1, NULL, NULL),
(11, 'OSWALDO RENE PATINO  CONTRERAS', '87716645', '3164527942', NULL, 'GUB993', 'drivers/zXqXEspgTAEtclldHsab0nbXYmhUkmGDX4fl1oRO.jpg', '2026-02-10', NULL, 'OLGA HORTENCIA TUPAZ CC27249825', 1, NULL, NULL),
(12, 'Álvaro Contreras Alvarino', '1067290321', '3102595515', 'drivers/heRlSjjs75xRSc3uzHuHnfphvMh0G95JbGrWVw6R.jpg', 'SOO040', 'drivers/PY6XdmRzeufMt6KBdi7A4ZgOXvD0xHh95MiemlhC.jpg', NULL, NULL, 'VIDRIO MASTER COLOMBIA', 1, NULL, NULL),
(13, 'OSCAR YESID BARRERA TOVAR', '1032421541', '3147474739', NULL, 'TRK141', NULL, NULL, NULL, 'VIDRIO MASTER COLOMBIA', 1, NULL, NULL),
(14, 'NELSON BELTRAN RAMIREZ', '3154000', '3208531098', NULL, 'SPQ171', NULL, NULL, NULL, 'VIDRIO MASTER COLOMBIA', 1, NULL, NULL),
(15, 'IVAN CASTIBLANCO PIRAQUIVE', '1076646493', '3227420098', NULL, 'THW303', NULL, NULL, NULL, 'VIDRIO MASTER COLOMBIA', 1, NULL, NULL),
(16, 'VICTOR MANUEL LIMAS CORDOBA', '80865528', '3219958615', NULL, 'SZQ350', NULL, NULL, NULL, 'VIDRIO MASTER COLOMBIA', 1, NULL, NULL),
(17, 'JESUS MANUEL MOLINA DUARTE', '5733396', '3203801626', NULL, 'WGY977', NULL, NULL, NULL, 'VIDRIO MASTER COLOMBIA', 1, NULL, NULL),
(18, 'OSCAR ADRIAN PINZON CASTILLO', '1007586197', '3238061594', 'drivers/pB86q499rM3PcjXgDdNTLbtw0sIiOHd5jCCrMJpq.jpg', 'WLY515', 'drivers/ErkYrB6XQNUnfeVod0LyPszXl6qLGbpPSQcRyB1q.jpg', NULL, NULL, 'VIDRIO MASTER COLOMBIA', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `imports`
--

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
  `nationalized` tinyint(1) NOT NULL DEFAULT 0,
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
  `credit_paid` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el crédito ha sido pagado',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `imports`
--

INSERT INTO `imports` (`id`, `do_code`, `commercial_invoice_number`, `proforma_invoice_number`, `bl_number`, `user_id`, `origin`, `destination`, `departure_date`, `arrival_date`, `actual_arrival_date`, `received_at`, `status`, `nationalized`, `files`, `credits`, `proforma_pdf`, `proforma_invoice_low_pdf`, `invoice_pdf`, `commercial_invoice_low_pdf`, `bl_pdf`, `packing_list_pdf`, `apostillamiento_pdf`, `other_documents_pdf`, `shipping_company`, `free_days_at_dest`, `credit_time`, `credit_paid`, `created_at`, `updated_at`) VALUES
(2, 'VJP26-002', 'COL001251006-4', 'COL001251006', 'DLN0262195', 14, 'QINGDAO', 'Colombia', '2025-12-09', '2026-01-13', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/ZS6aH7E32WwKRvDEt0oBNAWJiWnsCJwtwoswssGy.pdf', 'imports/W7Mlz7VzxsF8Pb1ODEfrzUWQWkDfIlGe9NyQsgRe.pdf', 'imports/n5aeLztNaxnRuSTXyHSZWbr3Rv3Syx0zIZ2tsUQk.pdf', 'imports/AyIZPDJ5dkNsSrgIgAfN6QqRWUDCTqUamQiyFHP9.pdf', 'imports/IEqoJuiZtXuMY8ON0ylY4RvMSyGfhglLoFueN4ol.pdf', 'CMA-CGM', 21, '45', 0, '2026-01-10 22:22:15', '2026-01-21 01:00:43'),
(3, 'VJP26-003', 'JPSA007-251020', NULL, 'TJN0803976', 17, 'QINGDAO', 'Colombia', '2025-11-23', '2026-01-06', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, NULL, NULL, NULL, NULL, 'imports/wrKYZK3HJ69y1EdSLuq8JJX5TpUUGOVCcW2sjINJ.pdf', 'imports/KFdNvF75Tv1UGsSHk0yDwYRsGfJJqTi1XMDZpjLQ.pdf', 'imports/cnnFu8tT59n1QXWMHWPRHLI6LLBFBT3M8oH0epZ5.pdf', 'imports/7D6UA6II2l6J9f8OjEv3SV9HLo7p3j8caRAQhePb.pdf', 'imports/YkuvxVEEdkeoxCrKkbyu3H8d1wmvqCp81AvkvEdS.pdf', 'CMA-CGM', 21, NULL, 0, '2026-01-11 03:09:59', '2026-01-21 04:47:47'),
(4, 'VJP26-004', 'COL001251006-5', 'COL001251006', 'MEDUHW415929', 14, 'DALIAN', 'Colombia', '2025-11-22', '2026-01-06', '2026-01-15', '2026-01-21 04:34:26', 'recibido', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/yFBwzU1Oa27PxAeGlekxGdOoPANe0GXFjIC1lKQV.pdf', 'imports/ZuVZYW7BZuKueCWp3VwbsGSJdkvncB4pvPlr6Fqx.pdf', 'imports/juuSgDn8M5l6x5F4AehYNTjgTj5Uwxr0cyEKNh0b.pdf', 'imports/rDtT3wELzGTTd6p95DqqjXHXsvphUEpzLbaQzj6C.pdf', 'imports/uo0G8Rz5bOs0yhQA9Ez0q5S8oK2GYYGlWCEVMt7A.pdf', 'MSC', 21, '45', 0, '2026-01-11 03:18:29', '2026-01-21 04:34:26'),
(5, 'VJP26-005', 'COL001251006-6', 'COL001251006', 'MEDUHW415911', 14, 'DALIAN', 'Colombia', '2025-11-22', '2026-01-06', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/9kozUV4I35li07VFkW14TneitS31VlKl3ys78WY8.pdf', 'imports/3y08dbtQKsWZRpOxtRVQ0AEFueipZ5u3BAulsjkN.pdf', 'imports/pl7TS9N4PjCzUQEJdurrl4OrQ01vAdEJkX2N3mXC.pdf', 'imports/faGJouWvwL07pveMlrcBXar5qXUPAHPJhUieELrl.pdf', 'imports/67pynXXE5m46b6qtjbB6ceCxHbttChB3QWjDWjNL.pdf', 'MSC', 21, '45', 0, '2026-01-11 03:28:04', '2026-01-21 01:03:40'),
(6, 'VJP26-006', 'COL001251006-7', 'COL001251006', 'MEDUHW415903', 14, 'DALIAN', 'Colombia', '2025-11-22', '2026-01-06', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/FqvWqEiYjIuxYai8d4hLGewjkXf1XUmy68BnwN6X.pdf', 'imports/9oT3XRKpqrVAvxcsqg84XDezN1IhC044h1xZSP4o.pdf', 'imports/BQHWvRPM4pxAlfPFkHBmDWlA00wDKgTW3a643snO.pdf', 'imports/0jnWdGTXqofP9eEqoQBgF9R3r35aMglFMKiXX31e.pdf', 'imports/AdNvdkql0tACXXdVswqEnUj6jHlpDm2NAgVNnBSa.pdf', 'MSC', 21, '45', 0, '2026-01-11 03:31:55', '2026-01-21 01:05:10'),
(7, 'VJP26-007', 'COL001251006-8', 'COL001251006', 'MEDUHW415887', 14, 'DALIAN', 'Colombia', '2025-11-22', '2026-01-06', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/2C2igxzpjIrTmXcyh4OxlFddzccMSUV1Zn76sUUL.pdf', 'imports/5urlwGCYpTEHgeoHAbP5pQ464YNmwJVa9z4oQaiP.pdf', 'imports/aaqBBqMYiSAXmjSHfcWrl0JtcaqUGMFM8ypUUj5p.pdf', 'imports/7VOq8nTfoWTEiyjLK7xWLqIelcHtrqspI7A3jlWv.pdf', 'imports/aFDsUP1vdKe6frAPhkkuXkT1j8ctk2llusxOsCie.pdf', 'MSC', 21, '45', 1, '2026-01-11 03:34:46', '2026-01-21 04:51:24'),
(8, 'VJP26-008', 'COL001251006-9', 'COL001251006', 'MEDUHW458788', 14, 'DALIAN', 'Colombia', '2025-11-22', '2026-01-14', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/pZo2EM3lB6g0EbtfDOiAJMCKHANr8X15H5iWmiut.pdf', 'imports/Q2qDVSXH6ZsscF3rNMiobXceZZ0DoEzSaQaBbwXt.pdf', 'imports/lg2l13zlw6vQ5w7MBXpmYTUXOWl8CjIva2JPmAUk.pdf', 'imports/FIMirhY7Hwe3I5liGqVgJaw1osERx8cOX3keUqbl.pdf', 'imports/Cd102QaFNc94lsaT9Vbgxf8YI69scRaCY9P4xrwZ.pdf', 'MSC', 21, '45', 0, '2026-01-11 03:38:45', '2026-01-21 04:52:47'),
(9, 'VJP26-009', 'COL001251006-10', 'COL001251006', 'MEDUHW458770', 14, 'DALIAN', 'Colombia', '2025-12-01', '2026-01-14', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/LL05GB5r0kcR5yOYV6wj73pdAM419C7jjJ4aIHlS.pdf', 'imports/yehO2LPqHBSCB6p4OiPwUrG9uRVmbDeKt98SKJSV.pdf', 'imports/kvpmHi7mRFy40wq8UggA7nHd1ina77GQ08SHaO58.pdf', 'imports/S5GFCQIspxhNswizwwRAEoYlmqG171X0FtBH5hI8.pdf', 'imports/ukORkw5FSPdZYVfOvzJvcAZCrzE2SSjbOXBtALgl.pdf', 'MSC', 21, '45', 0, '2026-01-11 03:43:37', '2026-01-15 04:20:39'),
(10, 'VJP26-010', 'COL001250904-1', 'COL001250904', 'QGD2281845', 14, 'QINGDAO', 'Colombia', '2025-11-29', '2026-01-07', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/UwMYf8z2vu5JoknqIQ7s0r5hNwVWoqhrQRnJ76Mx.pdf', 'imports/AskBHJdFan6Tz0HiTbUyCfq3T8pHKGrcFlIFMsJY.pdf', 'imports/kngEvXdwt2CXhtPzAac0Ccd3QYG00S8181S3UZHr.pdf', 'imports/bk40zlMhoHWHbUcTcRj4WzHjaaTXClESJnw7Spnl.pdf', 'imports/BBEEsigLUiiap8gscGLjSYQ62g3LgsghJaPsfNEy.pdf', 'CMA-CGM', 21, '45', 0, '2026-01-11 03:47:43', '2026-01-21 04:55:28'),
(11, 'VJP26-011', 'COL001251004-1', 'COL001251004', '261482941', 14, 'APAPA NIGERIA', 'Colombia', '2025-11-12', '2026-01-11', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/JBKQO18AGQazfuVEq805hnua1cT7ndcvLuCCrCKy.pdf', 'imports/pe7Is4ZMcAVOy4NOEzORhRmo51biAKUAg5zHyv8t.pdf', 'imports/8hi6HzfRXfkzE8kOb4xjoCqEapVP13ltqFBYelMv.pdf', 'imports/xZDBMYyRxq0vG4zbtSGgRbHZcLWxojNfq97406BT.pdf', 'imports/WY7FmF1txWdXMftJOJ32w7sOVDW8yaVE5F8Iy1KV.pdf', 'LOGISTICS SOLUTIONS', 21, '45', 0, '2026-01-11 21:45:20', '2026-01-21 04:56:51'),
(12, 'VJP26-012', 'COL001251004-2', 'COL001251004', '261483451', 14, 'APAPA NIGERIA', 'Colombia', '2025-11-12', '2026-01-11', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/swekMYs2ZXk1WHi4dNRgCyFd0bDQZVh0uw0ohRYG.pdf', 'imports/bj35CpHn08dqfQCgu75FK0FOG82YPCSva6oFB5Pk.pdf', 'imports/6rnqivI0SHrTch17lEwOVHC1mT79U2cAF8WBHZMO.pdf', 'imports/G60Eq8qU11SkyHsQBqTgKLTa6R7jsDONJ2dim9ny.pdf', 'imports/fHKdsT91k3xFxejXXO0wHFJpGMxOckfq9aaBVG6O.pdf', 'LOGISTICS SOLUTIONS', 21, '45', 0, '2026-01-11 21:49:53', '2026-01-21 04:58:18'),
(13, 'VJP26-013', 'COL001251004-3', 'COL001251004', '261483687', 14, 'APAPA NIGERIA', 'Colombia', '2025-11-12', '2026-01-11', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, 'imports/uN8or3bUe0iBoXiS0bl6cpKHx0jIbVqwgIRsFPSc.pdf', 'imports/9VsCWkFzLr9jxyiPe9TrjMovzzf5bhvPgCGwsKcv.pdf', 'imports/oH4Sgp5BBIsKtzd6LlpOKLAwufkV6NKuhKPEmsR6.pdf', 'imports/FODU1TrMDJ4xxpWzsb0zi02IriACPZpJG2gJyJYM.pdf', 'imports/L7dxi8pMa923DTpL9LZRfMHPQzTgbUx5chdRca8a.pdf', 'LOGISTICS SOLUTIONS', 21, '45', 0, '2026-01-11 21:52:55', '2026-01-21 04:59:37'),
(14, 'VJP26-014', 'RE2510101-1', 'CO9620508', 'AYN1289232', 18, 'QINGDAO', 'Colombia', '2025-12-17', '2026-01-20', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 30.00, NULL, NULL, NULL, 'imports/JCR9jjZOGu4Z5Wjc4iwkhAlmDOq2AHR4xsQjxVgC.pdf', 'imports/HSaKt4i8TRuF7wxlXbymxQtEWJgLs4yceezIstAQ.pdf', 'imports/vwVI5jI161iLXrIbsv3VLdclqB6MnxUSV5jtbbc7.pdf', 'imports/EHkUJgs87T2d3jacbkEff0BYY3HT5INMzIvrUPfX.pdf', NULL, 'CMA-CGM', 21, '30', 0, '2026-01-11 22:00:27', '2026-01-21 05:02:23'),
(15, 'VJP26-015', 'RE2509196-1', 'CO9620506', 'TJN0826454', 18, 'TIANJIN', 'Colombia', '2026-01-01', '2026-02-16', NULL, NULL, 'pending', 0, NULL, 30.00, NULL, NULL, NULL, 'imports/3sdJYcyJ5kwt3FnGjYHH1Ew8P44T7EABmfzlaACa.pdf', 'imports/uA4NJUrbAfHF0bJANA9bYoill1QWpCPEYfwP4F2r.pdf', 'imports/r00gfxevK0G6rprcBkd2YcABVVSQupOUvL60iiXc.pdf', NULL, NULL, 'CMA-CGM', 21, '30', 0, '2026-01-11 22:05:43', '2026-01-21 05:03:32'),
(16, 'VJP26-016', 'RG-18890/25', 'RGP06852/25', 'TJN0822029', 19, 'TIANJIN', 'Colombia', '2025-12-17', '2026-01-20', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, NULL, NULL, NULL, 'imports/JgUuaaEyN4PmClfm76u5QNGBTBxOXOs2txXdUnDj.pdf', NULL, 'imports/py62WW2oTVdcEzAoP73uWG5vcvbOQYxXhWMZW7F3.pdf', 'imports/SMkqL9Q0RA8Fa2EbngBVVpoF6eSJRqttLPQmg6qC.pdf', NULL, NULL, 'CMA-CGM', 21, NULL, 0, '2026-01-11 22:12:56', '2026-01-21 05:07:21'),
(17, 'VJP26-017', 'RG-17981/25', 'RGP-06852/25', 'TJN0821851', 19, 'TIANJIN', 'Colombia', '2025-12-17', '2026-01-20', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, NULL, NULL, NULL, 'imports/bCPChe8xCPp5Xf78tWQPUvinxwltNdD8xKpRDPZD.pdf', NULL, 'imports/I6fhMiOHFCqCbSpvvcR8QAJ5gA57JvHSdsk3pwrZ.pdf', 'imports/QYz9wEe8eGNor4kOeYFzDf6cxdy7edZdVe8i2fud.pdf', NULL, NULL, 'CMA-CGM', 21, NULL, 0, '2026-01-11 22:18:51', '2026-01-21 05:09:04'),
(18, 'VJP26-018', 'TZKY250927', NULL, 'CNSAC2511100', 20, 'QINGDAO', 'Colombia', '2025-12-07', '2026-01-05', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 30.00, NULL, NULL, NULL, 'imports/BTQ7nai3lYQz5ZQPu9auP7ymyYCbl2zSeoiZYzce.pdf', 'imports/Hi9oHiT5963MFKdkUMq0BkXKLNjRn56kW41fJWWS.pdf', 'imports/gLOzGGtPZ8irKT4ukku4HSdCKJQUdRMmrdinCKMI.pdf', 'imports/eC7chuI43AEUrUBVqcjYNlVbQLIeSbzmkclCyQaE.pdf', NULL, 'LOGISTICS SOLUTIONS', 18, '30', 0, '2026-01-11 22:27:35', '2026-01-21 05:15:25'),
(19, 'VJP26-019', 'FGQXE20251015JU-JPS04', 'FGQXE20251015JU-JPS03', 'NTJEC251210420', 21, 'XINGANG', 'Colombia', '2025-12-17', '2026-01-20', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, NULL, NULL, NULL, 'imports/HF803IAlDQATVlXs5JzYpvbU9VwtUxrjb6Qkjlj0.pdf', 'imports/42yDtXcUnhaFiGOqUvW55K4Oo5hINP0pac9deFgJ.pdf', 'imports/MMHdthgtKoz5psNuSg6a9M8GVTaHlJNrpqISC9hi.pdf', 'imports/6r1f17AXtGEeG5wnz9wC9Xr5fNWWRzkrErLmfeyd.pdf', NULL, NULL, 'LOGISTICS SOLUTIONS', 18, NULL, 0, '2026-01-12 03:38:56', '2026-01-21 05:31:18'),
(20, 'VJP26-020', 'FGQXE20250919JU-RLW01-2', 'FGQXE20250919JU-RLW01', '260975609', 21, 'TIANJIN', 'Colombia', '2025-11-25', '2026-01-01', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, NULL, NULL, NULL, 'imports/KGMYJJdPjmEIs2LNHZVtKeiZc6e2JPPlUfiK7sqE.pdf', NULL, 'imports/jvyOmCooMHYKtr3tG70gDJAPgL9ldiN7TthHEJ0i.pdf', 'imports/KhPxpIHEPbPIPAYsunhmkAiDr37WkFyKMLccO6Cb.pdf', NULL, NULL, 'MSK', 21, NULL, 0, '2026-01-15 02:48:03', '2026-01-15 04:20:39'),
(21, 'VJP26-021', 'FGQXE20250919JU-RLW01-1', 'FGQXE20250919JU-RLW01', 'TJN0782397', 21, 'TIANJIN', 'Colombia', '2026-01-01', '2026-01-14', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, NULL, NULL, NULL, 'imports/AsuF8Q40UeqGgG74PtEKAqmoB393QOzfQAHOzRFk.pdf', NULL, 'imports/r9Pc79sQevjmhAT7d8lVecvSjZMdrLfpHzLjUmWL.pdf', 'imports/xDq4sY6PrqlHG08bPYMS6OkFfSAb1WG3C34FWoFT.pdf', NULL, NULL, 'CMA-CGM', 21, NULL, 0, '2026-01-21 04:24:54', '2026-01-21 04:24:54'),
(22, 'VJP26-022', 'COL001251005-1', 'COL001251005', '261279366', 14, 'DALIAN', 'Colombia', '2026-01-01', '2026-01-01', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, 'imports/7AA4meQshzt3V2Qc195H8heSGpkpTFcvRc7waFST.pdf', NULL, 'imports/Vu1nnvf6Qe2tMEYVSN6zfZ7C2ECOBUPbOOpBUz9L.pdf', 'imports/ZSI7yoSBBZa4EZ4xKv6jcjZu75cyH5tu4rZQWqPG.pdf', NULL, NULL, 'MSK', 21, '45', 0, '2026-01-21 04:28:27', '2026-01-21 04:28:28'),
(23, 'VJP26-023', 'COL001250902', 'COL001250902', '258682843', 14, 'TIANJIN', 'Colombia', '2026-01-01', '2026-01-01', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, 'imports/IDuC1RUTdcw33ViqwjjuzlwmTPicRQEfs5JJ7lBh.pdf', NULL, NULL, NULL, 'imports/rWHAurelGmS7O1DpDrTbTKRuBYFcLP1sQj98ntWi.pdf', 'imports/CiFyyFjjDWmE2Q2hYgnfUcbRPCXyu9dHstY8yP48.pdf', NULL, NULL, 'MSK', 21, '45', 0, '2026-01-21 04:31:43', '2026-01-21 04:31:44'),
(24, 'VJP26-024', 'H50708251121&H50709251127CI', 'H50708251121&H50709251127', 'TJN0835264', 22, 'China', 'Colombia', '2025-12-25', '2026-02-01', NULL, NULL, 'pending', 0, NULL, NULL, 'imports/rhIHTWnOAAplN5kQvnWHMHBXvqRW1UgrGGtbYS6i.pdf', 'imports/3bOtU3LgQVuty0p9xZvwqhfrmOWJKeh7TIjrewk5.pdf', 'imports/HLHFEusybE0MLc0IBBKSiH0av4cDx4W89NUUmekR.pdf', 'imports/91FLix3iDbKitSHoKkR0P1PRbnkS6WmJLNpDV2va.pdf', 'imports/Vgt3tEUACfKH8JpoZ6ecjwBRoyRyAopiVUStl3Ph.pdf', 'imports/gG1h6mMshCc6E0EobZCa10yEZozpbnOFCboCobUr.pdf', NULL, 'imports/FuWK50BaV2CPHpzLGYBvYBoEWWHge29om7QOOP7u.pdf', 'CMA', 25, NULL, 0, '2026-01-21 07:12:59', '2026-01-21 07:12:59'),
(25, 'VJP26-025', 'RG-87406/25', 'RGP-02839/25', 'TJN0853109', 19, 'XINGANG', 'Colombia', '2026-01-20', '2026-03-02', NULL, NULL, 'pending', 0, NULL, NULL, 'imports/Uk304Ol8va2PJP2CLCthMUKeNsQTmZGJGD5rn30Q.pdf', NULL, 'imports/hX0BTXET0qOmfBwjthXEPRV5PsUsqobhQnWIRdew.pdf', NULL, 'imports/f4Sl6LzffrRMiqP2Z6Y09V83OfkRyYSql26L4c5O.pdf', 'imports/Si7QduUOWwKbLHZMaeKX73HlE8kRwyfF8dDJsvo9.pdf', NULL, NULL, 'CMA-CGM', 21, NULL, 0, '2026-01-21 19:50:55', '2026-01-21 19:50:55'),
(26, 'VJP25-001', 'COL001250802-1', 'COL001250802-1', '285734172', 14, 'china', 'Colombia', '2025-09-15', '2025-10-25', NULL, NULL, 'pendiente_por_confirmar', 0, NULL, 45.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '45', 0, '2026-01-22 07:21:05', '2026-01-22 07:21:06'),
(27, 'VJP26-026', 'RE2601113-1', 'CO9620602', 'TJN0851583', 18, 'CHINA', 'Colombia', '2026-01-14', '2026-02-16', NULL, NULL, 'pending', 0, NULL, 30.00, 'imports/J5BunTDh90CLkKfGgNjQLAqbePekNbVzwmAFnpaQ.pdf', NULL, 'imports/Ysa8zqU3NyvwvQbUqxZHPZ22j7lcxa9Go4ntZ23O.pdf', 'imports/2PktlysqSxq6tD9pN66M50kdcQAdE0yZd98AQV0z.pdf', 'imports/VaVDdHN73bQ7gsjHvRQUNo87xhv1o3OJS5UWjhRf.pdf', 'imports/ciZdSsvfaozdZvX8Psb9dxs6Nv6uRci3dPYlxpWr.pdf', NULL, NULL, 'CMA', NULL, '30', 0, '2026-01-23 06:14:44', '2026-01-23 06:17:45');

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
-- Dumping data for table `import_containers`
--

INSERT INTO `import_containers` (`id`, `import_id`, `reference`, `pdf_path`, `image_pdf_path`, `created_at`, `updated_at`) VALUES
(2, 2, 'TTNU1169987', NULL, NULL, '2026-01-10 22:22:15', '2026-01-10 22:22:15'),
(3, 3, 'SEGU1205426', NULL, NULL, '2026-01-11 03:09:59', '2026-01-11 03:09:59'),
(4, 3, 'CMAU0676983', NULL, NULL, '2026-01-11 03:09:59', '2026-01-11 03:09:59'),
(5, 4, 'CAIU6131519', NULL, NULL, '2026-01-11 03:18:29', '2026-01-11 03:18:29'),
(6, 5, 'CAIU6157811', NULL, NULL, '2026-01-11 03:28:04', '2026-01-11 03:28:04'),
(7, 6, 'FBIU0499048', NULL, NULL, '2026-01-11 03:31:55', '2026-01-11 03:31:55'),
(8, 7, 'MSBU1651946', NULL, NULL, '2026-01-11 03:34:46', '2026-01-11 03:34:46'),
(9, 8, 'CRSU1569385', NULL, NULL, '2026-01-11 03:38:45', '2026-01-11 03:38:45'),
(10, 9, 'MSMU2003772', NULL, NULL, '2026-01-11 03:43:37', '2026-01-11 03:43:37'),
(11, 10, 'TGCU0045811', NULL, NULL, '2026-01-11 03:47:43', '2026-01-11 03:47:43'),
(12, 11, 'SUDU1368362', NULL, NULL, '2026-01-11 21:45:20', '2026-01-11 21:45:20'),
(13, 12, 'MSKU5652671', NULL, NULL, '2026-01-11 21:49:53', '2026-01-11 21:49:53'),
(14, 13, 'MSKU3999996', NULL, NULL, '2026-01-11 21:52:55', '2026-01-11 21:52:55'),
(15, 14, 'APZU3273245', NULL, NULL, '2026-01-11 22:00:27', '2026-01-11 22:00:27'),
(16, 14, 'CMAU3004792', NULL, NULL, '2026-01-11 22:00:27', '2026-01-11 22:00:27'),
(17, 14, 'CMAU3223466', NULL, NULL, '2026-01-11 22:00:27', '2026-01-11 22:00:27'),
(18, 14, 'TGCU2048553', NULL, NULL, '2026-01-11 22:00:27', '2026-01-11 22:00:27'),
(19, 15, 'CMAU1008910', NULL, NULL, '2026-01-11 22:05:43', '2026-01-11 22:05:43'),
(20, 15, 'UETU3035260', NULL, NULL, '2026-01-11 22:05:43', '2026-01-11 22:05:43'),
(21, 16, 'GCXU5376673', NULL, NULL, '2026-01-11 22:12:56', '2026-01-11 22:12:56'),
(22, 17, 'CAAU2019832', NULL, NULL, '2026-01-11 22:18:51', '2026-01-11 22:18:51'),
(23, 17, 'CAAU2020387', NULL, NULL, '2026-01-11 22:18:51', '2026-01-11 22:18:51'),
(24, 17, 'CAAU2099360', NULL, NULL, '2026-01-11 22:18:51', '2026-01-11 22:18:51'),
(25, 18, 'PIDU4064515', NULL, NULL, '2026-01-11 22:27:35', '2026-01-11 22:27:35'),
(26, 18, 'PCIU9321491', NULL, NULL, '2026-01-11 22:27:35', '2026-01-11 22:27:35'),
(27, 19, 'TCLU3793713', NULL, NULL, '2026-01-12 03:38:56', '2026-01-12 03:38:56'),
(28, 19, 'TEMU5925760', NULL, NULL, '2026-01-12 03:38:56', '2026-01-12 03:38:56'),
(29, 20, 'MRKU7442760', NULL, NULL, '2026-01-15 02:48:03', '2026-01-15 02:48:03'),
(30, 20, 'MRKU8147497', NULL, NULL, '2026-01-15 02:48:03', '2026-01-15 02:48:03'),
(31, 20, 'TTNU1296929', NULL, NULL, '2026-01-15 02:48:03', '2026-01-15 02:48:03'),
(32, 22, 'HASU4977415', NULL, NULL, '2026-01-21 04:28:27', '2026-01-21 04:28:27'),
(33, 23, 'MRSU3161896', NULL, NULL, '2026-01-21 04:31:43', '2026-01-21 04:31:43'),
(34, 24, '6mm Clear Tempered Glass', 'imports/9LqsUZMCjqrQn9RbtqN0ukaaiYpdzi1jORekFF8E.pdf', 'imports/foo5NWaofVIC3wX9nJClwPrLLs3DSJV5iGV15jj3.pdf', '2026-01-21 07:12:59', '2026-01-21 07:12:59'),
(35, 24, '4mm/ Blue Flora Pattern Glass；Bronze Flora Pattern Glass；Blue Karatachi Pattern Glass；Bronze Karatachi Pattern Glass', 'imports/mg1ygN6I2Dygyfv44TU79fXrJm5scOdcfFhKpfOu.pdf', 'imports/QnUwUIP9mPc9bv8r7Ed88khL9H4J7LbPWq0uLbXC.pdf', '2026-01-21 07:12:59', '2026-01-21 07:12:59'),
(36, 25, 'TGBU2167203', NULL, NULL, '2026-01-21 19:50:55', '2026-01-21 19:50:55'),
(37, 25, 'TIIU3647119', NULL, NULL, '2026-01-21 19:50:55', '2026-01-21 19:50:55'),
(38, 27, 'XHCU2898960', NULL, NULL, '2026-01-23 06:14:44', '2026-01-23 06:17:45');

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
(44, '2026_01_09_112345_add_image_pdf_to_import_containers_table', 13),
(45, '2026_01_10_122742_add_receive_by_to_transfer_order_products_table', 14),
(46, '2026_01_12_093856_add_nationalized_to_imports_table', 15),
(48, '2026_01_13_143953_add_photos_to_drivers_table', 15),
(49, '2026_01_14_222033_add_social_security_and_vehicle_owner_to_drivers_table', 15),
(50, '2026_01_14_225525_add_credit_paid_to_imports_table', 15),
(51, '2026_01_25_000000_update_container_and_transfer_structure', 16),
(52, '2026_01_25_110000_add_capacity_to_drivers', 16),
(53, '2026_01_25_110001_add_weight_to_container_product', 16),
(54, '2026_01_25_134144_add_weight_to_transfer_order_products', 16);

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
(33, 'VIDRIO CLARO 10 MM 330*214', 'PRD-000001', NULL, 0.00, 0, 1, 'caja', 15, '330*214', NULL, '2026-01-10 19:54:54', '2026-01-10 19:55:35'),
(34, 'VIDRIO INCOLORO 6 MM 330*214', 'PRD-000002', NULL, 0.00, 0, 1, 'caja', 25, '330*214', NULL, '2026-01-10 19:56:38', '2026-01-10 19:57:26'),
(35, 'ESPEJO COPPER FREE 4 MM 330*214', 'PRD-000003', NULL, 0.00, 0, 1, NULL, 40, '330*214', NULL, '2026-01-10 19:58:51', '2026-01-16 17:07:10'),
(36, 'VIDRIO CLARO 8 MM 330*214', 'PRD-000004', NULL, 0.00, 0, 1, 'caja', 19, '330*214', NULL, '2026-01-10 20:09:48', '2026-01-10 20:10:44'),
(37, 'BRONCE REFLECTIVO 5 MM 330*214', 'PRD-000005', NULL, 0.00, 0, 1, 'caja', 31, '330*214', NULL, '2026-01-11 00:36:19', '2026-01-11 00:37:15'),
(38, 'BRONCE 5 MM 330*214', 'PRD-000006', NULL, 0.00, 0, 1, 'caja', 31, '330*214', NULL, '2026-01-11 00:58:01', '2026-01-11 00:58:44'),
(39, 'LAMINADO GRIS 3+3 MM 330*214', 'PRD-000007', NULL, 0.00, 0, 1, 'caja', 25, '330*214', NULL, '2026-01-11 01:00:07', '2026-01-11 01:00:56'),
(40, 'LAMINADO CERAMIC WHITE 3+3 330*214', 'PRD-000008', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-11 01:02:43', '2026-01-11 01:02:43'),
(41, 'LAMINADO MILKY WHITE 3+3 330*214', 'PRD-000009', NULL, 0.00, 0, 1, 'caja', 25, '330*214', NULL, '2026-01-11 01:03:43', '2026-01-11 01:05:05'),
(42, 'LAMINADO CLARO 3+3 330*225', 'PRD-000010', NULL, 0.00, 0, 1, 'caja', 23, '330*225', NULL, '2026-01-11 01:06:20', '2026-01-16 19:57:18'),
(43, 'LAMINADO CLARO 4+4 330*225', 'PRD-000011', NULL, 0.00, 0, 1, 'caja', 16, '330*225', NULL, '2026-01-11 01:06:40', '2026-01-16 19:57:18'),
(44, 'GRABADO MINIBOREAL 4 MM 183*244', 'PRD-000012', NULL, 0.00, 0, 1, 'caja', 60, '183*244', NULL, '2026-01-11 01:12:21', '2026-01-11 01:13:49'),
(45, 'VIDRIO INCOLORO 4 MM 330*225', 'PRD-000013', NULL, 0.00, 0, 1, 'caja', 36, '330*225', NULL, '2026-01-11 01:24:18', '2026-01-19 23:12:00'),
(46, 'VIDRIO CLARO 4 MM 183*260', 'PRD-000014', NULL, 0.00, 0, 1, 'caja', 57, '183*260', NULL, '2026-01-16 16:50:18', '2026-01-16 19:57:18'),
(47, 'VIDRIO CLARO 5 MM 183*260', 'PRD-000015', NULL, 0.00, 0, 1, 'caja', 46, '183*260', NULL, '2026-01-16 16:50:50', '2026-01-16 19:57:18'),
(48, 'VIDRIO CLARO 5 MM 330*214', 'PRD-000016', NULL, 0.00, 0, 1, 'caja', 30, '330*214', NULL, '2026-01-16 16:51:32', '2026-01-16 19:27:07'),
(49, 'VIDRIO CLARO 5 MM 330*244', 'PRD-000017', NULL, 0.00, 0, 1, 'caja', 26, '330*244', NULL, '2026-01-16 16:52:02', '2026-01-16 19:57:18'),
(50, 'AZUL DARK REFLECTIVO 4 MM 330*214', 'PRD-000018', NULL, 0.00, 0, 1, 'caja', 38, '330*214', NULL, '2026-01-16 17:04:38', '2026-01-16 19:27:43'),
(51, 'AZUL DARK REFLECTIVO 5 MM 330*214', 'PRD-000019', NULL, 0.00, 0, 1, 'caja', 30, '330*214', NULL, '2026-01-16 17:05:01', '2026-01-16 19:57:18'),
(52, 'AZUL DARK FLOTADO 4 MM 330*214', 'PRD-000020', NULL, 0.00, 0, 1, 'caja', 38, '330*214', NULL, '2026-01-16 17:05:35', '2026-01-16 19:57:18'),
(53, 'ESPEJO COPPER FREE 4 MM 330*244', 'PRD-000021', NULL, 0.00, 0, 1, 'caja', 35, '330*244', NULL, '2026-01-16 17:06:36', '2026-01-16 19:57:18'),
(54, 'ESPEJO COPPER FREE 5 MM 330*244', 'PRD-000022', NULL, 0.00, 0, 1, 'caja', 28, '330*244', NULL, '2026-01-16 17:06:58', '2026-01-16 19:57:18'),
(55, 'ESPEJO COPPER FREE 4 MM 366*244', 'PRD-000023', NULL, 0.00, 0, 1, 'caja', 31, '366*244', NULL, '2026-01-16 17:07:33', '2026-01-16 19:57:18'),
(56, 'LAMINADO BRONCE 4+4 MM 330*244', 'PRD-000024', NULL, 0.00, 0, 1, 'caja', 17, '330*244', NULL, '2026-01-16 17:08:30', '2026-01-16 20:02:56'),
(57, 'BRONCE FLOTADO 4 MM 330*244', 'PRD-000025', NULL, 0.00, 0, 1, 'caja', 33, '330*244', NULL, '2026-01-16 19:48:45', '2026-01-16 19:57:18'),
(58, 'VIDRIO CLARO 6 MM 330*244', 'PRD-000026', NULL, 0.00, 0, 1, 'caja', 22, '330*244', NULL, '2026-01-16 19:52:01', '2026-01-16 20:19:28'),
(59, 'LAMINADO 4+4MM 330*214', 'PRD-000027', NULL, 0.00, 0, 1, 'caja', 19, '330*214', NULL, '2026-01-16 19:58:25', '2026-01-16 20:02:56'),
(60, 'LAMINADO 3+3 MM 330*214', 'PRD-000028', NULL, 0.00, 0, 1, 'caja', 25, '330*214', NULL, '2026-01-16 19:58:37', '2026-01-16 20:02:56'),
(61, 'BRONCE FLOTADO 4 MM 330*214', 'PRD-000029', NULL, 0.00, 0, 1, 'caja', 38, '330*214', NULL, '2026-01-16 19:58:59', '2026-01-16 20:02:56'),
(62, 'LAMINADO BRONCE 3+3MM 330*214', 'PRD-000030', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-16 19:59:26', '2026-01-16 19:59:26'),
(63, 'BRONCE REFLECTIVO 4 MM 330*214', 'PRD-000031', NULL, 0.00, 0, 1, 'caja', 38, '330*214', NULL, '2026-01-16 20:17:46', '2026-01-16 20:19:28'),
(64, 'VIDRIO INCOLORO 5 MM 330*225', 'PRD-000032', NULL, 0.00, 0, 1, 'caja', 29, '330*225', NULL, '2026-01-19 23:10:45', '2026-01-19 23:12:00'),
(65, 'AZUL DARK REFLECTIVO 3+3MM 330*214', 'PRD-000033', NULL, 0.00, 0, 1, 'caja', 26, '330*214', NULL, '2026-01-21 20:00:17', '2026-01-25 17:35:48'),
(66, 'BRONCE REFLECTIVO 3+3MM 330*214', 'PRD-000034', NULL, 0.00, 0, 1, 'caja', 26, '330*214', NULL, '2026-01-21 20:00:47', '2026-01-25 17:25:37'),
(67, 'VIDRIO CLARO 10 MM 3.66*2.14', 'PRD-000035', NULL, 0.00, 0, 1, 'caja', 13, '3.66*2.14', NULL, '2026-01-21 20:02:26', '2026-01-22 15:59:48'),
(68, 'LAMINADO CLARO 3+3MM 330*244', 'PRD-000036', NULL, 0.00, 0, 1, 'caja', 23, '330*244', NULL, '2026-01-22 15:58:40', '2026-01-22 15:59:48');

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

--
-- Dumping data for table `salidas`
--

INSERT INTO `salidas` (`id`, `salida_number`, `warehouse_id`, `user_id`, `driver_id`, `fecha`, `a_nombre_de`, `nit_cedula`, `note`, `aprobo`, `ciudad_destino`, `created_at`, `updated_at`) VALUES
(7, 'SAL-000001', 18, 12, 10, '2026-01-19', 'CRISTALES TEMPLADOS LA TORRE', '900.593.026', 'SE AUTORIZA CAMBIO CON DEVOLUCIÓN EN BUENAVENTURA, POR REFERENCIA INCOLORO 6MM 3660*2140', 'JAIRO VILLAMIL', 'BOGOTA', '2026-01-21 03:52:38', '2026-01-21 03:52:38'),
(8, 'SAL-000002', 29, 1, 11, '2026-01-22', 'OLGA HORTENCIA TUPAZ', '27249825', NULL, 'PABLO ROJAS', 'IPIALES', '2026-01-22 14:15:53', '2026-01-22 14:15:53'),
(9, 'SAL-000003', 29, 1, 8, '2026-01-22', 'PABLO ANTONIO RIOS', '13905229', 'VENTA DEL 10 MM Y PRESTAMO DEL 3+3 AL MAYORISTA', 'PABLO ROJAS', 'BUCARAMANGA', '2026-01-22 16:01:53', '2026-01-22 16:01:53'),
(10, 'SAL-000004', 18, 12, 12, '2026-01-22', 'VIDRIO MASTER SAS', '901.293.025-9', NULL, 'JAIRO VILLAMIL', 'BOGOTA', '2026-01-23 00:46:54', '2026-01-23 00:46:54'),
(11, 'SAL-000005', 18, 12, 13, '2026-01-19', 'VIDRIO MASTER SAS', '901.293.025-9', NULL, 'JAIRO VILLAMIL', 'BOGOTA', '2026-01-24 13:53:07', '2026-01-24 13:53:07');

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

--
-- Dumping data for table `salida_products`
--

INSERT INTO `salida_products` (`id`, `salida_id`, `product_id`, `container_id`, `quantity`, `created_at`, `updated_at`) VALUES
(9, 7, 34, 33, 50, '2026-01-21 03:52:38', '2026-01-21 03:52:38'),
(10, 8, 61, 46, 38, '2026-01-22 14:15:53', '2026-01-22 14:15:53'),
(11, 8, 48, 46, 60, '2026-01-22 14:15:53', '2026-01-22 14:15:53'),
(12, 8, 34, 46, 25, '2026-01-22 14:15:53', '2026-01-22 14:15:53'),
(13, 9, 68, 47, 23, '2026-01-22 16:01:53', '2026-01-22 16:01:53'),
(14, 9, 67, 47, 13, '2026-01-22 16:01:53', '2026-01-22 16:01:53'),
(15, 10, 59, 34, 38, '2026-01-23 00:46:54', '2026-01-23 00:46:54'),
(16, 11, 55, 33, 31, '2026-01-24 13:53:07', '2026-01-24 13:53:07'),
(17, 11, 53, 33, 35, '2026-01-24 13:53:07', '2026-01-24 13:53:07'),
(18, 11, 60, 34, 25, '2026-01-24 13:53:07', '2026-01-24 13:53:07'),
(19, 11, 39, 33, 25, '2026-01-24 13:53:07', '2026-01-24 13:53:07');

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

--
-- Dumping data for table `transfer_orders`
--

INSERT INTO `transfer_orders` (`id`, `warehouse_from_id`, `warehouse_to_id`, `salida`, `destino`, `order_number`, `status`, `date`, `note`, `aprobo`, `ciudad_destino`, `driver_id`, `created_at`, `updated_at`) VALUES
(11, 14, 15, 'BUENAVENTURA', 'BOGOTA', 'TO-000001', 'recibido', '2026-01-15 02:30:08', NULL, NULL, NULL, 9, '2026-01-15 02:30:08', '2026-01-17 13:31:13'),
(12, 14, 15, 'BUENAVENTURA', 'BOGOTA', 'TO-000002', 'en_transito', '2026-01-15 22:15:32', NULL, NULL, NULL, 9, '2026-01-15 22:15:32', '2026-01-15 22:15:32'),
(14, 17, 18, 'BUENAVENTURA', 'GIRARDOT', 'TO-000003', 'recibido', '2026-01-16 20:53:57', 'ARREGLO INV VIEJO', NULL, NULL, 9, '2026-01-16 20:53:57', '2026-01-20 18:23:33'),
(16, 17, 15, 'BUENAVENTURA', 'BOGOTA', 'TO-000004', 'en_transito', '2026-01-25 17:27:49', 'SALEN 2 CAJAS DE BRONCE REFLECTIVO POR 25 Y 1 POR 26 LAMINAS', NULL, NULL, 18, '2026-01-25 17:27:49', '2026-01-25 17:27:49');

-- --------------------------------------------------------

--
-- Table structure for table `transfer_order_products`
--

CREATE TABLE `transfer_order_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transfer_order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `sheets_per_box` int(11) DEFAULT NULL,
  `weight_per_box` decimal(10,2) DEFAULT NULL,
  `good_sheets` int(11) DEFAULT NULL COMMENT 'Láminas en buen estado recibidas',
  `bad_sheets` int(11) DEFAULT NULL COMMENT 'Láminas en mal estado recibidas',
  `receive_by` enum('cajas','laminas') DEFAULT NULL COMMENT 'Forma en que se recibe la transferencia: por cajas o por láminas',
  `container_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transfer_order_products`
--

INSERT INTO `transfer_order_products` (`id`, `transfer_order_id`, `product_id`, `quantity`, `good_sheets`, `bad_sheets`, `receive_by`, `container_id`, `created_at`, `updated_at`) VALUES
(15, 11, 35, 4, 4, 0, 'laminas', 19, '2026-01-15 02:30:08', '2026-01-15 02:30:08'),
(16, 12, 36, 3, NULL, NULL, NULL, 20, '2026-01-15 22:15:32', '2026-01-15 22:15:32'),
(17, 12, 33, 2, NULL, NULL, NULL, 16, '2026-01-15 22:15:32', '2026-01-15 22:15:32'),
(18, 12, 33, 2, NULL, NULL, NULL, 17, '2026-01-15 22:15:32', '2026-01-15 22:15:32'),
(41, 14, 52, 3, 114, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(42, 14, 50, 1, 38, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(43, 14, 51, 2, 60, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(44, 14, 61, 2, 76, 0, 'laminas', 34, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(45, 14, 57, 8, 264, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(46, 14, 63, 2, 76, 0, 'laminas', 35, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(47, 14, 53, 10, 350, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(48, 14, 55, 1, 31, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(49, 14, 54, 1, 28, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(50, 14, 60, 2, 50, 0, 'laminas', 34, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(51, 14, 59, 7, 133, 0, 'laminas', 34, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(52, 14, 56, 3, 51, 0, 'laminas', 34, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(53, 14, 42, 1, 23, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(54, 14, 43, 11, 176, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(55, 14, 39, 1, 25, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(56, 14, 46, 12, 684, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(57, 14, 47, 6, 276, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(58, 14, 48, 3, 90, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(59, 14, 49, 3, 78, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(60, 14, 58, 6, 132, 0, 'laminas', 35, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(61, 14, 36, 1, 19, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(62, 14, 34, 2, 50, 0, 'laminas', 33, '2026-01-16 20:53:57', '2026-01-16 20:53:57'),
(64, 16, 65, 1, NULL, NULL, NULL, 40, '2026-01-25 17:27:49', '2026-01-25 17:27:49'),
(65, 16, 66, 3, NULL, NULL, NULL, 42, '2026-01-25 17:27:49', '2026-01-25 17:27:49');

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
(9, 'vidriomaster.sas@hotmail.com', 'vidriomaster.sas@hotmail.com', NULL, '$2y$10$QRV41fhGN7JkPmeJJvoM/.krytMro9T4i2IXslHzU7QscVThUl9rm', 'VIDRIO MASTER COLOMBIA', '3138353922', NULL, 'clientes', NULL, '2026-01-02 03:33:42', '2026-01-17 13:28:20'),
(11, 'vidriosjyp@gmail.com', 'vidriosjyp@gmail.com', NULL, '$2y$10$JdzGYRkHcsIU9ZmwhfPfUuvWxUQytsiIijl0YuZblPBbSEuBd18XO', 'GHEIDY YOHANA ISAZA', '3135250178', NULL, 'funcionario', NULL, '2026-01-02 15:12:31', '2026-01-03 15:39:01'),
(12, 'gerencia@serviglassgirardot.com', 'gerencia@serviglassgirardot.com', NULL, '$2y$10$g5uFgXhjdN1PpV9n7nAKDeEAKZnqsFCBm4qLXTSKvJVSdyodZ9jLy', 'MARIO FERNANDO DOMINGUEZ PRIETO', '3138349415', NULL, 'clientes', NULL, '2026-01-02 16:28:08', '2026-01-03 02:11:37'),
(13, 'funcionario@local.com', 'funcionario@local.com', NULL, '$2y$10$RZDJcVQd8CjM8k/4U4qaSeb0vGVfiEZry6ahVlYv4KBY25ACSgCyi', 'test funcionario', '56635464563', NULL, 'funcionario', NULL, '2026-01-03 14:27:21', '2026-01-03 14:27:21'),
(14, 'valeria@lanmogroup.com', 'valeria@lanmogroup.com', NULL, '$2y$10$fsRcN4XwxGsJ4EE69t.2neHpPTPTN8ZF3baEYT40TAka7M4xypteW', 'LANMO GROUP', '15898809200', NULL, 'importer', NULL, '2026-01-10 17:23:32', '2026-01-12 03:13:46'),
(15, 'PRUEBA123@gmail.com', 'PRUEBA123@gmail.com', NULL, '$2y$10$920c.r8/fhAc9qEEw4boremzpUFIh0elL.ZV3ekSoj0QpPMZ08st.', 'PRUEBNA', '318277382929', NULL, 'funcionario', NULL, '2026-01-10 22:29:25', '2026-01-10 22:29:25'),
(16, 'JENNIFER@GMAIL.COM', 'JENNIFER@GMAIL.COM', NULL, '$2y$10$tKVUb/x97mZoZzlJLwp69uU2tsMdz4eJfCSIRv4J6525PAFUaizOO', 'JENNIFER', '3192988281', NULL, 'import_viewer', NULL, '2026-01-11 00:54:31', '2026-01-11 00:54:31'),
(17, 'auspic@gmail.com', 'auspic@gmail.com', NULL, '$2y$10$bJSTs2TVvpU5a2FWizEgq.oDQ3NUHyMDWRUK5SIxoAz1mm3.GEPfG', 'AUSPIC GLASS CO', '3173049853', NULL, 'importer', NULL, '2026-01-11 03:03:17', '2026-01-11 03:03:17'),
(18, 'evia.zhong@rexiglass.com', 'evia.zhong@rexiglass.com', NULL, '$2y$10$nPYHqMSteo9jGicZFrz3l./LHUb7o150SKNaTqYEMWjkXfRXspLsC', 'REXIGLASS', '31827726627', NULL, 'importer', NULL, '2026-01-11 21:56:52', '2026-01-21 05:04:26'),
(19, 'jasmine@riderglass.com', 'jasmine@riderglass.com', NULL, '$2y$10$jb3ca7wh7gj2N3qvRCR0T..Foog86XDsqkH95jP1v.Dh4PoKLwo56', 'RIDER GLASS COMPANY', '3173049853', NULL, 'importer', NULL, '2026-01-11 22:09:44', '2026-01-21 05:13:19'),
(20, 'tzkyglass@gmail.com', 'tzkyglass@gmail.com', NULL, '$2y$10$UYIDWflDVTocDlXjrf1QteJYWVJtbmahG/sUPBm5U2SgIBohIqMDu', 'TENGZHOU KUNYUE GLASS', '3182372782', NULL, 'importer', NULL, '2026-01-11 22:21:53', '2026-01-21 05:35:25'),
(21, 'julio@gmail.com', 'julio@gmail.com', NULL, '$2y$10$OS7v/1AxioCiTZVVJ/xrJ.45S0xGtkhEa0PU1XdrWSTO8.7d8OOP2', 'CHEONGFULI (XIAMEN) CO', '13573220238', NULL, 'importer', NULL, '2026-01-12 03:32:48', '2026-01-12 03:32:48'),
(22, 'hand@hexadglass.com', 'hand@hexadglass.com', NULL, '$2y$10$YpHS9f53dW5O0u4tq8.0bewdpKMaQNoZLRdvgRM9MGryRlOgFzxuC', 'HEXAD GLASS', '17806285113', NULL, 'importer', NULL, '2026-01-21 05:39:58', '2026-01-21 05:39:58');

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
(32, 13, 14, '2026-01-03 14:27:21', '2026-01-03 14:27:21'),
(33, 15, 25, '2026-01-10 22:29:25', '2026-01-10 22:29:25'),
(34, 15, 16, '2026-01-10 22:29:25', '2026-01-10 22:29:25'),
(35, 15, 27, '2026-01-10 22:29:25', '2026-01-10 22:29:25');

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
(28, 'VENTA VIDRIOS DEL ORIENTE OPERACIONES JC', 'BUENAVENTURA', 'BUENAVENTURA', '2026-01-03 22:15:44', '2026-01-03 22:15:44'),
(29, 'PABLO ROJAS OPERACIONES JC', 'BUENAVENTURA', 'BUENAVENTURA', '2026-01-11 01:14:24', '2026-01-11 01:14:24'),
(30, 'VENTAS DIVIDRIOS SAS OPERACIONES JC', 'BUENAVENTURA', 'BUENAVENTURA', '2026-01-11 20:39:45', '2026-01-11 20:39:45'),
(31, 'ZULUVIDRIOS JC BUN', 'BUENAVENTURA', 'BUENAVENTURA', '2026-01-16 19:45:54', '2026-01-16 19:45:54'),
(32, 'ZULUVIDRIOS', 'MEDELLIN', 'MEDELLIN', '2026-01-19 23:07:15', '2026-01-19 23:07:15'),
(33, 'RLW GLASS', 'BUENAVENTURA', 'BUENAVENTURA', '2026-01-21 20:01:41', '2026-01-21 20:01:41');

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
  ADD KEY `container_id` (`container_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `containers`
--
ALTER TABLE `containers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `container_product`
--
ALTER TABLE `container_product`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `imports`
--
ALTER TABLE `imports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `import_containers`
--
ALTER TABLE `import_containers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `product_warehouse_stock`
--
ALTER TABLE `product_warehouse_stock`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salidas`
--
ALTER TABLE `salidas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `salida_products`
--
ALTER TABLE `salida_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `transfer_orders`
--
ALTER TABLE `transfer_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `transfer_order_products`
--
ALTER TABLE `transfer_order_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_warehouse`
--
ALTER TABLE `user_warehouse`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
-- Constraints for table `imports`
--
ALTER TABLE `imports`
  ADD CONSTRAINT `imports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `import_containers`
--
ALTER TABLE `import_containers`
  ADD CONSTRAINT `import_containers_import_id_foreign` FOREIGN KEY (`import_id`) REFERENCES `imports` (`id`) ON DELETE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
