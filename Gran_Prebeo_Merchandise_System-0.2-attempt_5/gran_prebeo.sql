-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 27, 2025 at 08:29 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gran_prebeo`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('male','female') COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('new','active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `gender`, `birth_date`, `phone`, `email`, `address`, `status`, `created_at`, `updated_at`) VALUES
(2, 'Kendrick', 'Vargas', 'male', '2025-11-01', '+639123456789', 'dummy@example.com', 'Davao', 'active', '2025-11-20 12:23:29', '2025-11-27 12:26:06'),
(3, 'Clyde', 'Domingo', 'male', '2000-07-06', '+639876543210', 'dummy2@example.com', 'Tagum', 'new', '2025-11-26 09:50:18', '2025-11-26 09:50:18'),
(4, 'Steffi', 'Asari', 'female', '2025-11-27', '09112233445', 'dummy3@example.com', 'Gensan', 'new', '2025-11-27 09:52:08', '2025-11-27 09:52:08'),
(6, 'Michael', 'Hernandez', 'male', '2025-11-27', '09887766554', 'dummy4@example.com', 'Davao City', 'new', '2025-11-27 12:07:13', '2025-11-27 12:07:13');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int UNSIGNED NOT NULL,
  `invoice_id` int UNSIGNED NOT NULL,
  `order_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tracking_number` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_tracking_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `customer_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `courier` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','shipped','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `delivery_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`id`, `invoice_id`, `order_id`, `invoice_number`, `tracking_number`, `delivery_tracking_id`, `customer_id`, `customer_name`, `address`, `courier`, `status`, `delivery_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 8, '14', 'INV-202511-0001', 'TRK-1C805EAC', 'DLV-20251127-6ED3AB', 3, 'Clyde Domingo', 'Matina Davao, ,  , Philippines', 'Car 1', 'completed', '2025-11-27', NULL, '2025-11-27 10:00:32', '2025-11-27 10:44:21'),
(2, 10, '15', 'INV-202511-0003', 'TRK-507B7512', 'DLV-20251127-7CB935', 4, 'Steffi Asari', 'Davao, ,  , Philippines', 'Car 4', 'completed', '2025-11-27', NULL, '2025-11-27 10:53:43', '2025-11-27 10:55:04'),
(3, 11, '16', 'INV-202511-0002', 'TRK-05562BD3', 'DLV-20251127-CD4FAB', 2, 'Kendrick Vargas', 'Sandawa, Davao City, ,  , Philippines', 'Car 2', 'completed', '2025-11-27', NULL, '2025-11-27 11:08:49', '2025-11-27 12:44:25'),
(4, 12, '17', 'INV-202511-0004', 'TRK-D3790D9D', 'DLV-20251127-1D1BAE', 6, 'Michael Hernandez', 'Bajada, Davao City, ,  , Philippines', 'Car 5', 'completed', '2025-11-27', NULL, '2025-11-27 13:08:20', '2025-11-27 13:08:46'),
(5, 13, '18', 'INV-202511-0005', 'TRK-6030176F', 'DLV-20251127-2666CD', 2, 'Kendrick Vargas', 'Toril, ,  , Philippines', 'Car 3', 'completed', '2025-11-27', 'testing', '2025-11-27 13:12:10', '2025-11-27 13:12:38'),
(6, 14, '19', 'INV-202511-0006', 'TRK-47A7BAF4', 'DLV-20251127-0383AF', 6, 'Michael Hernandez', 'Buhangin, Davao City, ,  , Philippines', 'Car 3', 'shipped', '2025-11-27', 'testing', '2025-11-27 13:14:38', '2025-11-27 13:30:07');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int UNSIGNED NOT NULL,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `customer_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_street` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_city` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_state` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_postal_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_country` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `items_json` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `shipping_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `next_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `terms` text COLLATE utf8mb4_unicode_ci,
  `tracking_number` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `uid`, `invoice_number`, `order_id`, `customer_id`, `customer_name`, `customer_email`, `billing_street`, `billing_city`, `billing_state`, `billing_postal_code`, `billing_country`, `items_json`, `currency`, `subtotal`, `tax_total`, `shipping_total`, `discount_total`, `grand_total`, `status`, `next_status`, `payment_method`, `payment_date`, `issue_date`, `due_date`, `notes`, `terms`, `tracking_number`, `deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(8, '8dde2016-8bad-4153-a967-2d1e1a862781', 'INV-202511-0001', '14', 3, 'Clyde Domingo', 'dummy2@example.com', 'Matina Davao', '', '', '', 'Philippines', '[{\"product_id\":null,\"description\":\"Order #14\",\"quantity\":1,\"unit_price\":1000,\"tax\":0,\"discount\":0,\"line_subtotal\":1000,\"line_total\":1000}]', 'PHP', 1000.00, 0.00, 0.00, 0.00, 1000.00, 'paid', 'pending', NULL, '2025-11-27 10:44:21', '2025-11-27', '2025-12-04', NULL, NULL, 'TRK-1C805EAC', 0, NULL, '2025-11-27 09:43:29', '2025-11-27 10:44:21'),
