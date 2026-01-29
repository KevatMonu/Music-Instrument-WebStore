-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 26, 2025 at 01:35 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `musicstore_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

DROP TABLE IF EXISTS `admin_actions`;
CREATE TABLE IF NOT EXISTS `admin_actions` (
  `action_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_ref` int NOT NULL,
  `admin_action` varchar(191) NOT NULL,
  `action_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`action_id`),
  UNIQUE KEY `action_id` (`action_id`),
  KEY `admin_ref` (`admin_ref`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `cart_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_ref` int NOT NULL,
  `product_ref` int NOT NULL,
  `item_quantity` int NOT NULL,
  `purchase_type` enum('buy','rent') NOT NULL,
  `offer_ref` int DEFAULT NULL,
  `added_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `cart_id` (`cart_id`),
  KEY `user_ref` (`user_ref`),
  KEY `product_ref` (`product_ref`),
  KEY `offer_ref` (`offer_ref`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_ref`, `product_ref`, `item_quantity`, `purchase_type`, `offer_ref`, `added_on`) VALUES
(1, 1, 9, 4, 'buy', NULL, '2025-02-25 06:49:59'),
(2, 2, 9, 1, 'buy', NULL, '2025-02-25 06:53:06'),
(3, 1, 8, 2, 'buy', NULL, '2025-02-25 07:53:52');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` varchar(191) NOT NULL,
  `category_description` text,
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category_image_type` varchar(50) DEFAULT NULL,
  `category_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `category_description`, `created_on`, `category_image_type`, `category_image`) VALUES
(61, 'VIOLIN', 'String Instructment', '2025-03-11 01:04:21', 'image/jpeg', 'uploads/67cf8c15a77d8_violin.jpg'),
(60, 'PIANO', 'Key Board Category', '2025-03-11 01:02:20', 'image/jpeg', 'uploads/67cf8b9c36a20_piano.jpg'),
(69, 'FLUTE', 'Wind Instruments', '2025-03-11 04:27:22', 'image/jpeg', 'uploads/67cfbbaa62cc6_flute.jpg'),
(67, 'Guitar', 'String Instruments', '2025-03-11 04:25:12', 'image/jpeg', 'uploads/67cfbb283c49a_electric-guitar.jpg'),
(71, 'tabla', 'Percussion Instruments', '2025-03-11 04:34:43', 'image/jpeg', 'uploads/67cfbd63e5bdb_c-tabla.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
CREATE TABLE IF NOT EXISTS `faqs` (
  `faq_id` int NOT NULL AUTO_INCREMENT,
  `faq_question` varchar(255) NOT NULL,
  `faq_answer` text NOT NULL,
  PRIMARY KEY (`faq_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`faq_id`, `faq_question`, `faq_answer`) VALUES
(1, 'Do you offer instrument repairs?', 'Yes, we offer repair services for most instruments. Please submit a support ticket with details about your repair needs for a quote.'),
(2, 'What payment methods do you accept?', 'We accept all major credit cards (Visa, Mastercard, American Express), PayPal, and bank transfers. All transactions are securely processed.'),
(3, 'Do you have a physical store?', 'Yes, we have physical stores in major cities. Visit our \"Locations\" page to find the store nearest to you.'),
(4, 'How do I reset my password?', 'Click on \"Forgot Password\" on the login page. You will receive an email with instructions to reset your password.'),
(5, 'Can I change my shipping address after placing an order?', 'You can change your shipping address within 24 hours of placing your order by contacting our customer support team.'),
(6, 'What is the estimated delivery time?', 'Standard delivery takes 3-5 business days within the continental US. Express shipping options are available at checkout.'),
(7, 'Do you price match?', 'Yes, we offer price matching for identical items from authorized dealers. Please contact support with details of the competing offer.'),
(8, 'Are there any special discounts for students?', 'We offer a 10% discount for students with valid ID. Register for our student program through your account page.'),
(9, 'How can I apply for financing?', 'Financing options are available for purchases over $500. Select \"Pay with financing\" at checkout to see available plans.'),
(10, 'What happens if my item arrives damaged?', 'Please take photos of the damaged item and packaging, then contact us within 48 hours. We\'ll arrange a replacement or refund.'),
(11, 'Do you sell used or refurbished instruments?', 'Yes, we have a selection of certified pre-owned instruments. These items are thoroughly inspected and come with a 90-day warranty.'),
(12, 'Can I rent instruments instead of buying?', 'We offer rental programs for most instrument categories. Rental periods start at 3 months with an option to apply rental fees toward purchase.');

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

