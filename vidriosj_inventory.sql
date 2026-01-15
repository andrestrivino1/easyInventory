-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 14, 2026 at 11:03 PM
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
(29, 'MSMU371572-4', 29, 'INV VIEJO', NULL, NULL);

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
(16, 16, 33, 2, 15, '2026-01-10 19:55:35', '2026-01-10 19:55:35'),
(17, 17, 33, 2, 15, '2026-01-10 19:56:15', '2026-01-10 19:56:15'),
(18, 18, 34, 7, 25, '2026-01-10 19:57:26', '2026-01-10 19:57:26'),
(19, 19, 35, 0, 40, '2026-01-10 20:00:17', '2026-01-10 20:00:17'),
(20, 20, 36, 3, 19, '2026-01-10 20:10:44', '2026-01-10 20:10:44'),
(21, 21, 37, 2, 31, '2026-01-11 00:37:15', '2026-01-11 00:37:15'),
(22, 22, 37, 2, 31, '2026-01-11 00:37:51', '2026-01-11 00:37:51'),
(23, 23, 38, 10, 31, '2026-01-11 00:58:44', '2026-01-11 00:58:44'),
(24, 24, 39, 5, 25, '2026-01-11 01:00:56', '2026-01-11 01:00:56'),
(25, 25, 41, 4, 25, '2026-01-11 01:05:05', '2026-01-11 01:05:05'),
(26, 26, 43, 10, 18, '2026-01-11 01:07:44', '2026-01-11 01:07:44'),
(27, 27, 42, 4, 24, '2026-01-11 01:08:53', '2026-01-11 01:08:53'),
(28, 28, 44, 4, 60, '2026-01-11 01:13:49', '2026-01-11 01:13:49'),
(29, 29, 44, 2, 60, '2026-01-11 01:15:21', '2026-01-11 01:15:21');

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
(7, 'FABIAN VIVAS PARADA', '1115911364', '3206615093', 'NYO322', 1, NULL, NULL),
(8, 'CESAR VANEGASS SERRANO', '79916566', '311459996', 'WOM166', 1, NULL, NULL),
(9, 'NICOLAS CANO', '1007530763', '3145656161', 'FST609', 1, NULL, NULL);

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

--
-- Dumping data for table `imports`
--