(10, '98735595-f2c7-4aef-9c6d-101978d1850a', 'INV-202511-0003', '15', 4, 'Steffi Asari', 'dummy3@example.com', 'Davao', '', '', '', 'Philippines', '[{\"product_id\":null,\"description\":\"Order #15\",\"quantity\":1,\"unit_price\":3000,\"tax\":0,\"discount\":0,\"line_subtotal\":3000,\"line_total\":3000}]', 'PHP', 3000.00, 0.00, 0.00, 0.00, 3000.00, 'paid', 'pending', NULL, '2025-11-27 10:54:54', '2025-11-27', '2025-12-04', NULL, NULL, 'TRK-507B7512', 0, NULL, '2025-11-27 10:53:20', '2025-11-27 10:55:04'),
(11, '0ea927c1-c768-42a6-9f0a-2f4009820984', 'INV-202511-0002', '16', 2, 'Kendrick Vargas', 'dummy@example.com', 'Sandawa, Davao City', '', '', '', 'Philippines', '[{\"product_id\":null,\"description\":\"Order #16\",\"quantity\":1,\"unit_price\":10000,\"tax\":0,\"discount\":0,\"line_subtotal\":10000,\"line_total\":10000}]', 'PHP', 10000.00, 0.00, 0.00, 0.00, 10000.00, 'paid', 'pending', NULL, '2025-11-27 12:04:46', '2025-11-27', '2025-12-04', NULL, NULL, 'TRK-05562BD3', 0, NULL, '2025-11-27 11:08:18', '2025-11-27 12:44:25'),
(12, '2687cd07-c531-48e3-9b7f-c8a282c62836', 'INV-202511-0004', '17', 6, 'Michael Hernandez', 'dummy4@example.com', 'Bajada, Davao City', '', '', '', 'Philippines', '[{\"product_id\":null,\"description\":\"Order #17\",\"quantity\":1,\"unit_price\":5000,\"tax\":0,\"discount\":0,\"line_subtotal\":5000,\"line_total\":5000}]', 'PHP', 5000.00, 0.00, 0.00, 0.00, 5000.00, 'paid', 'pending', NULL, '2025-11-27 13:08:46', '2025-11-27', '2025-12-04', NULL, NULL, 'TRK-D3790D9D', 0, NULL, '2025-11-27 13:07:50', '2025-11-27 13:08:46'),
(13, '058f7153-74a0-4cb3-9490-f375d4f60a0e', 'INV-202511-0005', '18', 2, 'Kendrick Vargas', 'dummy@example.com', 'Toril', '', '', '', 'Philippines', '[{\"product_id\":null,\"description\":\"Order #18\",\"quantity\":1,\"unit_price\":20000,\"tax\":0,\"discount\":0,\"line_subtotal\":20000,\"line_total\":20000}]', 'PHP', 20000.00, 0.00, 0.00, 0.00, 20000.00, 'paid', 'pending', NULL, '2025-11-27 13:12:38', '2025-11-27', '2025-12-04', NULL, NULL, 'TRK-6030176F', 0, NULL, '2025-11-27 13:10:55', '2025-11-27 13:12:38'),
(14, '66c9b4d8-569d-4b0e-9a64-ec2822344e80', 'INV-202511-0006', '19', 6, 'Michael Hernandez', 'dummy4@example.com', 'Buhangin, Davao City', '', '', '', 'Philippines', '[{\"product_id\":null,\"description\":\"Order #19\",\"quantity\":1,\"unit_price\":30000,\"tax\":0,\"discount\":0,\"line_subtotal\":30000,\"line_total\":30000}]', 'PHP', 30000.00, 0.00, 0.00, 0.00, 30000.00, 'paid', 'pending', NULL, '2025-11-27 13:14:51', '2025-11-27', '2025-12-04', 'testing', NULL, 'TRK-47A7BAF4', 0, NULL, '2025-11-27 13:13:32', '2025-11-27 13:14:51');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','processing','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `delivery_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `order_date`, `status`, `total_amount`, `delivery_address`, `notes`, `created_at`, `updated_at`) VALUES
(14, 3, '2025-11-27 15:56:00', 'completed', 1000.00, 'Matina Davao', NULL, '2025-11-27 07:56:53', '2025-11-27 02:44:21'),
(15, 4, '2025-11-27 17:53:00', 'completed', 3000.00, 'Davao', NULL, '2025-11-27 09:53:17', '2025-11-27 02:55:04'),
(16, 2, '2025-11-27 18:07:00', 'completed', 10000.00, 'Sandawa, Davao City', NULL, '2025-11-27 10:08:10', '2025-11-27 04:44:25'),
(17, 6, '2025-11-27 20:07:00', 'completed', 5000.00, 'Bajada, Davao City', NULL, '2025-11-27 12:07:45', '2025-11-27 05:08:46'),
(18, 2, '2025-11-27 20:10:00', 'completed', 20000.00, 'Toril', NULL, '2025-11-27 12:10:33', '2025-11-27 05:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `password_hash`, `created_at`, `updated_at`) VALUES
(1, 'Kendrick', 'Vargas', 'kendrick', '$2y$10$BV3ERUQyGgdx.hIb5DcAW.oWBAvF7RpBZSCPWnhaXWqcbLC5brPX6', '2025-11-27 11:22:18', '2025-11-27 11:22:18'),
(2, 'Clyde', 'Dominggo', 'clyde', '$2y$10$Tsf/oTbhMglynaM0FV5NEORnJviSNZlFMLqo6Z/EItYCCCOMuwTDq', '2025-11-27 13:09:45', '2025-11-27 13:09:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customers_email` (`email`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_delivery_invoice` (`invoice_id`),
  ADD KEY `idx_delivery_status` (`status`),
  ADD KEY `idx_delivery_date` (`delivery_date`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_invoice_uid` (`uid`),
  ADD UNIQUE KEY `uq_invoice_number` (`invoice_number`),
  ADD KEY `idx_invoice_customer` (`customer_id`),
  ADD KEY `idx_invoice_order` (`order_id`),
  ADD KEY `idx_invoice_status` (`status`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_customer` (`customer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `fk_deliveries_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