DROP TABLE IF EXISTS `history`;
CREATE TABLE IF NOT EXISTS `history` (
  `history_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_ref` int NOT NULL,
  `order_ref` int NOT NULL,
  `action_type` enum('bought','rented') NOT NULL,
  `action_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  UNIQUE KEY `history_id` (`history_id`),
  KEY `user_ref` (`user_ref`),
  KEY `order_ref` (`order_ref`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

DROP TABLE IF EXISTS `inventory_log`;
CREATE TABLE IF NOT EXISTS `inventory_log` (
  `log_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_ref` int NOT NULL,
  `stock_change` int NOT NULL,
  `change_reason` varchar(191) NOT NULL,
  `log_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  UNIQUE KEY `log_id` (`log_id`),
  KEY `product_ref` (`product_ref`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE IF NOT EXISTS `invoices` (
  `invoice_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_ref` bigint UNSIGNED NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `invoice_date` datetime NOT NULL,
  `invoice_amount` decimal(10,2) NOT NULL,
  `user_ref` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`invoice_id`),
  KEY `order_ref` (`order_ref`),
  KEY `user_ref` (`user_ref`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoice_id`, `order_ref`, `invoice_number`, `invoice_date`, `invoice_amount`, `user_ref`) VALUES
(1, 81, 'INV-2025-03-81', '2025-03-11 06:55:03', 0.00, 15),
(2, 82, 'INV-2025-03-82', '2025-03-11 06:58:07', 271602.00, 17),
(3, 83, 'INV-2025-03-83', '2025-03-11 08:27:47', 123456.00, 17),
(4, 84, 'INV-2025-03-84', '2025-03-11 12:28:31', 36500.00, 17),
(5, 86, 'INV-2025-03-86', '2025-03-11 15:12:37', 25690.00, 17),
(6, 87, 'INV-2025-03-87', '2025-03-11 15:15:37', 76980.00, 19),
(7, 88, 'INV-2025-03-88', '2025-03-22 17:44:26', 25690.00, 17);

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

DROP TABLE IF EXISTS `offers`;
CREATE TABLE IF NOT EXISTS `offers` (
  `offer_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `offer_title` varchar(191) NOT NULL,
  `offer_description` text,
  `discount_mode` enum('flat','percentage') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `applicable_type` enum('product','category','order') NOT NULL,
  `start_on` date NOT NULL,
  `end_on` date NOT NULL,
  `offer_status` enum('active','expired') DEFAULT 'active',
  `offer_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`offer_id`),
  UNIQUE KEY `offer_id` (`offer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_ref` int NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `order_created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `order_status` enum('completed','cancelled') DEFAULT 'completed',
  `order_type` enum('buy','rent') NOT NULL,
  `offer_ref` int DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `user_ref` (`user_ref`),
  KEY `offer_ref` (`offer_ref`)
) ENGINE=MyISAM AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_ref`, `total_cost`, `order_created`, `order_status`, `order_type`, `offer_ref`, `discount_amount`, `invoice_number`) VALUES
(1, 1, 500.00, '2025-02-24 21:12:11', '', '', NULL, 0.00, NULL),
(2, 1, 500.00, '2025-02-24 21:13:33', '', '', NULL, 0.00, NULL),
(5, 2, 500.00, '2025-02-25 01:11:31', '', '', NULL, 0.00, NULL),
(4, 1, 1000.00, '2025-02-24 23:40:23', '', '', NULL, 0.00, NULL),
(6, 2, 76800.00, '2025-02-25 03:40:13', '', '', NULL, 0.00, NULL),
(7, 2, 25600.00, '2025-02-25 04:21:54', '', '', NULL, 0.00, NULL),
(8, 2, 133600.00, '2025-02-25 08:55:49', '', '', NULL, 0.00, NULL),
(9, 2, 153600.00, '2025-02-25 19:37:51', '', '', NULL, 0.00, NULL),
(10, 13, 82400.00, '2025-02-25 19:52:58', '', '', NULL, 0.00, NULL),
(11, 17, 25600.00, '2025-02-26 01:41:56', '', '', NULL, 0.00, NULL),
(12, 17, 25600.00, '2025-02-26 01:42:06', '', '', NULL, 0.00, NULL),
(13, 17, 51200.00, '2025-02-26 01:49:48', '', '', NULL, 0.00, NULL),
(14, 17, 51200.00, '2025-02-26 01:50:07', '', '', NULL, 0.00, NULL),
(15, 17, 51200.00, '2025-02-26 01:52:19', '', '', NULL, 0.00, NULL),
(16, 17, 51200.00, '2025-02-26 01:53:33', '', '', NULL, 0.00, NULL),
(17, 17, 128000.00, '2025-02-26 01:53:50', '', '', NULL, 0.00, NULL),
(18, 17, 25600.00, '2025-02-26 06:01:47', '', '', NULL, 0.00, NULL),
(19, 17, 25600.00, '2025-02-26 06:05:05', '', '', NULL, 0.00, NULL),
(20, 17, 25600.00, '2025-02-26 07:36:51', '', '', NULL, 0.00, NULL),
(21, 17, 25600.00, '2025-02-27 04:12:44', '', '', NULL, 0.00, NULL),
(22, 17, 133600.00, '2025-02-27 05:46:33', '', '', NULL, 0.00, NULL),
(23, 17, 159200.00, '2025-02-27 08:17:47', '', '', NULL, 0.00, NULL),
(24, 17, 159200.00, '2025-02-27 08:17:53', '', '', NULL, 0.00, NULL),
(25, 17, 159200.00, '2025-02-27 08:19:34', '', '', NULL, 0.00, NULL),
(26, 17, 159200.00, '2025-02-27 08:19:38', '', '', NULL, 0.00, NULL),
(27, 17, 159200.00, '2025-02-27 08:19:55', '', '', NULL, 0.00, NULL),
(28, 17, 159200.00, '2025-02-27 08:22:33', '', '', NULL, 0.00, NULL),
(29, 17, 159200.00, '2025-02-27 08:23:09', '', '', NULL, 0.00, NULL),
(30, 17, 159200.00, '2025-02-27 08:23:48', '', '', NULL, 0.00, NULL),
(31, 17, 159200.00, '2025-02-27 08:24:07', '', '', NULL, 0.00, NULL),
(32, 17, 51200.00, '2025-02-27 08:31:25', '', '', NULL, 0.00, NULL),
(33, 17, 51200.00, '2025-02-27 08:32:39', '', '', NULL, 0.00, NULL),
(34, 17, 51200.00, '2025-02-27 08:32:41', '', '', NULL, 0.00, NULL),
(35, 17, 51200.00, '2025-02-27 08:36:24', '', '', NULL, 0.00, NULL),
(36, 17, 51200.00, '2025-02-27 08:36:27', '', '', NULL, 0.00, NULL),
(37, 17, 51200.00, '2025-02-27 08:37:24', '', '', NULL, 0.00, NULL),
(38, 17, 51200.00, '2025-02-27 08:39:27', '', '', NULL, 0.00, NULL),
(39, 17, 51200.00, '2025-02-27 08:42:23', '', '', NULL, 0.00, NULL),
(40, 17, 51200.00, '2025-02-27 08:52:40', 'completed', 'buy', NULL, 0.00, NULL),
(41, 17, 159240.00, '2025-02-27 08:53:03', 'completed', 'buy', NULL, 0.00, NULL),
(42, 17, 25600.00, '2025-02-27 09:07:25', 'completed', 'buy', NULL, 0.00, NULL),
(43, 17, 25000.00, '2025-02-27 19:34:33', 'completed', 'buy', NULL, 0.00, NULL),
(44, 17, 0.00, '2025-02-27 19:40:34', 'completed', 'buy', NULL, 0.00, NULL),
(45, 17, 25000.00, '2025-02-27 19:48:14', 'completed', 'buy', NULL, 0.00, NULL),
(46, 17, 125000.00, '2025-03-01 04:06:09', 'completed', 'buy', NULL, 0.00, NULL),
(47, 15, 77238.00, '2025-03-03 22:32:14', 'completed', 'buy', NULL, 0.00, NULL),
(48, 17, 29853.00, '2025-03-05 02:46:20', 'completed', 'buy', NULL, 0.00, NULL),
(49, 15, 51200.00, '2025-03-05 02:48:54', 'completed', 'buy', NULL, 0.00, NULL),
(50, 17, 33624.00, '2025-03-05 20:09:38', 'completed', 'buy', NULL, 0.00, NULL),
(51, 17, 25600.00, '2025-03-08 01:43:00', 'completed', 'buy', NULL, 0.00, NULL),
(52, 6, 31200.00, '2025-03-08 09:21:20', 'completed', 'buy', NULL, 0.00, NULL),
(53, 17, 31200.00, '2025-03-08 09:24:43', 'completed', 'buy', NULL, 0.00, NULL),
(54, 17, 8024.00, '2025-03-09 11:58:07', 'completed', 'buy', NULL, 0.00, NULL),
(55, 17, 5678.00, '2025-03-10 02:58:23', 'completed', 'buy', NULL, 0.00, NULL),
(56, 15, 1851840.00, '2025-03-10 10:39:07', 'completed', 'buy', NULL, 0.00, NULL),
(57, 17, 250.00, '2025-03-10 13:14:02', 'completed', 'buy', NULL, 0.00, NULL),
(58, 17, 250.00, '2025-03-10 13:42:13', 'completed', 'buy', NULL, 0.00, NULL),
(59, 17, 250.00, '2025-03-10 13:50:24', 'completed', 'buy', NULL, 0.00, NULL),
(60, 17, 250.00, '2025-03-10 14:26:08', 'completed', 'buy', NULL, 0.00, NULL),
(61, 17, 250.00, '2025-03-10 14:45:09', 'completed', 'buy', NULL, 0.00, NULL),
(62, 17, 1500.00, '2025-03-10 15:25:11', 'completed', 'buy', NULL, 0.00, 'INV-1741640111-17'),
(63, 17, 1500.00, '2025-03-10 15:25:50', 'completed', 'buy', NULL, 0.00, 'INV-1741640150-17'),
(64, 17, 250.00, '2025-03-10 15:26:21', 'completed', 'buy', NULL, 0.00, 'INV-1741640181-17'),
(65, 17, 250.00, '2025-03-10 15:27:08', 'completed', 'buy', NULL, 0.00, 'INV-1741640228-17'),
(66, 17, 250.00, '2025-03-10 18:38:24', 'completed', 'buy', NULL, 0.00, 'INV-1741651704-17'),
(67, 17, 0.00, '2025-03-10 18:42:35', 'completed', 'buy', NULL, 0.00, 'INV-1741651955-17'),
(68, 17, 0.00, '2025-03-10 18:44:14', 'completed', 'buy', NULL, 0.00, 'INV-1741652054-17'),
(69, 17, 0.00, '2025-03-10 18:45:10', 'completed', 'buy', NULL, 0.00, 'INV-1741652110-17'),
(70, 17, 1000.00, '2025-03-10 18:50:15', 'completed', 'buy', NULL, 0.00, 'INV-1741652415-17'),
(71, 17, 750.00, '2025-03-10 18:59:32', 'completed', 'buy', NULL, 0.00, 'INV-1741652972-17'),
(72, 17, 250.00, '2025-03-10 19:03:23', 'completed', 'buy', NULL, 0.00, 'INV-1741653203-17'),
(73, 15, 250.00, '2025-03-10 19:07:24', 'completed', 'buy', NULL, 0.00, 'INV-1741653444-15'),
(74, 17, 250.00, '2025-03-10 19:09:14', 'completed', 'buy', NULL, 0.00, 'INV-1741653554-17'),
(75, 17, 250.00, '2025-03-10 19:21:19', 'completed', 'buy', NULL, 0.00, 'INV-1741654279-17'),
(76, 17, 250.00, '2025-03-10 19:22:59', 'completed', 'buy', NULL, 0.00, 'INV-1741654379-17'),
(77, 15, 283947.00, '2025-03-10 19:38:53', 'completed', 'buy', NULL, 0.00, NULL),
(78, 15, 0.00, '2025-03-10 19:39:06', 'completed', 'buy', NULL, 0.00, 'INV-1741655346-15'),
(79, 15, 123456.00, '2025-03-10 19:39:42', 'completed', 'buy', NULL, 0.00, 'INV-1741655382-15'),
(80, 15, 0.00, '2025-03-10 19:40:06', 'completed', 'buy', NULL, 0.00, NULL),
(81, 15, 0.00, '2025-03-10 19:55:01', 'completed', 'buy', NULL, 0.00, NULL),
(82, 17, 271602.00, '2025-03-10 19:58:03', 'completed', 'buy', NULL, 0.00, NULL),
(83, 17, 123456.00, '2025-03-10 21:27:44', 'completed', 'buy', NULL, 0.00, NULL),
(84, 17, 36500.00, '2025-03-11 01:28:29', 'completed', 'buy', NULL, 0.00, NULL),
(85, 17, 51290.00, '2025-03-11 02:09:43', 'completed', 'buy', NULL, 0.00, NULL),
(86, 17, 25690.00, '2025-03-11 04:12:32', 'completed', 'buy', NULL, 0.00, NULL),
(87, 19, 76980.00, '2025-03-11 04:15:35', 'completed', 'buy', NULL, 0.00, NULL),
(88, 17, 25690.00, '2025-03-22 06:44:24', 'completed', 'buy', NULL, 0.00, NULL),
(89, 17, 25690.00, '2025-03-22 09:02:09', 'completed', 'buy', NULL, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_ref` int NOT NULL,
  `product_ref` int NOT NULL,
  `item_quantity` int NOT NULL,
  `item_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  UNIQUE KEY `order_item_id` (`order_item_id`),
  KEY `order_ref` (`order_ref`),
  KEY `product_ref` (`product_ref`)
) ENGINE=MyISAM AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_ref`, `product_ref`, `item_quantity`, `item_price`) VALUES
(1, 40, 15, 2, 25600.00),
(2, 41, 15, 1, 25600.00),
(3, 41, 16, 1, 25600.00),
(4, 41, 17, 1, 25600.00),
(5, 41, 18, 1, 56800.00),
(6, 41, 20, 1, 25640.00),
(7, 42, 15, 1, 25600.00),
(8, 43, 21, 1, 25000.00),
(9, 45, 21, 1, 25000.00),
(10, 46, 21, 5, 25000.00),
(11, 47, 31, 2, 24253.00),
(12, 47, 30, 3, 244.00),
(13, 47, 29, 5, 5600.00),
(14, 48, 31, 1, 24253.00),
(15, 48, 29, 1, 5600.00),
(16, 49, 32, 2, 25600.00),
(17, 50, 32, 1, 25600.00),
(18, 50, 28, 1, 2424.00),
(19, 50, 27, 1, 5600.00),
(20, 51, 32, 1, 25600.00),
(21, 52, 32, 1, 25600.00),
(22, 52, 29, 1, 5600.00),
(23, 53, 32, 1, 25600.00),
(24, 53, 29, 1, 5600.00),
(25, 54, 29, 1, 5600.00),
(26, 54, 28, 1, 2424.00),
(27, 55, 56, 1, 5678.00),
(28, 56, 57, 15, 123456.00),
(29, 57, 66, 1, 250.00),
(30, 58, 66, 1, 250.00),
(31, 59, 66, 1, 250.00),
(32, 60, 66, 1, 250.00),
(33, 61, 66, 1, 250.00),
(34, 63, 66, 6, 250.00),
(35, 64, 66, 1, 250.00),
(36, 65, 66, 1, 250.00),
(37, 66, 66, 1, 250.00),
(38, 70, 66, 4, 250.00),
(39, 71, 66, 3, 250.00),
(40, 72, 66, 1, 250.00),
(41, 73, 66, 1, 250.00),
(42, 74, 66, 1, 250.00),
(43, 75, 66, 1, 250.00),
(44, 76, 66, 1, 250.00),
(45, 77, 67, 3, 12345.00),
(46, 77, 68, 2, 123456.00),
(47, 79, 68, 1, 123456.00),
(48, 82, 68, 2, 123456.00),
(49, 82, 67, 2, 12345.00),
(50, 83, 68, 1, 123456.00),
(51, 84, 71, 1, 36500.00),
(52, 85, 73, 1, 25690.00),
(53, 85, 72, 1, 25600.00),
(54, 86, 73, 1, 25690.00),
(55, 87, 73, 2, 25690.00),
(56, 87, 72, 1, 25600.00),
(57, 88, 73, 1, 25690.00),
(58, 89, 73, 1, 25690.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_shipping`
--

DROP TABLE IF EXISTS `order_shipping`;
CREATE TABLE IF NOT EXISTS `order_shipping` (
  `shipping_id` int NOT NULL AUTO_INCREMENT,
  `order_ref` bigint UNSIGNED NOT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_state` varchar(100) NOT NULL,
  `shipping_pincode` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`shipping_id`),
  KEY `order_ref` (`order_ref`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_shipping`
--

INSERT INTO `order_shipping` (`shipping_id`, `order_ref`, `shipping_address`, `shipping_city`, `shipping_state`, `shipping_pincode`, `created_at`) VALUES
(1, 63, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-10 20:55:50'),
(2, 64, 'Sus GOna', 'dvsbv', 'dsb', 'sbdbd', '2025-03-10 20:56:21'),
(3, 65, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-10 20:57:08'),
(4, 66, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 00:08:24'),
(5, 67, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 00:12:35'),
(6, 68, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 00:14:14'),
(7, 69, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 00:15:10'),
(8, 70, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 00:20:15'),
(9, 71, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 00:29:32'),
(10, 72, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 00:33:23'),
(11, 73, 'Monuefwefw, Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India', 'pune', 'PUNE', '411021', '2025-03-11 00:37:24'),
(12, 74, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 00:39:14'),
(13, 75, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 00:51:19'),
(14, 76, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 00:52:59'),
(15, 77, 'Monuefwefw, Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India', 'pune', 'PUNE', '411021', '2025-03-11 01:08:53'),
(16, 78, 'Monuefwefw, Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India', 'pune', 'PUNE', '411021', '2025-03-11 01:09:06'),
(17, 79, 'Monuefwefw, Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India', 'pune', 'PUNE', '411021', '2025-03-11 01:09:42'),
(18, 80, 'Monuefwefw, Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India', 'pune', 'PUNE', '411021', '2025-03-11 01:10:06'),
(19, 81, 'Monuefwefw, Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India', 'pune', 'PUNE', '411021', '2025-03-11 01:25:01'),
(20, 82, 'Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India', 'City ', 'MAHARASHTRA', '411021', '2025-03-11 01:28:03'),
(21, 83, 'Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India', 'Pune', 'MAHARASHTRA', '411021', '2025-03-11 02:57:44'),
(22, 84, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 06:58:29'),
(23, 85, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-11 07:39:43'),
(24, 86, 'Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India', 'Pune', 'MAHARASHTRA', '411021', '2025-03-11 09:42:32'),
(25, 87, 'Sus GOan, Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India', 'PUNE', 'PUNE', '411021', '2025-03-11 09:45:35'),
(26, 88, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-22 12:14:24'),
(27, 89, 'PUn e', 'dvsbv', 'dsb', 'sbdbd', '2025-03-22 14:32:09');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_ref` int NOT NULL,
  `paid_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_mode` enum('credit_card','UPI','net_banking','cash') NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('success','failed') DEFAULT 'success',
  `payment_details` text COMMENT 'JSON encoded payment details specific to payment method',
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  KEY `order_ref` (`order_ref`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_ref`, `paid_on`, `payment_mode`, `payment_amount`, `payment_status`, `payment_details`) VALUES
(1, 57, '2025-03-10 18:44:02', 'UPI', 250.00, 'success', NULL),
(2, 58, '2025-03-10 19:12:13', 'credit_card', 250.00, 'success', NULL),
(3, 59, '2025-03-10 19:20:24', '', 250.00, 'success', NULL),
(4, 60, '2025-03-10 19:56:08', 'cash', 250.00, 'success', '[]'),
(5, 61, '2025-03-10 20:15:09', 'net_banking', 250.00, 'success', '{\"bank_name\":\"HDFC\",\"account_number\":\"25678956332\"}'),
(6, 63, '2025-03-10 20:55:50', 'UPI', 1500.00, 'success', '{\"upi_id\":\"\"}'),
(7, 64, '2025-03-10 20:56:21', 'UPI', 250.00, 'success', '{\"upi_id\":\"\"}'),
(8, 65, '2025-03-10 20:57:08', 'UPI', 250.00, 'success', '{\"upi_id\":\"\"}'),
(9, 66, '2025-03-11 00:08:24', 'UPI', 250.00, 'success', '{\"upi_id\":\"\"}'),
(10, 67, '2025-03-11 00:12:35', 'UPI', 0.00, 'success', '{\"upi_id\":\"\"}'),
(11, 68, '2025-03-11 00:14:14', 'UPI', 0.00, 'success', '{\"upi_id\":\"\"}'),
(12, 69, '2025-03-11 00:15:10', 'UPI', 0.00, 'success', '{\"upi_id\":\"\"}'),
(13, 70, '2025-03-11 00:20:15', 'UPI', 1000.00, 'success', '{\"upi_id\":\"\"}'),
(14, 71, '2025-03-11 00:29:32', 'UPI', 750.00, 'success', '{\"upi_id\":\"\"}'),
(15, 72, '2025-03-11 00:33:23', 'UPI', 250.00, 'success', '{\"upi_id\":\"\"}'),
(16, 73, '2025-03-11 00:37:24', 'UPI', 250.00, 'success', '{\"upi_id\":\"\"}'),
(17, 74, '2025-03-11 00:39:14', 'UPI', 250.00, 'success', '{\"upi_id\":\"\"}'),
(18, 75, '2025-03-11 00:51:19', 'UPI', 250.00, 'success', '{\"upi_id\":\"\"}'),
(19, 76, '2025-03-11 00:52:59', 'UPI', 250.00, 'success', '{\"upi_id\":\"\"}'),
(20, 77, '2025-03-11 01:08:53', 'UPI', 283947.00, 'success', '{\"upi_id\":\"\"}'),
(21, 78, '2025-03-11 01:09:06', 'UPI', 0.00, 'success', '{\"upi_id\":\"\"}'),
(22, 79, '2025-03-11 01:09:42', 'UPI', 123456.00, 'success', '{\"upi_id\":\"\"}'),
(23, 80, '2025-03-11 01:10:06', 'UPI', 0.00, 'success', '{\"upi_id\":\"\"}'),
(24, 81, '2025-03-11 01:25:01', 'UPI', 0.00, 'success', '{\"upi_id\":\"\"}'),
(25, 82, '2025-03-11 01:28:03', 'cash', 271602.00, 'success', '[]'),
(26, 83, '2025-03-11 02:57:44', 'UPI', 123456.00, 'success', '{\"upi_id\":\"\"}'),
(27, 84, '2025-03-11 06:58:29', 'cash', 36500.00, 'success', '[]'),
(28, 85, '2025-03-11 07:39:43', 'cash', 51290.00, 'success', '[]'),
(29, 86, '2025-03-11 09:42:32', 'cash', 25690.00, 'success', '[]'),
(30, 87, '2025-03-11 09:45:35', 'cash', 76980.00, 'success', '[]'),
(31, 88, '2025-03-22 12:14:24', 'UPI', 25690.00, 'success', '{\"upi_id\":\"\"}'),
(32, 89, '2025-03-22 14:32:09', 'credit_card', 25690.00, 'success', '{\"card_number\":\"\",\"card_name\":\"\",\"card_expiry\":\"\",\"card_cvv\":\"\"}');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_name` varchar(191) NOT NULL,
  `product_description` text,
  `category_ref` int DEFAULT NULL,
  `product_price` decimal(10,2) DEFAULT NULL,
  `rental_cost` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int DEFAULT '0',
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `image_type` varchar(50) DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `product_id` (`product_id`),
  KEY `category_ref` (`category_ref`)
) ENGINE=MyISAM AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `product_description`, `category_ref`, `product_price`, `rental_cost`, `stock_quantity`, `created_on`, `image_type`, `product_image`) VALUES
(73, 'paino', 'nice', 60, 25690.00, NULL, 100, '2025-03-11 07:02:43', '0', 'uploads/67cfe013737d4_grand piano.jpg'),
(72, 'Retro Volin', 'Elegant bowed instrument with rich, expressive, and melodious sound. 🎻✨', 61, 25600.00, NULL, 100, '2025-03-11 04:44:28', '0', 'uploads/67cfbfac48f2b_hover-Violin.jpg'),
(71, 'Classical Guitra', 'Traditional wooden guitar with warm tones and smooth playability. 🎸🎶', 67, 36500.00, NULL, 210, '2025-03-11 04:43:24', '0', 'uploads/67cfbf6c47d28_Beginner-Classical-Guitar-2.webp'),
(69, '18-Pipes-Pan Flue', 'Handcrafted wooden flute with smooth, melodic, and soothing tones. 🎶', 69, 250.00, NULL, 100, '2025-03-11 04:41:26', '0', 'uploads/67cfbef63e6d1_18-Pipes-Pan-Flute-F-Key-1.webp'),
(70, 'Grand White paino', 'legant, full-sized piano with rich, resonant, and dynamic sound. 🎹✨', 60, 256000.00, NULL, 100, '2025-03-11 04:42:35', '0', 'uploads/67cfbf3b42681_grand piano.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

DROP TABLE IF EXISTS `rentals`;
CREATE TABLE IF NOT EXISTS `rentals` (
  `rental_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_ref` int NOT NULL,
  `rental_start` date NOT NULL,
  `rental_end` date NOT NULL,
  `rental_status` enum('active','returned') DEFAULT 'active',
  PRIMARY KEY (`rental_id`),
  UNIQUE KEY `rental_id` (`rental_id`),
  KEY `order_ref` (`order_ref`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `ticket_id` int NOT NULL AUTO_INCREMENT,
  `user_ref` int NOT NULL,
  `ticket_subject` varchar(255) NOT NULL,
  `ticket_category` varchar(50) NOT NULL,
  `ticket_description` text NOT NULL,
  `ticket_priority` varchar(20) NOT NULL,
  `ticket_status` varchar(20) NOT NULL DEFAULT 'Open',
  `created_on` datetime NOT NULL,
  PRIMARY KEY (`ticket_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` varchar(191) NOT NULL,
  `email_address` varchar(191) NOT NULL,
  `user_password` varchar(191) NOT NULL,
  `user_role` enum('customer','admin') DEFAULT 'customer',
  `user_image` varchar(255) DEFAULT NULL,
  `created_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_address` varchar(255) DEFAULT NULL,
  `admin_code` varchar(50) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `email_address` (`email_address`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email_address`, `user_password`, `user_role`, `user_image`, `created_on`, `user_address`, `admin_code`, `phone_number`) VALUES
(15, 'harshal', 'hk@gmail.com', '$2y$10$xEpS7DL79RYdFoGdnzadmuorWS9No5z69cnHg3tc0Otfi2h61jygO', 'admin', 'headphones.jpg', '2025-02-26 01:54:28', 'Monuefwefw, Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India, pune, PUNE', NULL, NULL),
(17, 'monu kevat', 'mk@gmail.com', '$2y$10$SVXr5MIHe7aIZCUzzgdDv.G0IZDPe2KX19ykpYyjchMOkf8MdP1Vq', 'customer', NULL, '2025-02-26 02:21:28', 'PUn e, dvsbv, dsb - sbdbd', NULL, '7219732769'),
(19, 'harshal', 'hp@gmail.com', '$2y$10$lKDMgJVRUCc1ZHuiNIJhyOvZik4nn2VNPLKiv/IaTW6Ou50iaBkXy', 'customer', NULL, '2025-03-11 09:44:58', 'Sus GOan, Raj Trader, Near Shaik College Road Bhagwati nagar parkhe vasti sus Goan 411021 PUNE, MAHARASHTRA 411008 India, , PUNE', NULL, '1234567890');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