INSERT INTO `imports` (`id`, `do_code`, `commercial_invoice_number`, `proforma_invoice_number`, `bl_number`, `user_id`, `origin`, `destination`, `departure_date`, `arrival_date`, `actual_arrival_date`, `received_at`, `status`, `files`, `credits`, `proforma_pdf`, `proforma_invoice_low_pdf`, `invoice_pdf`, `commercial_invoice_low_pdf`, `bl_pdf`, `packing_list_pdf`, `apostillamiento_pdf`, `other_documents_pdf`, `shipping_company`, `free_days_at_dest`, `credit_time`, `created_at`, `updated_at`) VALUES
(2, 'VJP26-002', 'COL001251006-4', 'COL001251006', 'DLN0262195', 14, 'QINGDAO', 'Colombia', '2025-12-09', '2026-01-13', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/wfSBKhx6PBiSI9Xa1AnIrvT0cvLIox0wrlVDzsWx.pdf', 'imports/FTJonN5pWh3HzZHwOJ0pIOPCoym6r24cCD6qB0VF.pdf', 'imports/nmzKv1tmBix9rBNCiDzrBL8jZppP2mFV53ctTqq2.pdf', 'imports/m7eOOe9mb1k8Y0zf57nfRxCiLduAOcx7zaBMwgZI.pdf', 'imports/XhD2sUq1DfK3CjmHvVIqKNfZQ3699GHe2muVx3ML.pdf', 'CMA-CGM', 21, '45', '2026-01-10 22:22:15', '2026-01-14 15:03:41'),
(3, 'VJP26-003', 'JPSA007-251020', NULL, 'TJN0803976', 17, 'QINGDAO', 'Colombia', '2025-11-23', '2026-01-06', NULL, NULL, 'completed', NULL, NULL, NULL, NULL, NULL, 'imports/fNirbYniCz37Jx2hVhq0eUorWhjH9oL18EggUNfH.pdf', 'imports/OzmtfYxTC8Xj6T09Tu6Fw3Guid8EmFiTH7SwgVPc.pdf', 'imports/MeQ00cv1F8clARDXBDlHHF4qikdnKgYILQhL5y4R.pdf', 'imports/9wvv17VbpZ5k4AlgBiMMttySnMcTZs58umg3g0rQ.pdf', 'imports/OnwOvTx85ATNtVaYakEUn3B0I8HLn4VXnt9axSxv.pdf', 'CMA-CGM', 21, NULL, '2026-01-11 03:09:59', '2026-01-11 03:09:59'),
(4, 'VJP26-004', 'COL001251006-5', 'COL001251006', 'MEDUHW415929', 14, 'DALIAN', 'Colombia', '2025-11-22', '2026-01-06', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/IPySkWTf6EEwv3WW8FCAmm2YIU2xZW2Ix3Vns0pP.pdf', 'imports/4gucvYvCM6j0Bj0p9efk6kv9GZpBJWqvCMmlFSEK.pdf', 'imports/w3lvfpGp89nZRhQnLGi4gqCniISBUUeg3T88LVWz.pdf', 'imports/EUlsYucdtjBPqCqceMEpMTL9wpqEGv1yLMBQ22pC.pdf', 'imports/OBMhS3gmGN4H9q9QzhACRSrYXQQUvoYex5ptgpJv.pdf', 'MSC', 21, '45', '2026-01-11 03:18:29', '2026-01-11 03:19:06'),
(5, 'VJP26-005', 'COL001251006-6', 'COL001251006', 'MEDUHW415911', 14, 'DALIAN', 'Colombia', '2025-11-22', '2026-01-06', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/lMbcTnkutwpVhPYfUEk2CHhTHkzcNxvbUx56TgHx.pdf', 'imports/f8HpMxXoNT68El9XGro0IazaimyBjQuFQTWnjMbQ.pdf', 'imports/n0j6NNlpqN8oA36D8HfDUxcVBx3b3bOySD3fUoVq.pdf', 'imports/BhkfRcmvzBsUT9YlQQevUGGEiYjk7EDToPAr542s.pdf', 'imports/Zcoo8L2If3ncm1fabINcZK4WnC91gGLfNo4fY96r.pdf', 'MSC', 21, '45', '2026-01-11 03:28:04', '2026-01-11 03:28:05'),
(6, 'VJP26-006', 'COL001251006-7', 'COL001251006', 'MEDUHW415903', 14, 'DALIAN', 'Colombia', '2025-11-22', '2026-01-06', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/ocjVzfpSb6HBfIJAi77hwSp3ZyyQn9r0uL7NxMmd.pdf', 'imports/xUQNyObhMFb7Cvy7BflSJwMSqWDHzqCJNc3ei20A.pdf', 'imports/XbefuezCUeisgXnS2lmHmb5TDp99Lt9HdrYsLx2w.pdf', 'imports/8wemnPIel7U9bOdR58R34xP7cHRULXFXm37G1fNr.pdf', 'imports/sVIElB7VUJlGQpGaMqOgrSzkBuWohV8j3jaC9PaI.pdf', 'MSC', 21, '45', '2026-01-11 03:31:55', '2026-01-11 03:31:55'),
(7, 'VJP26-007', 'COL001251006-8', 'COL001251006', 'MEDUHW415887', 14, 'DALIAN', 'Colombia', '2025-11-22', '2026-01-06', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/93kI2cuMVKS0tyNVNuTS3lVUPTaNg976ylTQqc24.pdf', 'imports/77sdKGN76g8tMYOumnNuDAOnpnyNL02uQSyUoDUN.pdf', 'imports/ILwu8swOJT3gNEXm1rCjpLAvOa2CGRTN8mrFbQPB.pdf', 'imports/rSfluEGUVCormOcATKOQbbrZroxTSLHfBf1b7ECp.pdf', 'imports/q1cffin0S4PUjoxSp7lpqFBSbTZ6d3611XQHiWgG.pdf', 'MSC', 21, '45', '2026-01-11 03:34:46', '2026-01-11 03:34:46'),
(8, 'VJP26-008', 'COL001251006-9', 'COL001251006', 'MEDUHW458788', 14, 'DALIAN', 'Colombia', '2025-11-22', '2026-01-14', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/ZCZWIJ8FYsdF4DTddLkd5IjHZEvK5c4URZ2SJUcO.pdf', 'imports/Jz41gEvgUt4H0nvzRZNCEJmlG0JBscWEh3nua62m.pdf', 'imports/IUzPPjDkkoVvYNPdCc6ZuKbElaBytpDVi5UvtxG6.pdf', 'imports/y8MXiJqr2fO8pnJrsvc7Kwjkq7OYEtv19vwih5YZ.pdf', 'imports/YZhE7Bek5iGwZgxS2tfOqP8fKcXyoFUxAC8IbCOA.pdf', 'MSC', 21, '45', '2026-01-11 03:38:45', '2026-01-15 02:19:50'),
(9, 'VJP26-009', 'COL001251006-10', 'COL001251006', 'MEDUHW458770', 14, 'DALIAN', 'Colombia', '2025-12-01', '2026-01-14', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/LL05GB5r0kcR5yOYV6wj73pdAM419C7jjJ4aIHlS.pdf', 'imports/yehO2LPqHBSCB6p4OiPwUrG9uRVmbDeKt98SKJSV.pdf', 'imports/kvpmHi7mRFy40wq8UggA7nHd1ina77GQ08SHaO58.pdf', 'imports/S5GFCQIspxhNswizwwRAEoYlmqG171X0FtBH5hI8.pdf', 'imports/ukORkw5FSPdZYVfOvzJvcAZCrzE2SSjbOXBtALgl.pdf', 'MSC', 21, '45', '2026-01-11 03:43:37', '2026-01-14 15:03:41'),
(10, 'VJP26-010', 'COL001250904-1', 'COL001250904', 'QGD2281845', 14, 'QINGDAO', 'Colombia', '2025-11-29', '2026-01-07', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/ECA7Z8pU2uYh8YeK1ibQBbK8NEglSpM7RlScDWCs.pdf', 'imports/t2DfN6qpIvKW7rxhEm2Ff4IRl1wf40HzlFGB9CZc.pdf', 'imports/ZamQZJ5aia6Kx3nBOTnZPmaKFMxQ39qC8e6WFvqd.pdf', 'imports/NRGPN9wAUJo0h574wAzjXYalmOOqVxPp4V4CHPxH.pdf', 'imports/E2JQPV8Qh5oZt28dg4gCOwi7KCIbciCID2qBZqZb.pdf', 'CMA-CGM', 21, '45', '2026-01-11 03:47:43', '2026-01-11 03:47:43'),
(11, 'VJP26-011', 'COL001251004-1', 'COL001251004', '261482941', 14, 'APAPA NIGERIA', 'Colombia', '2025-11-12', '2026-01-11', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/tb1Y0GxXLmQwouHOhPrX6DH5tyyin0n36pMUzA6i.pdf', 'imports/1ZP09eQLkTTbwocqec3J6pAaTH9PIliZ5gLHCN8n.pdf', 'imports/dtoqs36iJpVyFNKsw7DpvW2ymJa2Y9Cqzt7KuZzl.pdf', 'imports/h1qjTsQeIEwPPBIe3eaKIJv8JYxmHYhcunT3IabA.pdf', 'imports/MCVz1aigSTC4cn9u6DYNxrQUAviC97CzTOCix1cM.pdf', 'LOGISTICS SOLUTIONS', 21, '45', '2026-01-11 21:45:20', '2026-01-11 21:45:21'),
(12, 'VJP26-012', 'COL001251004-2', 'COL001251004', '261483451', 14, 'APAPA NIGERIA', 'Colombia', '2025-11-12', '2026-01-11', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/GsmP9zCUPJIcRVA0qzu92IXY7haumhJLCjr6h3Gy.pdf', 'imports/Jq5EvicLm9XCBfXmI6KRItH57USopaGhcHj1Zha2.pdf', 'imports/YpEW3aHTdjHQ2V5Kku22ScghUpaaKmhS58hIaEjN.pdf', 'imports/QOQlSBejxj8mZ6eT1cJK8dBjcfJDmM01HPFiyPAj.pdf', 'imports/yT38L4FLFBDJaAxs3ubndwLo1RdeDTtOuCjT4y4V.pdf', 'LOGISTICS SOLUTIONS', 21, '45', '2026-01-11 21:49:53', '2026-01-11 21:49:53'),
(13, 'VJP26-013', 'COL001251004-3', 'COL001251004', '261483687', 14, 'APAPA NIGERIA', 'Colombia', '2025-11-12', '2026-01-11', NULL, NULL, 'completed', NULL, 45.00, NULL, NULL, NULL, 'imports/u8mYL0oFGlQjiJuRmOXdNFFGOPJ6cDBRYivjg79E.pdf', 'imports/Zg0br3Upnt2Ewdx4IwN6B4dNYq12n52Sc3T4iP4L.pdf', 'imports/NrzERePGKQbQ8uABPr5tVuDvmkVIglflQ4KwkJ14.pdf', 'imports/MKnABtXBEhFhtFRUNGjgo5sVxDyfEQjrQ1ARKUjE.pdf', 'imports/UTIqTHDAjVz4RAsz6pL1YzIsBf5vt62oRAwqE659.pdf', 'LOGISTICS SOLUTIONS', 21, '45', '2026-01-11 21:52:55', '2026-01-11 21:52:55'),
(14, 'VJP26-014', 'RE2510101-1', 'CO9620508', 'AYN1289232', 18, 'QINGDAO', 'Colombia', '2025-12-17', '2026-01-20', NULL, NULL, 'pending', NULL, 30.00, NULL, NULL, NULL, 'imports/GaWBJOyYNrWpW6FexwN5EHoa0E5ZPf8dk1GWPVXJ.pdf', 'imports/FZ8lMxUvUnAsDNTi9H8iMhaDEvC3yrpyCnmcBMyZ.pdf', 'imports/QvjYVWrsG5Do8ldyQIQP5OK5KPOdIenpl6nbc3bJ.pdf', 'imports/bCDEMJOVLPB68GhxB9VE53jCK9opfphpBNivzn5t.pdf', NULL, 'CMA-CGM', 21, '30', '2026-01-11 22:00:27', '2026-01-11 22:00:27'),
(15, 'VJP26-015', 'RE2509196-1', 'CO9620506', 'TJN0826454', 18, 'TIANJIN', 'Colombia', '2026-01-01', '2026-02-16', NULL, NULL, 'pending', NULL, 30.00, NULL, NULL, NULL, 'imports/ag6PSMCcbFUXJG7sZ74qtYk9JNEMFRqMLW91I4JC.pdf', 'imports/p4gNcLRwXuUop4lcIaVb2UceJLqIXXNhhqGqF3dQ.pdf', 'imports/1suBc0DWmQUCmrOBak8xG1o4eEfls29eH1Iix0he.pdf', NULL, NULL, 'CMA-CGM', 21, '30', '2026-01-11 22:05:43', '2026-01-11 22:05:43'),
(16, 'VJP26-016', 'RG-18890/25', 'RGP06852/25', 'TJN0822029', 19, 'TIANJIN', 'Colombia', '2025-12-17', '2026-01-20', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 'imports/WTWFrByFCgx6uM6OiflRWIV4emr8f9jLC1ymRbMq.pdf', NULL, 'imports/G1mlLEZEIWCfnqDo4d2wGeSCfIOdZFBfblQOaQqq.pdf', 'imports/gfsdOBKtKFwI5r0Hb1cY9crwNuA2EahRmvlVUV2s.pdf', NULL, NULL, 'CMA-CGM', 21, NULL, '2026-01-11 22:12:56', '2026-01-11 22:12:56'),
(17, 'VJP26-017', 'RG-17981/25', 'RGP-06852/25', 'TJN0821851', 19, 'TIANJIN', 'Colombia', '2025-12-17', '2026-01-20', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 'imports/mD6EuvWqaTu9vUjhyfiN7mrtNuQTBxcDqhLGyOwI.pdf', NULL, 'imports/bCVeJUVwq4tpwm4lN6m9ZF2NvUuYIq04CimCY9cZ.pdf', 'imports/s8eX7STyVt33aQdIDiEsjXod0k6qb7bPvLSRqy4v.pdf', NULL, NULL, 'CMA-CGM', 21, NULL, '2026-01-11 22:18:51', '2026-01-11 22:18:51'),
(18, 'VJP26-018', 'TZKY250927', NULL, 'CNSAC2511100', 20, 'QINGDAO', 'Colombia', '2025-12-07', '2026-01-05', NULL, NULL, 'completed', NULL, 30.00, NULL, NULL, NULL, 'imports/WdkFVcuG4NVL1ovmQZoI05CKIo87F3PXyWK6TTuV.pdf', 'imports/CUVjfA92mNeWiYYqDYjQlEdSTOhHzo2qRR4AzZB0.pdf', 'imports/b5fsEvLSgFc1lUbGLWIK39JnO3jbLgyVwV25im2b.pdf', 'imports/eC7chuI43AEUrUBVqcjYNlVbQLIeSbzmkclCyQaE.pdf', NULL, 'LOGISTICS SOLUTIONS', 18, '30', '2026-01-11 22:27:35', '2026-01-11 22:27:36'),
(19, 'VJP26-019', 'FGQXE20251015JU-JPS04', 'FGQXE20251015JU-JPS03', 'NTJEC251210420', 21, 'XINGANG', 'Colombia', '2025-12-17', '2026-01-20', NULL, NULL, 'pending', NULL, NULL, NULL, NULL, 'imports/9mQ6wa992nlVCJRjyMJHAYuSFGF32d1DVGTaQnAP.pdf', 'imports/D4KbzZogH2c4xjfKGfy8AenbY9Kyl0b6VbCjmVbs.pdf', 'imports/9oyB0B9Cx4z4a0m6NB05T84Tlr3vLLFsmD86J7xd.pdf', 'imports/YJTI8QoLeRNEsbYiHv87yBsxLPaJs3GRIFsXCE98.pdf', NULL, NULL, 'LOGISTICS SOLUTIONS', 18, NULL, '2026-01-12 03:38:56', '2026-01-14 15:45:32'),
(20, 'VJP26-020', 'FGQXE20250919JU-RLW01-2', 'FGQXE20250919JU-RLW01', '260975609', 21, 'TIANJIN', 'Colombia', '2025-11-25', '2026-01-01', NULL, NULL, 'completed', NULL, NULL, NULL, NULL, 'imports/KGMYJJdPjmEIs2LNHZVtKeiZc6e2JPPlUfiK7sqE.pdf', NULL, 'imports/jvyOmCooMHYKtr3tG70gDJAPgL9ldiN7TthHEJ0i.pdf', 'imports/KhPxpIHEPbPIPAYsunhmkAiDr37WkFyKMLccO6Cb.pdf', NULL, NULL, 'MSK', 21, NULL, '2026-01-15 02:48:03', '2026-01-15 02:48:04');

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
(31, 20, 'TTNU1296929', NULL, NULL, '2026-01-15 02:48:03', '2026-01-15 02:48:03');

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
(45, '2026_01_10_122742_add_receive_by_to_transfer_order_products_table', 14);

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
(35, 'ESPEJO COPPER 4 MM 330*214', 'PRD-000003', NULL, 0.00, 0, 1, 'caja', 40, '330*214', NULL, '2026-01-10 19:58:51', '2026-01-10 20:00:17'),
(36, 'VIDRIO CLARO 8 MM 330*214', 'PRD-000004', NULL, 0.00, 0, 1, 'caja', 19, '330*214', NULL, '2026-01-10 20:09:48', '2026-01-10 20:10:44'),
(37, 'BRONCE REFLECTIVO 5 MM 330*214', 'PRD-000005', NULL, 0.00, 0, 1, 'caja', 31, '330*214', NULL, '2026-01-11 00:36:19', '2026-01-11 00:37:15'),
(38, 'BRONCE 5 MM 330*214', 'PRD-000006', NULL, 0.00, 0, 1, 'caja', 31, '330*214', NULL, '2026-01-11 00:58:01', '2026-01-11 00:58:44'),
(39, 'LAMINADO GRIS 3+3 MM 330*214', 'PRD-000007', NULL, 0.00, 0, 1, 'caja', 25, '330*214', NULL, '2026-01-11 01:00:07', '2026-01-11 01:00:56'),
(40, 'LAMINADO CERAMIC WHITE 3+3 330*214', 'PRD-000008', NULL, 0.00, 0, 1, NULL, NULL, '330*214', NULL, '2026-01-11 01:02:43', '2026-01-11 01:02:43'),
(41, 'LAMINADO MILKY WHITE 3+3 330*214', 'PRD-000009', NULL, 0.00, 0, 1, 'caja', 25, '330*214', NULL, '2026-01-11 01:03:43', '2026-01-11 01:05:05'),
(42, 'LAMINADO CLARO 3+3 330*225', 'PRD-000010', NULL, 0.00, 0, 1, 'caja', 24, '330*225', NULL, '2026-01-11 01:06:20', '2026-01-11 01:08:53'),
(43, 'LAMINADO CLARO 4+4 330*225', 'PRD-000011', NULL, 0.00, 0, 1, 'caja', 18, '330*225', NULL, '2026-01-11 01:06:40', '2026-01-11 01:07:44'),
(44, 'GRABADO MINIBOREAL 4 MM 183*244', 'PRD-000012', NULL, 0.00, 0, 1, 'caja', 60, '183*244', NULL, '2026-01-11 01:12:21', '2026-01-11 01:13:49'),
(45, 'VIDRIO INCOLORO 4 MM 330*225', 'PRD-000013', NULL, 0.00, 0, 1, NULL, NULL, '330*225', NULL, '2026-01-11 01:24:18', '2026-01-11 01:24:18');

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

--
-- Dumping data for table `transfer_orders`
--

INSERT INTO `transfer_orders` (`id`, `warehouse_from_id`, `warehouse_to_id`, `salida`, `destino`, `order_number`, `status`, `date`, `note`, `aprobo`, `ciudad_destino`, `driver_id`, `created_at`, `updated_at`) VALUES
(11, 14, 15, 'BUENAVENTURA', 'BOGOTA', 'TO-000001', 'en_transito', '2026-01-15 02:30:08', NULL, NULL, NULL, 9, '2026-01-15 02:30:08', '2026-01-15 02:30:08');

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
  `receive_by` enum('cajas','laminas') DEFAULT NULL COMMENT 'Forma en que se recibe la transferencia: por cajas o por láminas',
  `container_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transfer_order_products`
--

INSERT INTO `transfer_order_products` (`id`, `transfer_order_id`, `product_id`, `quantity`, `good_sheets`, `bad_sheets`, `receive_by`, `container_id`, `created_at`, `updated_at`) VALUES
(15, 11, 35, 4, NULL, NULL, NULL, 19, '2026-01-15 02:30:08', '2026-01-15 02:30:08');

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
(13, 'funcionario@local.com', 'funcionario@local.com', NULL, '$2y$10$RZDJcVQd8CjM8k/4U4qaSeb0vGVfiEZry6ahVlYv4KBY25ACSgCyi', 'test funcionario', '56635464563', NULL, 'funcionario', NULL, '2026-01-03 14:27:21', '2026-01-03 14:27:21'),
(14, 'valeria@lanmogroup.com', 'valeria@lanmogroup.com', NULL, '$2y$10$fsRcN4XwxGsJ4EE69t.2neHpPTPTN8ZF3baEYT40TAka7M4xypteW', 'LANMO GROUP', '15898809200', NULL, 'importer', NULL, '2026-01-10 17:23:32', '2026-01-12 03:13:46'),
(15, 'PRUEBA123@gmail.com', 'PRUEBA123@gmail.com', NULL, '$2y$10$920c.r8/fhAc9qEEw4boremzpUFIh0elL.ZV3ekSoj0QpPMZ08st.', 'PRUEBNA', '318277382929', NULL, 'funcionario', NULL, '2026-01-10 22:29:25', '2026-01-10 22:29:25'),
(16, 'JENNIFER@GMAIL.COM', 'JENNIFER@GMAIL.COM', NULL, '$2y$10$tKVUb/x97mZoZzlJLwp69uU2tsMdz4eJfCSIRv4J6525PAFUaizOO', 'JENNIFER', '3192988281', NULL, 'import_viewer', NULL, '2026-01-11 00:54:31', '2026-01-11 00:54:31'),
(17, 'auspic@gmail.com', 'auspic@gmail.com', NULL, '$2y$10$bJSTs2TVvpU5a2FWizEgq.oDQ3NUHyMDWRUK5SIxoAz1mm3.GEPfG', 'AUSPIC GLASS CO', '3173049853', NULL, 'importer', NULL, '2026-01-11 03:03:17', '2026-01-11 03:03:17'),
(18, 'REXIGLASS@GMAIL.COM', 'REXIGLASS@GMAIL.COM', NULL, '$2y$10$QOYbPHNV/Ua8mfeInCbHdOVEScevXrQ9d8NYu9JOFv7oZN6v6oGNu', 'REXIGLASS', '31827726627', NULL, 'importer', NULL, '2026-01-11 21:56:52', '2026-01-11 21:56:52'),
(19, 'RIDERGLASS@GMAIL.COM', 'RIDERGLASS@GMAIL.COM', NULL, '$2y$10$UFS.nlZBiHQccutMxTRKJ.RL7MzUxCtUoEDfqP9P7ASxkvHOdYNCW', 'RIDER GLASS COMPANY', '3173049853', NULL, 'importer', NULL, '2026-01-11 22:09:44', '2026-01-11 22:09:44'),
(20, 'melon@gmail.com', 'melon@gmail.com', NULL, '$2y$10$PpRQqp2jjfvxq7Dt7MczbekVhcb/M.Ro60AvuRQy0zUttUZcvVm66', 'TENGZHOU KUNYUE GLASS', '3182372782', NULL, 'importer', NULL, '2026-01-11 22:21:53', '2026-01-11 22:21:53'),
(21, 'julio@gmail.com', 'julio@gmail.com', NULL, '$2y$10$OS7v/1AxioCiTZVVJ/xrJ.45S0xGtkhEa0PU1XdrWSTO8.7d8OOP2', 'CHEONGFULI (XIAMEN) CO', '13573220238', NULL, 'importer', NULL, '2026-01-12 03:32:48', '2026-01-12 03:32:48');

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
(30, 'VENTAS DIVIDRIOS SAS OPERACIONES JC', 'BUENAVENTURA', 'BUENAVENTURA', '2026-01-11 20:39:45', '2026-01-11 20:39:45');

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `container_product`
--
ALTER TABLE `container_product`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `imports`
--
ALTER TABLE `imports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `import_containers`
--
ALTER TABLE `import_containers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transfer_order_products`
--
ALTER TABLE `transfer_order_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `user_warehouse`
--
ALTER TABLE `user_warehouse`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
