-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 28, 2026 at 03:40 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lendly`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Tools & Equipment', 'tools-equipment', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(2, 1, 'Power Tools', 'power-tools', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(3, 1, 'Pressure Washers', 'pressure-washers', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(4, 1, 'Ladders', 'ladders', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(5, NULL, 'Photography & Video', 'photography-video', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(6, 5, 'Cameras', 'cameras', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(7, 5, 'Lenses', 'lenses', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(8, 5, 'Lighting', 'lighting', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(9, NULL, 'Events & Party', 'events-party', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(10, 9, 'Tents & Canopies', 'tents-canopies', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(11, 9, 'Sound Systems', 'sound-systems', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(12, 9, 'Decor', 'decor', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(13, NULL, 'Outdoor & Sports', 'outdoor-sports', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(14, 13, 'Camping Gear', 'camping-gear', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(15, 13, 'Bicycles', 'bicycles', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(16, 13, 'Water Sports', 'water-sports', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(17, NULL, 'Vehicles', 'vehicles', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(18, 17, 'Cars', 'cars', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(19, 17, 'Motorcycles', 'motorcycles', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(20, NULL, 'Electronics', 'electronics', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(21, 20, 'Projectors', 'projectors', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(22, 20, 'Drones', 'drones', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(23, NULL, 'Home Appliances', 'home-appliances', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(24, 23, 'Cleaning Equipment', 'cleaning-equipment', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(25, 23, 'Kitchen Appliances', 'kitchen-appliances', '2026-09-07 08:12:31', '2026-09-07 08:12:31');

-- --------------------------------------------------------

--
-- Table structure for table `commission_settings`
--

CREATE TABLE `commission_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 10.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commission_settings`
--

INSERT INTO `commission_settings` (`id`, `commission_rate`, `created_at`, `updated_at`) VALUES
(1, 10.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31');

-- --------------------------------------------------------

--
-- Table structure for table `condition_records`
--

CREATE TABLE `condition_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rental_id` bigint(20) UNSIGNED NOT NULL,
  `recorded_by` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `condition` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `has_damage` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `condition_records`
--

INSERT INTO `condition_records` (`id`, `rental_id`, `recorded_by`, `type`, `condition`, `notes`, `has_damage`, `created_at`, `updated_at`) VALUES
(1, 7, 2, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(2, 8, 4, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(3, 9, 5, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(4, 10, 6, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(5, 11, 7, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(6, 12, 8, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(7, 13, 9, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(8, 14, 10, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(9, 14, 10, 'after', 'good', 'Item returned with a cracked component.', 1, '2026-09-07 08:12:31', '2026-09-20 07:40:16'),
(10, 15, 11, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(11, 15, 11, 'after', 'good', 'Returned in the same condition as handed over.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(12, 16, 12, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(13, 16, 12, 'after', 'good', 'Returned in the same condition as handed over.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(14, 17, 13, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(15, 17, 13, 'after', 'good', 'Small scuff noticed on return, otherwise functional.', 1, '2026-09-07 08:12:31', '2026-09-20 07:40:16'),
(16, 18, 2, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(17, 18, 2, 'after', 'good', 'Returned in the same condition as handed over.', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(18, 21, 2, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(19, 21, 2, 'after', 'good', 'Returned in the same condition as handed over.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(20, 22, 2, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(21, 23, 2, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(22, 25, 2, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(23, 25, 2, 'after', 'good', 'Returned in the same condition as handed over.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(24, 26, 2, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(25, 27, 2, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(26, 29, 12, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(27, 31, 4, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(28, 31, 4, 'after', 'good', 'Returned in the same condition as handed over.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(29, 32, 9, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(30, 33, 2, 'before', 'good', 'Item handed over in good condition.', 0, '2026-09-20 08:07:29', '2026-09-20 08:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `condition_record_photos`
--

CREATE TABLE `condition_record_photos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `condition_record_id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `damage_reports`
--

CREATE TABLE `damage_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rental_id` bigint(20) UNSIGNED NOT NULL,
  `condition_record_id` bigint(20) UNSIGNED DEFAULT NULL,
  `damage_type` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `estimated_repair_cost` decimal(10,2) NOT NULL,
  `proposed_deduction` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `renter_response_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `damage_reports`
--

INSERT INTO `damage_reports` (`id`, `rental_id`, `condition_record_id`, `damage_type`, `description`, `estimated_repair_cost`, `proposed_deduction`, `status`, `renter_response_notes`, `created_at`, `updated_at`) VALUES
(1, 17, 15, 'Cosmetic scratch', 'A visible scratch on the casing was noted during the return inspection. Item still functions normally.', 800.00, 500.00, 'accepted', 'I agree, that happened during transport. Fine with the deduction.', '2026-09-20 07:40:16', '2026-09-20 07:40:16'),
(2, 14, 9, 'Cracked housing', 'The item was returned with a visible crack that was not present at handoff. Repair estimate attached.', 2500.00, 2000.00, 'disputed', 'This damage was already there when I picked it up — I have photos from before the rental.', '2026-09-20 07:40:16', '2026-09-20 07:40:16');

-- --------------------------------------------------------

--
-- Table structure for table `damage_report_photos`
--

CREATE TABLE `damage_report_photos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `damage_report_id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rental_id` bigint(20) UNSIGNED NOT NULL,
  `damage_report_id` bigint(20) UNSIGNED DEFAULT NULL,
  `raised_by` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `resolution` varchar(255) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `resolved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disputes`
--

INSERT INTO `disputes` (`id`, `rental_id`, `damage_report_id`, `raised_by`, `reason`, `description`, `status`, `resolution`, `resolution_notes`, `resolved_by`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 14, 2, 22, 'item_damaged', 'Renter disputes the damage deduction, claiming the item was already damaged before pickup.', 'open', NULL, NULL, NULL, NULL, '2026-09-20 07:40:16', '2026-09-20 07:40:16'),
(2, 15, NULL, 23, 'incorrect_charge', 'Renter believes the late fee applied does not match the actual return time.', 'open', NULL, NULL, NULL, NULL, '2026-09-20 07:40:16', '2026-09-20 07:40:16');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `listings`
--

CREATE TABLE `listings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `subcategory_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `condition` varchar(255) NOT NULL,
  `purchase_year` smallint(5) UNSIGNED DEFAULT NULL,
  `estimated_original_price` decimal(10,2) DEFAULT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `price_per_hour` decimal(10,2) DEFAULT NULL,
  `price_per_week` decimal(10,2) DEFAULT NULL,
  `security_deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `location` varchar(255) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `pickup_available` tinyint(1) NOT NULL DEFAULT 1,
  `delivery_available` tinyint(1) NOT NULL DEFAULT 0,
  `rental_rules` text DEFAULT NULL,
  `max_rental_duration_days` smallint(5) UNSIGNED NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `status` varchar(255) NOT NULL DEFAULT 'pending_approval',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `views_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `listings`
--

INSERT INTO `listings` (`id`, `owner_id`, `category_id`, `subcategory_id`, `name`, `brand`, `model`, `description`, `condition`, `purchase_year`, `estimated_original_price`, `price_per_day`, `price_per_hour`, `price_per_week`, `security_deposit`, `location`, `latitude`, `longitude`, `pickup_available`, `delivery_available`, `rental_rules`, `max_rental_duration_days`, `is_available`, `status`, `rejection_reason`, `views_count`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 2, 1, 3, 'Bosch Pressure Washer', 'Bosch', NULL, 'Powerful electric pressure washer, great for cleaning cars, patios, and driveways.', 'excellent', NULL, 8000.00, 450.00, NULL, NULL, 1000.00, 'Batangas City', 13.7565000, 121.0583000, 1, 0, NULL, 7, 1, 'published', NULL, 2, '2026-09-07 08:12:31', '2026-09-20 22:08:05', NULL),
(2, 4, 5, 6, 'Canon EOS M50 Mirrorless Camera', 'Canon', NULL, 'Compact mirrorless camera with 4K video, perfect for events and travel vlogging.', 'like_new', NULL, 35000.00, 800.00, NULL, NULL, 5000.00, 'Lipa City, Batangas', 13.9411000, 121.1622000, 1, 0, NULL, 5, 1, 'published', NULL, 3, '2026-09-07 08:12:31', '2026-09-20 22:08:06', NULL),
(3, 5, 13, 14, '4-Person Camping Tent', 'Coleman', NULL, 'Spacious weatherproof tent, easy to set up, ideal for weekend camping trips.', 'good', NULL, 6000.00, 300.00, NULL, NULL, 500.00, 'Tanauan City, Batangas', 14.0866000, 121.1497000, 1, 0, NULL, 10, 1, 'published', NULL, 1, '2026-09-07 08:12:31', '2026-09-20 22:08:07', NULL),
(4, 6, 9, 11, 'JBL PartyBox Speaker Set', 'JBL', NULL, 'Loud, portable party speakers with built-in lights, ideal for birthdays and small events.', 'excellent', NULL, 15000.00, 700.00, NULL, NULL, 2000.00, 'Sto. Tomas, Batangas', 14.1078000, 121.1414000, 1, 0, NULL, 3, 1, 'published', NULL, 1, '2026-09-07 08:12:31', '2026-09-20 22:08:08', NULL),
(5, 7, 17, 19, 'Honda Click 125i Scooter', 'Honda', NULL, 'Fuel-efficient automatic scooter, well maintained, complete with two helmets.', 'good', NULL, 90000.00, 900.00, NULL, NULL, 8000.00, 'Nasugbu, Batangas', 14.0722000, 120.6317000, 1, 0, NULL, 14, 1, 'published', NULL, 1, '2026-09-07 08:12:31', '2026-09-20 22:08:09', NULL),
(6, 8, 20, 21, 'Epson Home Cinema Projector', 'Epson', NULL, 'Bright full-HD projector, perfect for movie nights and small presentations.', 'like_new', NULL, 28000.00, 600.00, NULL, NULL, 3000.00, 'Taal, Batangas', 13.8783000, 120.9986000, 1, 0, NULL, 5, 1, 'published', NULL, 1, '2026-09-07 08:12:31', '2026-09-20 22:08:09', NULL),
(7, 9, 23, 25, 'KitchenAid Stand Mixer', 'KitchenAid', NULL, 'Heavy-duty stand mixer, great for baking projects and small home bakeries.', 'excellent', NULL, 22000.00, 350.00, NULL, NULL, 1500.00, 'Lemery, Batangas', 13.9139000, 120.8825000, 1, 0, NULL, 7, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(8, 10, 13, 15, 'Trek Mountain Bike', 'Trek', NULL, 'Sturdy mountain bike with front suspension, ready for trail rides.', 'good', NULL, 32000.00, 500.00, NULL, NULL, 3000.00, 'San Juan, Batangas', 13.8275000, 121.3958000, 1, 0, NULL, 7, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(9, 11, 1, 2, 'DeWalt Cordless Drill Set', 'DeWalt', NULL, 'Cordless drill/driver combo kit with two batteries and a carrying case.', 'excellent', NULL, 12000.00, 350.00, NULL, NULL, 800.00, 'Bauan, Batangas', 13.7925000, 121.0083000, 1, 0, NULL, 7, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(10, 12, 1, 4, 'Aluminum Extension Ladder 12ft', 'Werner', NULL, 'Lightweight aluminum extension ladder, safe up to 250 lbs.', 'good', NULL, 5000.00, 200.00, NULL, NULL, 300.00, 'Calaca, Batangas', 13.9319000, 120.8144000, 1, 0, NULL, 5, 1, 'published', NULL, 1, '2026-09-07 08:12:31', '2026-09-20 22:08:42', NULL),
(11, 13, 5, 7, 'Sony 50mm f1.8 Lens', 'Sony', NULL, 'Fast prime lens, great for portraits and low-light shooting.', 'like_new', NULL, 15000.00, 400.00, NULL, NULL, 3000.00, 'Calatagan, Batangas', 13.8306000, 120.6325000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(12, 2, 5, 8, 'Godox Studio Light Kit', 'Godox', NULL, 'Two-softbox studio lighting kit with stands, ideal for product photography.', 'excellent', NULL, 18000.00, 500.00, NULL, NULL, 2000.00, 'Lian, Batangas', 14.0392000, 120.6417000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(13, 4, 9, 10, '10x10 Event Canopy Tent', 'Coleman', NULL, 'Pop-up canopy tent, great for outdoor parties, market stalls, and events.', 'good', NULL, 9000.00, 400.00, NULL, NULL, 1000.00, 'Mabini, Batangas', 13.7472000, 120.9214000, 1, 0, NULL, 3, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(14, 5, 9, 12, 'LED Balloon Arch Kit', 'Generic', NULL, 'Reusable balloon arch frame with LED string lights, easy setup.', 'excellent', NULL, 4000.00, 250.00, NULL, NULL, 300.00, 'Tuy, Batangas', 13.9928000, 120.7275000, 1, 0, NULL, 3, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(15, 6, 13, 16, 'Inflatable Kayak 2-Seater', 'Intex', NULL, 'Two-person inflatable kayak with paddles and a foot pump, packs into a duffel bag.', 'good', NULL, 10000.00, 450.00, NULL, NULL, 1500.00, 'Rosario, Batangas', 13.8461000, 121.2094000, 1, 0, NULL, 3, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(16, 7, 17, 18, 'Toyota Vios 2022', 'Toyota', NULL, 'Fuel-efficient sedan, automatic transmission, well-maintained with complete documents.', 'excellent', NULL, 750000.00, 2000.00, NULL, NULL, 15000.00, 'San Jose, Batangas', 13.8792000, 121.1058000, 1, 0, NULL, 10, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(17, 8, 20, 22, 'DJI Mini 3 Pro Drone', 'DJI', NULL, 'Compact 4K drone with obstacle avoidance, extra battery included.', 'like_new', NULL, 45000.00, 900.00, NULL, NULL, 5000.00, 'Ibaan, Batangas', 13.8189000, 121.1339000, 1, 0, NULL, 3, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(18, 9, 23, 24, 'Karcher Steam Cleaner', 'Karcher', NULL, 'High-powered steam cleaner for upholstery, tiles, and kitchen surfaces.', 'good', NULL, 7000.00, 300.00, NULL, NULL, 500.00, 'Cuenca, Batangas', 13.9014000, 121.0525000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(19, 10, 1, 2, 'Makita Angle Grinder', 'Makita', NULL, 'Compact angle grinder for cutting and grinding metal, tile, and concrete.', 'good', NULL, 6000.00, 250.00, NULL, NULL, 500.00, 'Malvar, Batangas', 14.0522000, 121.1583000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(20, 11, 1, 3, 'Karcher K5 Pressure Washer', 'Karcher', NULL, 'High-pressure electric washer with patio cleaner attachment, barely used.', 'like_new', NULL, 12000.00, 500.00, NULL, NULL, 1200.00, 'Batangas City', 13.7565000, 121.0583000, 1, 0, NULL, 7, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(21, 12, 1, 4, 'Step Ladder 6ft', 'Werner', NULL, 'Sturdy A-frame step ladder, ideal for home repairs and painting jobs.', 'good', NULL, 2500.00, 150.00, NULL, NULL, 200.00, 'Lipa City, Batangas', 13.9411000, 121.1622000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(22, 13, 5, 6, 'Sony A7III Mirrorless Camera', 'Sony', NULL, 'Full-frame mirrorless camera, excellent low-light performance, body only.', 'excellent', NULL, 90000.00, 1500.00, NULL, NULL, 10000.00, 'Tanauan City, Batangas', 14.0866000, 121.1497000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(23, 2, 5, 7, 'Canon 24-70mm f2.8 Lens', 'Canon', NULL, 'Professional standard zoom lens, sharp across the whole range, well maintained.', 'excellent', NULL, 60000.00, 900.00, NULL, NULL, 8000.00, 'Sto. Tomas, Batangas', 14.1078000, 121.1414000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(24, 4, 5, 8, 'Neewer Ring Light Kit', 'Neewer', NULL, '18-inch LED ring light with stand and phone holder, great for content creation.', 'good', NULL, 5000.00, 250.00, NULL, NULL, 500.00, 'Nasugbu, Batangas', 14.0722000, 120.6317000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(25, 5, 9, 10, 'White Wedding Tent 20x30', 'Generic', NULL, 'Large white event tent, seats up to 60 guests, includes side walls.', 'good', NULL, 40000.00, 1500.00, NULL, NULL, 5000.00, 'Taal, Batangas', 13.8783000, 120.9986000, 1, 0, NULL, 3, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(26, 6, 9, 11, 'Bose S1 Pro PA System', 'Bose', NULL, 'Portable battery-powered PA speaker, clear sound for small gatherings and speeches.', 'excellent', NULL, 30000.00, 1000.00, NULL, NULL, 3000.00, 'Lemery, Batangas', 13.9139000, 120.8825000, 1, 0, NULL, 3, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(27, 7, 9, 12, 'Backdrop Stand with Fairy Lights', 'Generic', NULL, 'Adjustable backdrop stand with warm fairy lights, perfect for photo corners.', 'good', NULL, 3000.00, 200.00, NULL, NULL, 200.00, 'San Juan, Batangas', 13.8275000, 121.3958000, 1, 0, NULL, 3, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(28, 8, 13, 14, '8-Person Family Tent', 'Coleman', NULL, 'Roomy family-sized tent with room divider, great for group camping trips.', 'excellent', NULL, 12000.00, 500.00, NULL, NULL, 1000.00, 'Bauan, Batangas', 13.7925000, 121.0083000, 1, 0, NULL, 10, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(29, 9, 13, 15, 'Foldable City Bike', 'Dahon', NULL, 'Compact foldable bike, easy to carry and store, good for commuting.', 'good', NULL, 15000.00, 300.00, NULL, NULL, 1500.00, 'Calaca, Batangas', 13.9319000, 120.8144000, 1, 0, NULL, 7, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(30, 10, 13, 16, 'Stand Up Paddleboard', 'Bestway', NULL, 'Inflatable SUP board with paddle and pump, includes carry backpack.', 'good', NULL, 12000.00, 500.00, NULL, NULL, 2000.00, 'Calatagan, Batangas', 13.8306000, 120.6325000, 1, 0, NULL, 3, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(31, 11, 17, 18, 'Mitsubishi Xpander 2023', 'Mitsubishi', NULL, '7-seater MPV, automatic transmission, ideal for family trips and group travel.', 'excellent', NULL, 1100000.00, 2800.00, NULL, NULL, 20000.00, 'Lian, Batangas', 14.0392000, 120.6417000, 1, 0, NULL, 10, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(32, 12, 17, 19, 'Yamaha Mio 125 Scooter', 'Yamaha', NULL, 'Reliable automatic scooter, good on fuel, comes with one helmet.', 'good', NULL, 75000.00, 700.00, NULL, NULL, 6000.00, 'Mabini, Batangas', 13.7472000, 120.9214000, 1, 0, NULL, 14, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(33, 13, 20, 21, 'BenQ Portable Projector', 'BenQ', NULL, 'Compact portable projector with built-in speaker, great for outdoor movie nights.', 'like_new', NULL, 20000.00, 450.00, NULL, NULL, 2000.00, 'Tuy, Batangas', 13.9928000, 120.7275000, 1, 0, NULL, 5, 1, 'published', NULL, 1, '2026-09-07 08:12:31', '2026-09-20 22:08:40', NULL),
(34, 2, 20, 22, 'DJI Air 2S Drone', 'DJI', NULL, '1-inch sensor drone with 5.4K video, extra battery and ND filters included.', 'excellent', NULL, 65000.00, 1200.00, NULL, NULL, 7000.00, 'Rosario, Batangas', 13.8461000, 121.2094000, 1, 0, NULL, 3, 1, 'published', NULL, 1, '2026-09-07 08:12:31', '2026-09-20 22:08:34', NULL),
(35, 4, 23, 24, 'Robot Vacuum Cleaner', 'Xiaomi', NULL, 'Smart robot vacuum with app control and auto-recharge dock.', 'good', NULL, 15000.00, 350.00, NULL, NULL, 1500.00, 'San Jose, Batangas', 13.8792000, 121.1058000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(36, 5, 23, 25, 'NutriBullet Blender Set', 'NutriBullet', NULL, 'Personal blender set with multiple cup sizes, great for smoothies and shakes.', 'excellent', NULL, 5000.00, 200.00, NULL, NULL, 300.00, 'Ibaan, Batangas', 13.8189000, 121.1339000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(37, 6, 1, 2, 'Bosch Circular Saw', 'Bosch', NULL, 'Corded circular saw for clean, straight cuts on wood and plywood.', 'good', NULL, 8000.00, 300.00, NULL, NULL, 700.00, 'Cuenca, Batangas', 13.9014000, 121.0525000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(38, 7, 5, 6, 'GoPro Hero 11', 'GoPro', NULL, 'Rugged action camera with mounts, perfect for beach trips and water sports.', 'like_new', NULL, 25000.00, 600.00, NULL, NULL, 2500.00, 'Malvar, Batangas', 14.0522000, 121.1583000, 1, 0, NULL, 5, 1, 'published', NULL, 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31', NULL),
(39, 2, 13, NULL, 'Folding Table Set', NULL, NULL, 'Well-maintained and ready to rent. Message me with any questions before booking.', 'good', NULL, NULL, 500.00, NULL, NULL, 1000.00, 'Quezon City, Metro Manila', 14.6760000, 121.0430000, 1, 0, NULL, 14, 1, 'published', NULL, 1, '2026-09-20 08:07:29', '2026-09-20 22:07:14', NULL),
(40, 2, 13, NULL, 'Bluetooth Speaker', NULL, NULL, 'Well-maintained and ready to rent. Message me with any questions before booking.', 'good', NULL, NULL, 800.00, NULL, NULL, 500.00, 'Quezon City, Metro Manila', 14.6760000, 121.0430000, 1, 0, NULL, 14, 1, 'published', NULL, 1, '2026-09-20 08:07:29', '2026-09-20 22:07:15', NULL),
(41, 2, 13, NULL, 'Mountain Bike', NULL, NULL, 'Well-maintained and ready to rent. Message me with any questions before booking.', 'good', NULL, NULL, 500.00, NULL, NULL, 500.00, 'Quezon City, Metro Manila', 14.6760000, 121.0430000, 1, 0, NULL, 14, 1, 'published', NULL, 1, '2026-09-20 08:07:29', '2026-09-20 22:07:16', NULL),
(42, 2, 13, NULL, 'Pressure Washer', NULL, NULL, 'Well-maintained and ready to rent. Message me with any questions before booking.', 'good', NULL, NULL, 350.00, NULL, NULL, 500.00, 'Quezon City, Metro Manila', 14.6760000, 121.0430000, 1, 0, NULL, 14, 1, 'published', NULL, 1, '2026-09-20 08:07:29', '2026-09-20 22:07:21', NULL),
(43, 2, 13, NULL, 'Party Tent (10x10)', NULL, NULL, 'Well-maintained and ready to rent. Message me with any questions before booking.', 'good', NULL, NULL, 800.00, NULL, NULL, 500.00, 'Quezon City, Metro Manila', 14.6760000, 121.0430000, 1, 0, NULL, 14, 1, 'published', NULL, 1, '2026-09-20 08:07:29', '2026-09-20 22:08:04', NULL),
(44, 2, 13, NULL, 'Karaoke Machine', NULL, NULL, 'Well-maintained and ready to rent. Message me with any questions before booking.', 'good', NULL, NULL, 350.00, NULL, NULL, 500.00, 'Quezon City, Metro Manila', 14.6760000, 121.0430000, 1, 0, NULL, 14, 1, 'published', NULL, 1, '2026-09-20 08:07:29', '2026-09-20 22:08:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `listing_images`
--

CREATE TABLE `listing_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `listing_id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `listing_images`
--

INSERT INTO `listing_images` (`id`, `listing_id`, `path`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'listings/bosch-pressure-washer-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(2, 1, 'listings/bosch-pressure-washer-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(3, 2, 'listings/canon-eos-m50-mirrorless-camera-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(4, 2, 'listings/canon-eos-m50-mirrorless-camera-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(5, 3, 'listings/4-person-camping-tent-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(6, 3, 'listings/4-person-camping-tent-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(7, 4, 'listings/jbl-partybox-speaker-set-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(8, 4, 'listings/jbl-partybox-speaker-set-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(9, 5, 'listings/honda-click-125i-scooter-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(10, 5, 'listings/honda-click-125i-scooter-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(11, 6, 'listings/epson-home-cinema-projector-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(12, 6, 'listings/epson-home-cinema-projector-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(13, 7, 'listings/kitchenaid-stand-mixer-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(14, 7, 'listings/kitchenaid-stand-mixer-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(15, 8, 'listings/trek-mountain-bike-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(16, 8, 'listings/trek-mountain-bike-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(17, 9, 'listings/dewalt-cordless-drill-set-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(18, 9, 'listings/dewalt-cordless-drill-set-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(19, 10, 'listings/aluminum-extension-ladder-12ft-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(20, 10, 'listings/aluminum-extension-ladder-12ft-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(21, 11, 'listings/sony-50mm-f18-lens-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(22, 11, 'listings/sony-50mm-f18-lens-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(23, 12, 'listings/godox-studio-light-kit-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(24, 12, 'listings/godox-studio-light-kit-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(25, 13, 'listings/10x10-event-canopy-tent-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(26, 13, 'listings/10x10-event-canopy-tent-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(27, 14, 'listings/led-balloon-arch-kit-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(28, 14, 'listings/led-balloon-arch-kit-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(29, 15, 'listings/inflatable-kayak-2-seater-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(30, 15, 'listings/inflatable-kayak-2-seater-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(31, 16, 'listings/toyota-vios-2022-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(32, 16, 'listings/toyota-vios-2022-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(33, 17, 'listings/dji-mini-3-pro-drone-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(34, 17, 'listings/dji-mini-3-pro-drone-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(35, 18, 'listings/karcher-steam-cleaner-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(36, 18, 'listings/karcher-steam-cleaner-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(37, 19, 'listings/makita-angle-grinder-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(38, 19, 'listings/makita-angle-grinder-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(39, 20, 'listings/karcher-k5-pressure-washer-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(40, 20, 'listings/karcher-k5-pressure-washer-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(41, 21, 'listings/step-ladder-6ft-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(42, 21, 'listings/step-ladder-6ft-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(43, 22, 'listings/sony-a7iii-mirrorless-camera-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(44, 22, 'listings/sony-a7iii-mirrorless-camera-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(45, 23, 'listings/canon-24-70mm-f28-lens-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(46, 23, 'listings/canon-24-70mm-f28-lens-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(47, 24, 'listings/neewer-ring-light-kit-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(48, 24, 'listings/neewer-ring-light-kit-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(49, 25, 'listings/white-wedding-tent-20x30-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(50, 25, 'listings/white-wedding-tent-20x30-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(51, 26, 'listings/bose-s1-pro-pa-system-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(52, 26, 'listings/bose-s1-pro-pa-system-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(53, 27, 'listings/backdrop-stand-with-fairy-lights-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(54, 27, 'listings/backdrop-stand-with-fairy-lights-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(55, 28, 'listings/8-person-family-tent-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(56, 28, 'listings/8-person-family-tent-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(57, 29, 'listings/foldable-city-bike-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(58, 29, 'listings/foldable-city-bike-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(59, 30, 'listings/stand-up-paddleboard-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(60, 30, 'listings/stand-up-paddleboard-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(61, 31, 'listings/mitsubishi-xpander-2023-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(62, 31, 'listings/mitsubishi-xpander-2023-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(63, 32, 'listings/yamaha-mio-125-scooter-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(64, 32, 'listings/yamaha-mio-125-scooter-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(65, 33, 'listings/benq-portable-projector-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(66, 33, 'listings/benq-portable-projector-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(67, 34, 'listings/dji-air-2s-drone-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(68, 34, 'listings/dji-air-2s-drone-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(69, 35, 'listings/robot-vacuum-cleaner-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(70, 35, 'listings/robot-vacuum-cleaner-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(71, 36, 'listings/nutribullet-blender-set-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(72, 36, 'listings/nutribullet-blender-set-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(73, 37, 'listings/bosch-circular-saw-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(74, 37, 'listings/bosch-circular-saw-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(75, 38, 'listings/gopro-hero-11-1.svg', 0, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(76, 38, 'listings/gopro-hero-11-2.svg', 1, '2026-09-07 08:12:31', '2026-09-07 08:12:31');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rental_request_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_id` bigint(20) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `rental_request_id`, `sender_id`, `receiver_id`, `body`, `read_at`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 2, 'Hi! Is this still available for the dates I requested?', '2026-09-15 03:42:16', '2026-09-15 03:40:16', '2026-09-15 03:40:16'),
(2, 1, 2, 3, 'Yes, it\'s available! I can have it ready for pickup.', '2026-09-15 04:22:16', '2026-09-15 04:20:16', '2026-09-15 04:20:16'),
(3, 1, 3, 2, 'Great, what time works best for you?', '2026-09-20 21:42:35', '2026-09-15 04:06:16', '2026-09-20 21:42:35'),
(4, 21, 2, 12, 'Hi! Is this still available for the dates I requested?', '2026-09-16 02:42:17', '2026-09-16 02:40:17', '2026-09-16 02:40:17'),
(5, 21, 12, 2, 'Yes, it\'s available! I can have it ready for pickup.', '2026-09-16 03:13:17', '2026-09-16 03:11:17', '2026-09-16 03:11:17'),
(6, 21, 2, 12, 'Great, what time works best for you?', '2026-09-16 03:30:17', '2026-09-16 03:28:17', '2026-09-16 03:28:17'),
(7, 21, 12, 2, 'Anytime after 2pm works on my end.', '2026-09-16 04:03:17', '2026-09-16 04:01:17', '2026-09-16 04:01:17'),
(8, 21, 2, 12, 'Perfect, see you then. Thank you!', '2026-09-16 03:34:17', '2026-09-16 03:32:17', '2026-09-16 03:32:17'),
(9, 21, 12, 2, 'Sounds good, looking forward to it.', '2026-09-20 07:50:15', '2026-09-16 03:35:17', '2026-09-20 07:50:15'),
(10, 7, 10, 9, 'Hi! Is this still available for the dates I requested?', '2026-09-17 01:42:17', '2026-09-17 01:40:17', '2026-09-17 01:40:17'),
(11, 7, 9, 10, 'Yes, it\'s available! I can have it ready for pickup.', '2026-09-17 02:15:17', '2026-09-17 02:13:17', '2026-09-17 02:13:17'),
(12, 7, 10, 9, 'Great, what time works best for you?', NULL, '2026-09-17 02:48:17', '2026-09-17 02:48:17'),
(13, 8, 11, 10, 'Hi! Is this still available for the dates I requested?', '2026-09-18 04:42:17', '2026-09-18 04:40:17', '2026-09-18 04:40:17'),
(14, 8, 10, 11, 'Yes, it\'s available! I can have it ready for pickup.', '2026-09-18 04:56:17', '2026-09-18 04:54:17', '2026-09-18 04:54:17'),
(15, 8, 11, 10, 'Great, what time works best for you?', NULL, '2026-09-18 05:58:17', '2026-09-18 05:58:17'),
(16, 16, 19, 7, 'Hi! Is this still available for the dates I requested?', '2026-09-19 04:42:17', '2026-09-19 04:40:17', '2026-09-19 04:40:17'),
(17, 16, 7, 19, 'Yes, it\'s available! I can have it ready for pickup.', '2026-09-19 04:53:17', '2026-09-19 04:51:17', '2026-09-19 04:51:17'),
(18, 16, 19, 7, 'Great, what time works best for you?', NULL, '2026-09-19 04:52:17', '2026-09-19 04:52:17');

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
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_09_06_150626_add_marketplace_profile_fields_to_users_table', 1),
(6, '2026_09_06_153658_create_categories_table', 1),
(7, '2026_09_06_153658_create_listings_table', 1),
(8, '2026_09_06_153659_create_listing_images_table', 1),
(9, '2026_09_06_165952_create_commission_settings_table', 1),
(10, '2026_09_06_165952_create_rental_requests_table', 1),
(11, '2026_09_06_171728_a_create_rentals_table', 1),
(12, '2026_09_06_171728_b_create_payments_table', 1),
(13, '2026_09_06_171728_c_create_security_deposits_table', 1),
(14, '2026_09_06_173634_add_lifecycle_fields_to_rentals_table', 1),
(15, '2026_09_06_174800_a_create_condition_records_table', 1),
(16, '2026_09_06_174800_b_create_condition_record_photos_table', 1),
(17, '2026_09_06_174801_a_create_damage_reports_table', 1),
(18, '2026_09_06_174801_b_create_damage_report_photos_table', 1),
(19, '2026_09_06_181038_create_disputes_table', 1),
(20, '2026_09_06_181038_create_reviews_table', 1),
(21, '2026_09_06_182933_create_notifications_table', 1),
(22, '2026_09_06_182945_create_messages_table', 1),
(24, '2026_09_07_121525_add_cancellation_fields_to_rentals_table', 1),
(25, '2026_09_07_121911_add_terms_acceptance_to_rental_requests_table', 1),
(27, '2026_09_20_052949_drop_trust_score_from_users_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('0f269a8d-ac2c-4c74-b7b6-6a6d45604922', 'App\\Notifications\\TalaNotification', 'App\\Models\\User', 1, '{\"type\":\"listing_pending\",\"title\":\"Listing pending approval\",\"message\":\"A new listing was submitted and is awaiting moderation.\",\"url\":\"http:\\/\\/127.0.0.1:8123\\/admin\\/listings\"}', NULL, '2026-09-20 07:40:17', '2026-09-20 07:40:17'),
('29973b5d-376c-4078-96a4-0c99580f80e6', 'App\\Notifications\\TalaNotification', 'App\\Models\\User', 2, '{\"type\":\"payment_received\",\"title\":\"Payment received\",\"message\":\"A renter completed payment for an approved rental.\",\"url\":\"http:\\/\\/127.0.0.1:8123\\/owner\\/rentals\"}', NULL, '2026-09-20 07:40:17', '2026-09-20 07:40:17'),
('3ccc884b-7e9c-46c9-a074-d0c7fb4d2ea8', 'App\\Notifications\\TalaNotification', 'App\\Models\\User', 2, '{\"type\":\"rental_request\",\"title\":\"New rental request\",\"message\":\"Demo Renter requested to rent one of your listings.\",\"url\":\"http:\\/\\/127.0.0.1:8123\\/owner\\/rental-requests\"}', NULL, '2026-09-20 07:40:17', '2026-09-20 07:40:17'),
('5bb4965f-d18d-4f17-bb45-26e6b8645708', 'App\\Notifications\\TalaNotification', 'App\\Models\\User', 1, '{\"type\":\"dispute_opened\",\"title\":\"New dispute opened\",\"message\":\"A renter opened a dispute that needs admin review.\",\"url\":\"http:\\/\\/127.0.0.1:8123\\/admin\\/disputes\"}', NULL, '2026-09-20 07:40:17', '2026-09-20 07:40:17'),
('a1d36629-5461-4f08-ad0f-5024fae07c75', 'App\\Notifications\\TalaNotification', 'App\\Models\\User', 2, '{\"type\":\"damage_report\",\"title\":\"Damage report needs your review\",\"message\":\"A renter responded to a damage report you filed.\",\"url\":\"http:\\/\\/127.0.0.1:8123\\/owner\\/rentals\"}', NULL, '2026-09-20 07:40:17', '2026-09-20 07:40:17'),
('bb4daaac-ea2b-4852-ac67-8df8538663b6', 'App\\Notifications\\TalaNotification', 'App\\Models\\User', 3, '{\"type\":\"request_approved\",\"title\":\"Request approved\",\"message\":\"Your rental request was approved. Complete payment to confirm.\",\"url\":\"http:\\/\\/127.0.0.1:8123\\/renter\\/rentals\"}', NULL, '2026-09-20 07:40:17', '2026-09-20 07:40:17'),
('c1c99013-64c2-42b3-ba7d-7f7d6b4134d7', 'App\\Notifications\\TalaNotification', 'App\\Models\\User', 3, '{\"type\":\"rental_reminder\",\"title\":\"Rental starting soon\",\"message\":\"One of your rentals starts within the next 2 days.\",\"url\":\"http:\\/\\/127.0.0.1:8123\\/renter\\/rentals\"}', NULL, '2026-09-20 07:40:17', '2026-09-20 07:40:17');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rental_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_reference` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'paid',
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `rental_id`, `transaction_reference`, `amount`, `status`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 4, 'LENDLY-CPQGSMGQV1', 1955.00, 'paid', '2026-09-09 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(2, 5, 'LENDLY-XV6OWNHSYQ', 740.00, 'paid', '2026-09-12 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(3, 6, 'LENDLY-F2T3RYFMNP', 5200.00, 'paid', '2026-09-08 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(4, 7, 'LENDLY-KROR2GR9VD', 3650.00, 'paid', '2026-09-04 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(5, 8, 'LENDLY-ZFBZSB9TJC', 3640.00, 'paid', '2026-09-05 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(6, 9, 'LENDLY-UFNZLS6TI1', 3050.00, 'paid', '2026-09-03 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(7, 10, 'LENDLY-N7UNZYKGVP', 2985.00, 'paid', '2026-08-31 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(8, 11, 'LENDLY-QXTXTORAOR', 26000.00, 'paid', '2026-08-27 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(9, 12, 'LENDLY-ULHW2SWW52', 7970.00, 'paid', '2026-08-29 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(10, 13, 'LENDLY-JOIHJASYUC', 1820.00, 'paid', '2026-08-25 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(11, 14, 'LENDLY-D9WBD9GDHT', 1325.00, 'paid', '2026-08-12 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(12, 15, 'LENDLY-5FADYJZDMW', 2850.00, 'paid', '2026-08-12 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(13, 16, 'LENDLY-TIW2TZCWWC', 695.00, 'paid', '2026-08-12 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(14, 17, 'LENDLY-QW3J2M1TTG', 14950.00, 'paid', '2026-08-12 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(15, 18, 'LENDLY-ATXBXJDPI1', 10970.00, 'paid', '2026-08-12 08:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(16, 20, 'LENDLY-PKSVURAU5W', 9950.00, 'paid', '2026-09-07 04:12:31', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(17, 21, 'LENDLY-29GBVJUHHR', 10970.00, 'paid', '2026-08-25 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(18, 22, 'LENDLY-ARTUV5DXWA', 10960.00, 'paid', '2026-09-05 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(19, 23, 'LENDLY-UO2UVTYCF9', 2650.00, 'paid', '2026-09-09 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(20, 24, 'LENDLY-SSCODQ6BFJ', 3140.00, 'paid', '2026-09-07 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(21, 25, 'LENDLY-SNSKXAGIDC', 2150.00, 'paid', '2026-08-25 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(22, 26, 'LENDLY-O1J3COWNUE', 1655.00, 'paid', '2026-09-13 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(23, 27, 'LENDLY-DIUYUWUDL5', 3140.00, 'paid', '2026-09-14 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(24, 28, 'LENDLY-A4WRKH7MSV', 1655.00, 'paid', '2026-09-07 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(25, 29, 'LENDLY-WQXSRYAT18', 960.00, 'paid', '2026-09-07 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(26, 30, 'LENDLY-3YGUMT0X43', 3140.00, 'paid', '2026-09-06 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(27, 31, 'LENDLY-WKOIPINASL', 2320.00, 'paid', '2026-08-25 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(28, 32, 'LENDLY-HZQCJYCE65', 1490.00, 'paid', '2026-09-17 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(29, 33, 'LENDLY-OW6GOBSRSO', 10960.00, 'paid', '2026-09-12 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(30, 34, 'LENDLY-7AB0SZEHTS', 3485.00, 'paid', '2026-09-06 08:07:29', '2026-09-20 08:07:29', '2026-09-20 08:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

CREATE TABLE `rentals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rental_request_id` bigint(20) UNSIGNED NOT NULL,
  `listing_id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED NOT NULL,
  `renter_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `rental_days` smallint(5) UNSIGNED NOT NULL,
  `fulfillment_method` varchar(255) NOT NULL,
  `rental_fee` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'payment_pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `pickup_confirmed_by_owner_at` timestamp NULL DEFAULT NULL,
  `pickup_confirmed_by_renter_at` timestamp NULL DEFAULT NULL,
  `return_confirmed_by_owner_at` timestamp NULL DEFAULT NULL,
  `return_confirmed_by_renter_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `cancellation_fee` decimal(10,2) DEFAULT NULL,
  `days_overdue` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `late_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rentals`
--

INSERT INTO `rentals` (`id`, `rental_request_id`, `listing_id`, `owner_id`, `renter_id`, `start_date`, `end_date`, `rental_days`, `fulfillment_method`, `rental_fee`, `commission_rate`, `commission_amount`, `security_deposit`, `total_amount`, `status`, `paid_at`, `pickup_confirmed_by_owner_at`, `pickup_confirmed_by_renter_at`, `return_confirmed_by_owner_at`, `return_confirmed_by_renter_at`, `completed_at`, `cancelled_at`, `cancellation_reason`, `cancellation_fee`, `days_overdue`, `late_fee`, `created_at`, `updated_at`) VALUES
(1, 6, 6, 8, 9, '2026-09-12', '2026-09-14', 3, 'pickup', 1800.00, 10.00, 180.00, 3000.00, 4980.00, 'payment_pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(2, 7, 7, 9, 10, '2026-09-14', '2026-09-15', 2, 'pickup', 700.00, 10.00, 70.00, 1500.00, 2270.00, 'payment_pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(3, 8, 8, 10, 11, '2026-09-11', '2026-09-14', 4, 'pickup', 2000.00, 10.00, 200.00, 3000.00, 5200.00, 'payment_pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(4, 9, 9, 11, 12, '2026-09-10', '2026-09-12', 3, 'pickup', 1050.00, 10.00, 105.00, 800.00, 1955.00, 'paid', '2026-09-09 08:12:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(5, 10, 10, 12, 13, '2026-09-13', '2026-09-14', 2, 'pickup', 400.00, 10.00, 40.00, 300.00, 740.00, 'paid', '2026-09-12 08:12:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(6, 11, 11, 13, 14, '2026-09-09', '2026-09-13', 5, 'pickup', 2000.00, 10.00, 200.00, 3000.00, 5200.00, 'paid', '2026-09-08 08:12:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(7, 12, 12, 2, 15, '2026-09-05', '2026-09-08', 3, 'pickup', 1500.00, 10.00, 150.00, 2000.00, 3650.00, 'active', '2026-09-04 08:12:31', '2026-09-05 06:12:31', '2026-09-05 07:12:31', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(8, 13, 13, 4, 16, '2026-09-06', '2026-09-11', 6, 'pickup', 2400.00, 10.00, 240.00, 1000.00, 3640.00, 'active', '2026-09-05 08:12:31', '2026-09-06 06:12:31', '2026-09-06 07:12:31', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(9, 14, 14, 5, 17, '2026-09-04', '2026-09-13', 10, 'pickup', 2500.00, 10.00, 250.00, 300.00, 3050.00, 'active', '2026-09-03 08:12:31', '2026-09-04 06:12:31', '2026-09-04 07:12:31', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(10, 15, 15, 6, 18, '2026-09-01', '2026-09-03', 3, 'pickup', 1350.00, 10.00, 135.00, 1500.00, 2985.00, 'overdue', '2026-08-31 08:12:31', '2026-09-01 06:12:31', '2026-09-01 07:12:31', NULL, NULL, NULL, NULL, NULL, NULL, 2, 900.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(11, 16, 16, 7, 19, '2026-08-28', '2026-09-01', 5, 'pickup', 10000.00, 10.00, 1000.00, 15000.00, 26000.00, 'overdue', '2026-08-27 08:12:31', '2026-08-28 06:12:31', '2026-08-28 07:12:31', NULL, NULL, NULL, NULL, NULL, NULL, 4, 8000.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(12, 17, 17, 8, 20, '2026-08-30', '2026-09-01', 3, 'pickup', 2700.00, 10.00, 270.00, 5000.00, 7970.00, 'returned', '2026-08-29 08:12:31', '2026-08-30 06:12:31', '2026-08-30 07:12:31', '2026-09-07 05:12:31', '2026-09-07 04:12:31', NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(13, 18, 18, 9, 21, '2026-08-26', '2026-08-29', 4, 'pickup', 1200.00, 10.00, 120.00, 500.00, 1820.00, 'returned', '2026-08-25 08:12:31', '2026-08-26 06:12:31', '2026-08-26 07:12:31', '2026-09-07 05:12:31', '2026-09-07 04:12:31', NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(14, 19, 19, 10, 22, '2026-08-13', '2026-08-15', 3, 'pickup', 750.00, 10.00, 75.00, 500.00, 1325.00, 'completed', '2026-08-12 08:12:31', '2026-08-13 06:12:31', '2026-08-13 07:12:31', '2026-09-07 05:12:31', '2026-09-07 04:12:31', '2026-08-17 08:12:31', NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(15, 20, 20, 11, 23, '2026-08-13', '2026-08-15', 3, 'pickup', 1500.00, 10.00, 150.00, 1200.00, 2850.00, 'completed', '2026-08-12 08:12:31', '2026-08-13 06:12:31', '2026-08-13 07:12:31', '2026-09-07 05:12:31', '2026-09-07 04:12:31', '2026-08-17 08:12:31', NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(16, 21, 21, 12, 2, '2026-08-13', '2026-08-15', 3, 'pickup', 450.00, 10.00, 45.00, 200.00, 695.00, 'completed', '2026-08-12 08:12:31', '2026-08-13 06:12:31', '2026-08-13 07:12:31', '2026-09-07 05:12:31', '2026-09-07 04:12:31', '2026-08-17 08:12:31', NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(17, 22, 22, 13, 3, '2026-08-13', '2026-08-15', 3, 'pickup', 4500.00, 10.00, 450.00, 10000.00, 14950.00, 'completed', '2026-08-12 08:12:31', '2026-08-13 06:12:31', '2026-08-13 07:12:31', '2026-09-07 05:12:31', '2026-09-07 04:12:31', '2026-08-17 08:12:31', NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(18, 23, 23, 2, 4, '2026-08-13', '2026-08-15', 3, 'pickup', 2700.00, 10.00, 270.00, 8000.00, 10970.00, 'completed', '2026-08-12 08:12:31', '2026-08-13 06:12:31', '2026-08-13 07:12:31', '2026-09-07 05:12:31', '2026-09-07 04:12:31', '2026-08-17 08:12:31', NULL, NULL, NULL, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(19, 24, 24, 4, 5, '2026-09-17', '2026-09-19', 3, 'pickup', 750.00, 10.00, 75.00, 500.00, 1325.00, 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-07 07:12:31', 'Found a cheaper option nearby.', 0.00, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(20, 25, 25, 5, 6, '2026-09-08', '2026-09-10', 3, 'pickup', 4500.00, 10.00, 450.00, 5000.00, 9950.00, 'cancelled', '2026-09-07 04:12:31', NULL, NULL, NULL, NULL, NULL, '2026-09-07 07:12:31', 'Change of plans, no longer needed.', 900.00, 0, 0.00, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(21, 33, 23, 2, 19, '2026-08-26', '2026-08-28', 3, 'pickup', 2700.00, 10.00, 270.00, 8000.00, 10970.00, 'completed', '2026-08-25 08:07:29', '2026-08-26 06:07:29', '2026-08-26 07:07:29', '2026-09-20 05:07:29', '2026-09-20 04:07:29', '2026-08-30 08:07:29', NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(22, 34, 34, 2, 3, '2026-09-06', '2026-09-08', 3, 'pickup', 3600.00, 10.00, 360.00, 7000.00, 10960.00, 'returned', '2026-09-05 08:07:29', '2026-09-06 06:07:29', '2026-09-06 07:07:29', '2026-09-20 05:07:29', '2026-09-20 04:07:29', NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(23, 35, 39, 2, 18, '2026-09-10', '2026-09-12', 3, 'pickup', 1500.00, 10.00, 150.00, 1000.00, 2650.00, 'active', '2026-09-09 08:07:29', '2026-09-10 06:07:29', '2026-09-10 07:07:29', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(24, 36, 40, 2, 15, '2026-09-08', '2026-09-10', 3, 'pickup', 2400.00, 10.00, 240.00, 500.00, 3140.00, 'paid', '2026-09-07 08:07:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(25, 37, 41, 2, 3, '2026-08-26', '2026-08-28', 3, 'pickup', 1500.00, 10.00, 150.00, 500.00, 2150.00, 'completed', '2026-08-25 08:07:29', '2026-08-26 06:07:29', '2026-08-26 07:07:29', '2026-09-20 05:07:29', '2026-09-20 04:07:29', '2026-08-30 08:07:29', NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(26, 38, 42, 2, 5, '2026-09-14', '2026-09-16', 3, 'pickup', 1050.00, 10.00, 105.00, 500.00, 1655.00, 'returned', '2026-09-13 08:07:29', '2026-09-14 06:07:29', '2026-09-14 07:07:29', '2026-09-20 05:07:29', '2026-09-20 04:07:29', NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(27, 39, 43, 2, 19, '2026-09-15', '2026-09-17', 3, 'pickup', 2400.00, 10.00, 240.00, 500.00, 3140.00, 'active', '2026-09-14 08:07:29', '2026-09-15 06:07:29', '2026-09-15 07:07:29', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(28, 40, 44, 2, 3, '2026-09-08', '2026-09-10', 3, 'pickup', 1050.00, 10.00, 105.00, 500.00, 1655.00, 'paid', '2026-09-07 08:07:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(29, 43, 10, 12, 3, '2026-09-08', '2026-09-10', 3, 'pickup', 600.00, 10.00, 60.00, 300.00, 960.00, 'active', '2026-09-07 08:07:29', '2026-09-08 06:07:29', '2026-09-08 07:07:29', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(30, 44, 40, 2, 3, '2026-09-07', '2026-09-09', 3, 'pickup', 2400.00, 10.00, 240.00, 500.00, 3140.00, 'paid', '2026-09-06 08:07:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(31, 45, 13, 4, 3, '2026-08-26', '2026-08-28', 3, 'pickup', 1200.00, 10.00, 120.00, 1000.00, 2320.00, 'completed', '2026-08-25 08:07:29', '2026-08-26 06:07:29', '2026-08-26 07:07:29', '2026-09-20 05:07:29', '2026-09-20 04:07:29', '2026-08-30 08:07:29', NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(32, 46, 18, 9, 3, '2026-09-18', '2026-09-20', 3, 'pickup', 900.00, 10.00, 90.00, 500.00, 1490.00, 'returned', '2026-09-17 08:07:29', '2026-09-18 06:07:29', '2026-09-18 07:07:29', '2026-09-20 05:07:29', '2026-09-20 04:07:29', NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(33, 47, 34, 2, 3, '2026-09-13', '2026-09-15', 3, 'pickup', 3600.00, 10.00, 360.00, 7000.00, 10960.00, 'active', '2026-09-12 08:07:29', '2026-09-13 06:07:29', '2026-09-13 07:07:29', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(34, 48, 33, 13, 3, '2026-09-07', '2026-09-09', 3, 'pickup', 1350.00, 10.00, 135.00, 2000.00, 3485.00, 'paid', '2026-09-06 08:07:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, '2026-09-20 08:07:29', '2026-09-20 08:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `rental_requests`
--

CREATE TABLE `rental_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `listing_id` bigint(20) UNSIGNED NOT NULL,
  `renter_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `rental_days` smallint(5) UNSIGNED NOT NULL,
  `fulfillment_method` varchar(255) NOT NULL,
  `rental_fee` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `security_deposit` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'requested',
  `renter_terms_accepted_at` timestamp NULL DEFAULT NULL,
  `owner_terms_accepted_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rental_requests`
--

INSERT INTO `rental_requests` (`id`, `listing_id`, `renter_id`, `start_date`, `end_date`, `rental_days`, `fulfillment_method`, `rental_fee`, `commission_rate`, `commission_amount`, `security_deposit`, `total_amount`, `status`, `renter_terms_accepted_at`, `owner_terms_accepted_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 3, '2026-09-17', '2026-09-19', 3, 'pickup', 1350.00, 10.00, 135.00, 1000.00, 2485.00, 'requested', '2026-09-07 06:12:31', NULL, NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(2, 2, 5, '2026-09-17', '2026-09-19', 3, 'pickup', 2400.00, 10.00, 240.00, 5000.00, 7640.00, 'requested', '2026-09-07 06:12:31', NULL, NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(3, 3, 6, '2026-09-17', '2026-09-19', 3, 'pickup', 900.00, 10.00, 90.00, 500.00, 1490.00, 'requested', '2026-09-07 06:12:31', NULL, NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(4, 4, 7, '2026-09-15', '2026-09-16', 2, 'pickup', 1400.00, 10.00, 140.00, 2000.00, 3540.00, 'rejected', '2026-09-06 08:12:31', NULL, 'Item is already booked for those dates.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(5, 5, 8, '2026-09-15', '2026-09-16', 2, 'pickup', 1800.00, 10.00, 180.00, 8000.00, 9980.00, 'rejected', '2026-09-06 08:12:31', NULL, 'Not comfortable renting to a new account yet.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(6, 6, 9, '2026-09-12', '2026-09-14', 3, 'pickup', 1800.00, 10.00, 180.00, 3000.00, 4980.00, 'approved', '2026-09-10 08:12:31', '2026-09-11 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(7, 7, 10, '2026-09-14', '2026-09-15', 2, 'pickup', 700.00, 10.00, 70.00, 1500.00, 2270.00, 'approved', '2026-09-12 08:12:31', '2026-09-13 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(8, 8, 11, '2026-09-11', '2026-09-14', 4, 'pickup', 2000.00, 10.00, 200.00, 3000.00, 5200.00, 'approved', '2026-09-09 08:12:31', '2026-09-10 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(9, 9, 12, '2026-09-10', '2026-09-12', 3, 'pickup', 1050.00, 10.00, 105.00, 800.00, 1955.00, 'approved', '2026-09-08 08:12:31', '2026-09-09 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(10, 10, 13, '2026-09-13', '2026-09-14', 2, 'pickup', 400.00, 10.00, 40.00, 300.00, 740.00, 'approved', '2026-09-11 08:12:31', '2026-09-12 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(11, 11, 14, '2026-09-09', '2026-09-13', 5, 'pickup', 2000.00, 10.00, 200.00, 3000.00, 5200.00, 'approved', '2026-09-07 08:12:31', '2026-09-08 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(12, 12, 15, '2026-09-05', '2026-09-08', 3, 'pickup', 1500.00, 10.00, 150.00, 2000.00, 3650.00, 'approved', '2026-09-03 08:12:31', '2026-09-04 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(13, 13, 16, '2026-09-06', '2026-09-11', 6, 'pickup', 2400.00, 10.00, 240.00, 1000.00, 3640.00, 'approved', '2026-09-04 08:12:31', '2026-09-05 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(14, 14, 17, '2026-09-04', '2026-09-13', 10, 'pickup', 2500.00, 10.00, 250.00, 300.00, 3050.00, 'approved', '2026-09-02 08:12:31', '2026-09-03 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(15, 15, 18, '2026-09-01', '2026-09-03', 3, 'pickup', 1350.00, 10.00, 135.00, 1500.00, 2985.00, 'approved', '2026-08-30 08:12:31', '2026-08-31 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(16, 16, 19, '2026-08-28', '2026-09-01', 5, 'pickup', 10000.00, 10.00, 1000.00, 15000.00, 26000.00, 'approved', '2026-08-26 08:12:31', '2026-08-27 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(17, 17, 20, '2026-08-30', '2026-09-01', 3, 'pickup', 2700.00, 10.00, 270.00, 5000.00, 7970.00, 'approved', '2026-08-28 08:12:31', '2026-08-29 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(18, 18, 21, '2026-08-26', '2026-08-29', 4, 'pickup', 1200.00, 10.00, 120.00, 500.00, 1820.00, 'approved', '2026-08-24 08:12:31', '2026-08-25 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(19, 19, 22, '2026-08-13', '2026-08-15', 3, 'pickup', 750.00, 10.00, 75.00, 500.00, 1325.00, 'approved', '2026-08-11 08:12:31', '2026-08-12 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(20, 20, 23, '2026-08-13', '2026-08-15', 3, 'pickup', 1500.00, 10.00, 150.00, 1200.00, 2850.00, 'approved', '2026-08-11 08:12:31', '2026-08-12 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(21, 21, 2, '2026-08-13', '2026-08-15', 3, 'pickup', 450.00, 10.00, 45.00, 200.00, 695.00, 'approved', '2026-08-11 08:12:31', '2026-08-12 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(22, 22, 3, '2026-08-13', '2026-08-15', 3, 'pickup', 4500.00, 10.00, 450.00, 10000.00, 14950.00, 'approved', '2026-08-11 08:12:31', '2026-08-12 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(23, 23, 4, '2026-08-13', '2026-08-15', 3, 'pickup', 2700.00, 10.00, 270.00, 8000.00, 10970.00, 'approved', '2026-08-11 08:12:31', '2026-08-12 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(24, 24, 5, '2026-09-17', '2026-09-19', 3, 'pickup', 750.00, 10.00, 75.00, 500.00, 1325.00, 'cancelled', '2026-09-15 08:12:31', '2026-09-16 08:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(25, 25, 6, '2026-09-08', '2026-09-10', 3, 'pickup', 4500.00, 10.00, 450.00, 5000.00, 9950.00, 'cancelled', '2026-09-06 04:12:31', '2026-09-07 04:12:31', NULL, '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(26, 34, 3, '2026-09-30', '2026-10-02', 3, 'pickup', 3600.00, 10.00, 360.00, 7000.00, 10960.00, 'requested', '2026-09-20 06:07:29', NULL, NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(27, 39, 18, '2026-09-30', '2026-10-02', 3, 'pickup', 1500.00, 10.00, 150.00, 1000.00, 2650.00, 'requested', '2026-09-20 06:07:29', NULL, NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(28, 40, 15, '2026-09-30', '2026-10-02', 3, 'pickup', 2400.00, 10.00, 240.00, 500.00, 3140.00, 'requested', '2026-09-20 06:07:29', NULL, NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(29, 41, 3, '2026-09-30', '2026-10-02', 3, 'pickup', 1500.00, 10.00, 150.00, 500.00, 2150.00, 'requested', '2026-09-20 06:07:29', NULL, NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(30, 42, 5, '2026-09-30', '2026-10-02', 3, 'pickup', 1050.00, 10.00, 105.00, 500.00, 1655.00, 'requested', '2026-09-20 06:07:29', NULL, NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(31, 43, 19, '2026-09-30', '2026-10-02', 3, 'pickup', 2400.00, 10.00, 240.00, 500.00, 3140.00, 'requested', '2026-09-20 06:07:29', NULL, NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(32, 44, 3, '2026-09-30', '2026-10-02', 3, 'pickup', 1050.00, 10.00, 105.00, 500.00, 1655.00, 'requested', '2026-09-20 06:07:29', NULL, NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(33, 23, 19, '2026-08-26', '2026-08-28', 3, 'pickup', 2700.00, 10.00, 270.00, 8000.00, 10970.00, 'approved', '2026-08-24 08:07:29', '2026-08-25 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(34, 34, 3, '2026-09-06', '2026-09-08', 3, 'pickup', 3600.00, 10.00, 360.00, 7000.00, 10960.00, 'approved', '2026-09-04 08:07:29', '2026-09-05 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(35, 39, 18, '2026-09-10', '2026-09-12', 3, 'pickup', 1500.00, 10.00, 150.00, 1000.00, 2650.00, 'approved', '2026-09-08 08:07:29', '2026-09-09 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(36, 40, 15, '2026-09-08', '2026-09-10', 3, 'pickup', 2400.00, 10.00, 240.00, 500.00, 3140.00, 'approved', '2026-09-06 08:07:29', '2026-09-07 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(37, 41, 3, '2026-08-26', '2026-08-28', 3, 'pickup', 1500.00, 10.00, 150.00, 500.00, 2150.00, 'approved', '2026-08-24 08:07:29', '2026-08-25 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(38, 42, 5, '2026-09-14', '2026-09-16', 3, 'pickup', 1050.00, 10.00, 105.00, 500.00, 1655.00, 'approved', '2026-09-12 08:07:29', '2026-09-13 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(39, 43, 19, '2026-09-15', '2026-09-17', 3, 'pickup', 2400.00, 10.00, 240.00, 500.00, 3140.00, 'approved', '2026-09-13 08:07:29', '2026-09-14 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(40, 44, 3, '2026-09-08', '2026-09-10', 3, 'pickup', 1050.00, 10.00, 105.00, 500.00, 1655.00, 'approved', '2026-09-06 08:07:29', '2026-09-07 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(41, 34, 3, '2026-09-30', '2026-10-02', 3, 'pickup', 3600.00, 10.00, 360.00, 7000.00, 10960.00, 'requested', '2026-09-20 06:07:29', NULL, NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(42, 33, 3, '2026-09-30', '2026-10-02', 3, 'pickup', 1350.00, 10.00, 135.00, 2000.00, 3485.00, 'requested', '2026-09-20 06:07:29', NULL, NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(43, 10, 3, '2026-09-08', '2026-09-10', 3, 'pickup', 600.00, 10.00, 60.00, 300.00, 960.00, 'approved', '2026-09-06 08:07:29', '2026-09-07 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(44, 40, 3, '2026-09-07', '2026-09-09', 3, 'pickup', 2400.00, 10.00, 240.00, 500.00, 3140.00, 'approved', '2026-09-05 08:07:29', '2026-09-06 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(45, 13, 3, '2026-08-26', '2026-08-28', 3, 'pickup', 1200.00, 10.00, 120.00, 1000.00, 2320.00, 'approved', '2026-08-24 08:07:29', '2026-08-25 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(46, 18, 3, '2026-09-18', '2026-09-20', 3, 'pickup', 900.00, 10.00, 90.00, 500.00, 1490.00, 'approved', '2026-09-16 08:07:29', '2026-09-17 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(47, 34, 3, '2026-09-13', '2026-09-15', 3, 'pickup', 3600.00, 10.00, 360.00, 7000.00, 10960.00, 'approved', '2026-09-11 08:07:29', '2026-09-12 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(48, 33, 3, '2026-09-07', '2026-09-09', 3, 'pickup', 1350.00, 10.00, 135.00, 2000.00, 3485.00, 'approved', '2026-09-05 08:07:29', '2026-09-06 08:07:29', NULL, '2026-09-20 08:07:29', '2026-09-20 08:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rental_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `rental_id`, `type`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 14, 'renter_to_owner', 4, 'Very accommodating with pickup times.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(2, 14, 'renter_to_listing', 5, 'Clean and well-maintained, no issues at all.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(3, 14, 'owner_to_renter', 4, 'Returned the item on time and in great shape.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(4, 15, 'renter_to_owner', 4, 'Great communication, item was exactly as described.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(5, 15, 'renter_to_listing', 5, 'Exactly what I needed for the weekend.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(6, 15, 'owner_to_renter', 5, 'Would rent to again anytime.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(7, 16, 'renter_to_owner', 5, 'Very accommodating with pickup times.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(8, 16, 'renter_to_listing', 4, 'Exactly what I needed for the weekend.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(9, 16, 'owner_to_renter', 4, 'Returned the item on time and in great shape.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(10, 17, 'renter_to_owner', 5, 'Great communication, item was exactly as described.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(11, 17, 'renter_to_listing', 5, 'Clean and well-maintained, no issues at all.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(12, 17, 'owner_to_renter', 5, 'Easy to coordinate with, no problems.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(13, 18, 'renter_to_owner', 5, 'Smooth transaction, would rent from again.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(14, 18, 'renter_to_listing', 4, 'Clean and well-maintained, no issues at all.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(15, 18, 'owner_to_renter', 5, 'Easy to coordinate with, no problems.', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(16, 21, 'renter_to_owner', 5, 'Very accommodating with pickup times.', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(17, 21, 'renter_to_listing', 5, 'Item was in excellent condition, worked perfectly.', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(18, 21, 'owner_to_renter', 4, 'Returned the item on time and in great shape.', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(19, 25, 'renter_to_owner', 5, 'Great communication, item was exactly as described.', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(20, 25, 'renter_to_listing', 5, 'Exactly what I needed for the weekend.', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(21, 25, 'owner_to_renter', 5, 'Easy to coordinate with, no problems.', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(22, 31, 'renter_to_owner', 5, 'Smooth transaction, would rent from again.', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(23, 31, 'renter_to_listing', 4, 'Item was in excellent condition, worked perfectly.', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(24, 31, 'owner_to_renter', 5, 'Returned the item on time and in great shape.', '2026-09-20 08:07:29', '2026-09-20 08:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `security_deposits`
--

CREATE TABLE `security_deposits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rental_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `deducted_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'held',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `security_deposits`
--

INSERT INTO `security_deposits` (`id`, `rental_id`, `amount`, `deducted_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 800.00, 0.00, 'held', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(2, 5, 300.00, 0.00, 'held', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(3, 6, 3000.00, 0.00, 'held', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(4, 7, 2000.00, 0.00, 'held', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(5, 8, 1000.00, 0.00, 'held', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(6, 9, 300.00, 0.00, 'held', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(7, 10, 1500.00, 0.00, 'held', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(8, 11, 15000.00, 0.00, 'held', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(9, 12, 5000.00, 0.00, 'held', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(10, 13, 500.00, 0.00, 'held', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(11, 14, 500.00, 0.00, 'released', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(12, 15, 1200.00, 0.00, 'released', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(13, 16, 200.00, 0.00, 'released', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(14, 17, 10000.00, 0.00, 'released', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(15, 18, 8000.00, 0.00, 'released', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(16, 20, 5000.00, 0.00, 'refunded', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(17, 21, 8000.00, 0.00, 'released', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(18, 22, 7000.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(19, 23, 1000.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(20, 24, 500.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(21, 25, 500.00, 0.00, 'released', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(22, 26, 500.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(23, 27, 500.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(24, 28, 500.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(25, 29, 300.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(26, 30, 500.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(27, 31, 1000.00, 0.00, 'released', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(28, 32, 500.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(29, 33, 7000.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29'),
(30, 34, 2000.00, 0.00, 'held', '2026-09-20 08:07:29', '2026-09-20 08:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('06oNJnjDO4UFcefDin52ES4EAYV1BxWwSUAoUuuJ', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoid2ZhYlRiWDdBQkFVQms2blFFMld4WDd4MDFTWUFnMjRURkJkaTZ3SyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjk6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9wcm9maWxlIjtzOjU6InJvdXRlIjtzOjc6InByb2ZpbGUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1789970651),
('3C4UzTB9gdzEq4ovWQDVJfDbXzqzJ1GOQ7k8bjSO', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZzFNbENyeURpbnpRTzRlT1RUNzhFcXJteDI4UzV6TFdNRHpUbUdrMCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDU6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9yZW50YWwtcmVxdWVzdHMvMjEvY2hhdCI7czo1OiJyb3V0ZSI7czoyMDoicmVudGFsLXJlcXVlc3RzLmNoYXQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1789968646),
('5b5xxlkipZQqFimzaqxG9TSACBw0Pk8vxJ0zgP4R', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQmVaOTdVcnV5RjZITjRQYTgxUGlFSGkyMzFIdHREREdUSEY4blVLSyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MDp7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==', 1789968523),
('8mzXoVy5TbfTldWWXweaoFjPdzLDpsg9D5yLj0me', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOU5UakpjOG96YXBnU3B3Q2tNRkpjbXJVS09tRldqalV1YjZMUmRjNCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9hZG1pbi9kaXNwdXRlcyI7czo1OiJyb3V0ZSI7czoyMDoiYWRtaW4uZGlzcHV0ZXMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1789970979),
('8nYspmF3U7XjNE8MPUg6a0MnaSFrpEycQZov0RQE', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYUxScXAzN2hWUXgxR1hLeU5YUXFwYkZndHdaeXhoazU3QWtFMU5aciI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9vd25lci9yZW50YWxzIjtzOjU6InJvdXRlIjtzOjE5OiJvd25lci5yZW50YWxzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9', 1789970810),
('8uIQT7XRnVce3OD5rqxqWwO3k3VtAu99hbydnjCa', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTzFkVU9QU3oxV3JQM1RzeVBPeTRCUGhqWHRDUlVKcGp4RXJQSG4ydiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDU6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9yZW50YWwtcmVxdWVzdHMvMjEvY2hhdCI7czo1OiJyb3V0ZSI7czoyMDoicmVudGFsLXJlcXVlc3RzLmNoYXQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1789968894),
('8xsUYtGhWn45WrcrBVMg7DU0PKLSsfKZNh8bUFYt', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMjBDUFd1U2NhOUVxelBGS0I2MnVFNTJNVXRvMzh3YlBRcWpzcXVHTSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9', 1789968714),
('bWxwT5B2tUfNz3WlUfvlkQTOqQXBINurWckeserw', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTm16S3RGclhsTkE5elBkeUdkdzQ5OTNqZlZDejRRVTBLUFhrVUd1QiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMyI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1789970747),
('CKpTCTJIDfkgRj2tKmedRcF73JcebzaONIeSQh1r', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiblRQOWw3dWV0MjhRTk1VRjVYQkg0VWtVQ0VHTHplRE1HVW5iS2pGeCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9yZWdpc3RlciI7czo1OiJyb3V0ZSI7czo4OiJyZWdpc3RlciI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1789970597),
('d6dxT2Q0gKVVE1gUbYD2A6YrDgoESqESBz76AVro', NULL, '127.0.0.1', 'curl/8.12.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSGlzN01HUGxIelhnbHF0aUd0ZHBlajJLSDFtTmlvVWYyY0tKVXJUYSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMyI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789968947),
('dxIVa5wFyJ9Oc2cuynM7XtkrFW53vUIHWRDB82N4', NULL, '127.0.0.1', 'curl/8.12.1', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWlZxVnlCTEdCUkFXdzF1ZW1JM2hJT0RTMkI3Sk5RTXVrc21xTmQyZSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo0MjoiaHR0cDovLzEyNy4wLjAuMTo4MTIzL293bmVyL3JlbnRhbHMvOTk5OTk5Ijt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDI6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9vd25lci9yZW50YWxzLzk5OTk5OSI7czo1OiJyb3V0ZSI7czoxODoib3duZXIucmVudGFscy5zaG93Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789971120),
('eNVc1bFTEZMeFD1sUFWTwUEVmEmCXq0BbP0Cx17v', NULL, '127.0.0.1', 'curl/8.12.1', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibGVKM1B5b0RYWThVbGhyWmx5elhmSTkyQXZpb1JJdFVrZ0lZTE9YRCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo0MjoiaHR0cDovLzEyNy4wLjAuMTo4MTIzL293bmVyL3JlbnRhbHMvOTk5OTk5Ijt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDI6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9vd25lci9yZW50YWxzLzk5OTk5OSI7czo1OiJyb3V0ZSI7czoxODoib3duZXIucmVudGFscy5zaG93Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789971107),
('fsKPf6m7WD5NtjgXVRFaL04wdFezDzPKQk56Z966', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoid0V5VWtzVURiUUFGOTROb3l1UGhqc0RRc0N0VXFQOUpJSHJWaXBDbiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMyI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789970759),
('hdp1EYEPIgYvgqVAmzfnQShou0GCvYDLTLFCvykI', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSllndVFkS05MQkdDam14MkVJSzZiNHNFTEpkTDZveHlURVNuZXM2OSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==', 1789968554),
('IFALdxZZih5p5xhPEJ9g1rWoyfGDMngQI4nA5a1l', NULL, '127.0.0.1', 'curl/8.12.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVzc1YVlBcVpiY3JQNExUdW5FNGJkV004WWRTTWRuZ00wSEM4Mm1HcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9saXN0aW5ncy85OTk5OTkiO3M6NToicm91dGUiO3M6MTM6Imxpc3RpbmdzLnNob3ciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1789971118),
('JWzvdjzNrLZErCmaLy0EfYhnvrRDFUQJNE9TAx2m', NULL, '127.0.0.1', 'curl/8.12.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQmVzYXAxdTNJVnEyRkxEd3ZodDBDN0V5S3VROE43M3dpMVdXN2R5eiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMyI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789968009),
('kLny4jXqARsAFqnd0WtiF9Hy2sw9MQhq9YHEc9cu', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiN3duZ0t4a05RdHNMdWtpQTlSRjZPdzJxVEVBTGFoOWcwNkN0NUduNSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9vd25lci9yZW50YWxzIjtzOjU6InJvdXRlIjtzOjE5OiJvd25lci5yZW50YWxzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9', 1789970730),
('KxORgNo4EMlEHfAcNeyqPqBc7DDsQLpepwmY4Ipy', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOWRKanlPemJBOXhCVGVZd3FKTGlpVzR5NDZHMEdLNzMyMFZHVWFXSiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo0MzoiaHR0cDovLzEyNy4wLjAuMTo4MTIzL293bmVyL3JlbnRhbC1yZXF1ZXN0cyI7czo1OiJyb3V0ZSI7czoyNzoib3duZXIucmVudGFsLXJlcXVlc3RzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789970482),
('McZl1inatXSaBGy2PmsD3ILGRRKdIiZpJ5PVI69S', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiblpLc25nQlAzaHdOMkwwcWI0VkJiYkJCYXBnelR4dG5abmRXaW5mRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9hZG1pbi9saXN0aW5ncyI7czo1OiJyb3V0ZSI7czoyMDoiYWRtaW4ubGlzdGluZ3MuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1789968901),
('n1ZYfcNP889YDriSaT1KrFcFlz6JcZi3OXDPQY7I', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoidk5pRERadFNkQ2NXdXRFSHVKNkZZSUNxT242clh1dGZJcWZYdGdzNiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9tYXAiO3M6NToicm91dGUiO3M6MzoibWFwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789970781),
('N7BYAx9epUvjFbn0tmptLwRlE2wafvRCDirF5hNj', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYXlaeGRQY2dSMERsZ0NnYmNWa0ZrWTV4YWpxSlhYQ0lmdDl2dmpVMCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9tZXNzYWdlcyI7czo1OiJyb3V0ZSI7czoxNDoibWVzc2FnZXMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTozO30=', 1789970716),
('NCCc2UWvcBIiMrQmQBug8gc7nU4sDaiewSjAuYv4', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoieVlnSHBFaGE4MXZ4NkFhT21lUnZmRWJPV2JSSUtvUFVrdDdselBkZSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9hZG1pbi9saXN0aW5ncyI7czo1OiJyb3V0ZSI7czoyMDoiYWRtaW4ubGlzdGluZ3MuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1789969926),
('NQHgJd6Nkj5suEw4oK5x9cyFJT4pQ8tVq4sVWPUI', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWEtZQ1BlVjdnblNkSm10Y1FMSWRpOHFuVE5RQmtKcVgwcDVKNTNIUiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9hZG1pbi9saXN0aW5ncyI7czo1OiJyb3V0ZSI7czoyMDoiYWRtaW4ubGlzdGluZ3MuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1789968652),
('o0CKoSOahXxAoANZbbZjFuiS3aMylljRpVsMn5nC', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTFltck0zaW9sR1ZRVUFYeHFralp4VUJGR1BhRzVObzJZU3BCVVZvYiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9', 1789968607),
('OgB23BkYmnu4XdeC1wtYf3pSq50BTvOLCjXQjklQ', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWDE4TUFlRHc1UU5RZExEdU9vakNDTTN6VlM1bTBFRU0xdEY4Zk5CWCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9hZG1pbi9saXN0aW5ncyI7fX0=', 1789968531),
('p05wzcMdTZsQCTc5Vw7zwm1Ga7qg36Tqtejq5QlQ', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUlZsSUFNS3poa0I3M0Q2a2NwVjhUNnFwd3QwMWtZZVloU0VpcXhhSiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDU6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9yZW50YWwtcmVxdWVzdHMvMjEvY2hhdCI7czo1OiJyb3V0ZSI7czoyMDoicmVudGFsLXJlcXVlc3RzLmNoYXQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=', 1789969917),
('p0OzU0wdvlc0JuVsmBkf85GcVTc4vcQWwE56KSVT', NULL, '127.0.0.1', 'curl/8.12.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiTkN2NWVMMmw5T04zaHhTM0NUcEpqckpVUGh0VDREZjhEWnh6aExrdyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9saXN0aW5ncy85OTk5OTkiO3M6NToicm91dGUiO3M6MTM6Imxpc3RpbmdzLnNob3ciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1789971105),
('qsyeXruYiHiV0et3AZy9Dxi5UGESrMF9Hg6MPFT9', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibmVVYWpWOWZQOVpVNFBDQTU2dW1BZWVGSUVEOGdvcVFhZHVidVpzYiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9', 1789968571),
('rtOQorbWVAFk4lUMaL0IGH32pCJbP7OxTdC459sL', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiTFNCU2Y4SG1YcEo0ZVVmaUFuNTRqcTF5Qk41bHJtWWVyempOcHo1VSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9hZG1pbi9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6MTU6ImFkbWluLmRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjA6e31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1789968529),
('s9iGnIuWprONL9cANv7BYCnoogchHPED09bdNbqx', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMENySDVFbFlsMUp5YWlJbVRxRTU0dk9XRVhXR3BvZVBEMXhDbEVIQSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9tZXNzYWdlcyI7fX0=', 1789968526),
('StGxNSgIXevNEY8dIcUIrYobyMDeus9PfP0fUZos', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZ2laTTlzSlIwWU5pUVIybEJLcWFGdEVwY0R0T2pqalBpRWFRVDFOQSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDc6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9yZW50ZXIvbGlzdGluZ3MvMi9yZXF1ZXN0IjtzOjU6InJvdXRlIjtzOjI5OiJyZW50ZXIucmVudGFsLXJlcXVlc3RzLmNyZWF0ZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjM7fQ==', 1789970627),
('t5Cxbdsm3BQRLKX7V2vuUZyxCjTzpc2wTuxEjYsP', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNkhKVkJYbFJ0RllKeVZzbzRqTFNIZk9UZENUZzlxamNsNG1HWklDcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9tYXAiO3M6NToicm91dGUiO3M6MzoibWFwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789970702),
('uy0TSMclOIrwkdUtfiOKd8tDK5uuFJsfUi6xrtix', NULL, '127.0.0.1', 'curl/8.12.1', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOWtRVXk0RTJsT1ZtSkdNZ2FnZGxXN2FnMEl0azhOZkRUMnJyb2kyUSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMyI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789968116),
('wmLgLhk0FBVCNyqlyiVsONjWuZRWKfc4Jlm6JlCx', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiWnJ0cFNTcFdKcE5QWldvMk01cnd3UDlJbW42czhXYnM3OGRWMzBXdyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1789970672),
('X5ansL5JRFk4VoT6201zhxL2LGfnxu1myFGaaLol', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/149.0.7827.55 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZ1NZa204SkE5WVVHZDVyaWdVNEtMUkd5bVJEMzFsQUhBU1JScndBViI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODEyMy9tZXNzYWdlcyI7czo1OiJyb3V0ZSI7czoxNDoibWVzc2FnZXMuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTozO30=', 1789970796);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'member',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `phone` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `suspension_reason` varchar(255) DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `role`, `status`, `phone`, `address`, `avatar_path`, `suspension_reason`, `suspended_at`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Tala Admin', 'admin@tala.test', 'admin', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'Z7uiz7ZboJ', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(2, 'Demo Owner', 'owner@tala.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'T8poMorPGN', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(3, 'Demo Renter', 'renter@tala.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', '4X4Jaepm5O', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(4, 'Ben Tennyson', 'ben.tennyson@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'n86rFaMOrn', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(5, 'Gumball Watterson', 'gumball.watterson@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'LeilrdMfvH', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(6, 'Finn Mertens', 'finn.mertens@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'eH5Ia40Fcs', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(7, 'Marceline Abadeer', 'marceline.abadeer@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'lUgGXojhis', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(8, 'Steven Universe', 'steven.universe@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'm2GUH0KT8x', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(9, 'Nigel Uno', 'nigel.uno@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'NzOqlH8DCa', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(10, 'Wallabee Beetles', 'wallabee.beetles@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'OcN37xjDLq', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(11, 'Blossom Utonium', 'blossom.utonium@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'hefbgW4IX7', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(12, 'Johnny Bravo', 'johnny.bravo@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'RiB16ti3dq', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(13, 'Samurai Jack', 'samurai.jack@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'RyLPAPLeTP', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(14, 'Dexter', 'dexter@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'ABPp6ml2ri', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(15, 'Mordecai', 'mordecai@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', '0pXsdJUZmy', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(16, 'Rigby', 'rigby@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'aJOT0Hd7w0', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(17, 'Robin', 'robin@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'TwgwqjV4Uu', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(18, 'Starfire', 'starfire@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'zBX5N9CznP', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(19, 'Courage', 'courage@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'xH6wIzjFJ0', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(20, 'Eddy', 'eddy@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'KDmMZNvCsP', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(21, 'Double D', 'double.d@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', '4YKN4VA3XO', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(22, 'Mac', 'mac@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', 'trNnYwb0Lh', '2026-09-07 08:12:31', '2026-09-07 08:12:31'),
(23, 'Bubbles Utonium', 'bubbles.utonium@lendly.test', 'member', 'active', NULL, NULL, NULL, NULL, NULL, '2026-09-07 08:12:31', '$2y$12$hAEzWq6ExkBp7hOjgDlZ.OUfdTFwgUKiI37ZbP2zYUQsLI9oZo2fO', '9ViZncLrGX', '2026-09-07 08:12:31', '2026-09-07 08:12:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `commission_settings`
--
ALTER TABLE `commission_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `condition_records`
--
ALTER TABLE `condition_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `condition_records_rental_id_type_unique` (`rental_id`,`type`),
  ADD KEY `condition_records_recorded_by_foreign` (`recorded_by`);

--
-- Indexes for table `condition_record_photos`
--
ALTER TABLE `condition_record_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `condition_record_photos_condition_record_id_foreign` (`condition_record_id`);

--
-- Indexes for table `damage_reports`
--
ALTER TABLE `damage_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `damage_reports_rental_id_foreign` (`rental_id`),
  ADD KEY `damage_reports_condition_record_id_foreign` (`condition_record_id`);

--
-- Indexes for table `damage_report_photos`
--
ALTER TABLE `damage_report_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `damage_report_photos_damage_report_id_foreign` (`damage_report_id`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disputes_rental_id_foreign` (`rental_id`),
  ADD KEY `disputes_damage_report_id_foreign` (`damage_report_id`),
  ADD KEY `disputes_raised_by_foreign` (`raised_by`),
  ADD KEY `disputes_resolved_by_foreign` (`resolved_by`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `listings`
--
ALTER TABLE `listings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `listings_owner_id_foreign` (`owner_id`),
  ADD KEY `listings_category_id_foreign` (`category_id`),
  ADD KEY `listings_subcategory_id_foreign` (`subcategory_id`);

--
-- Indexes for table `listing_images`
--
ALTER TABLE `listing_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `listing_images_listing_id_foreign` (`listing_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_rental_request_id_foreign` (`rental_request_id`),
  ADD KEY `messages_sender_id_foreign` (`sender_id`),
  ADD KEY `messages_receiver_id_foreign` (`receiver_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_transaction_reference_unique` (`transaction_reference`),
  ADD KEY `payments_rental_id_foreign` (`rental_id`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rentals_rental_request_id_foreign` (`rental_request_id`),
  ADD KEY `rentals_listing_id_foreign` (`listing_id`),
  ADD KEY `rentals_owner_id_foreign` (`owner_id`),
  ADD KEY `rentals_renter_id_foreign` (`renter_id`);

--
-- Indexes for table `rental_requests`
--
ALTER TABLE `rental_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rental_requests_renter_id_foreign` (`renter_id`),
  ADD KEY `rental_requests_listing_id_status_index` (`listing_id`,`status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reviews_rental_id_type_unique` (`rental_id`,`type`);

--
-- Indexes for table `security_deposits`
--
ALTER TABLE `security_deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `security_deposits_rental_id_foreign` (`rental_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `commission_settings`
--
ALTER TABLE `commission_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `condition_records`
--
ALTER TABLE `condition_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `condition_record_photos`
--
ALTER TABLE `condition_record_photos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `damage_reports`
--
ALTER TABLE `damage_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `damage_report_photos`
--
ALTER TABLE `damage_report_photos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `listings`
--
ALTER TABLE `listings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `listing_images`
--
ALTER TABLE `listing_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `rental_requests`
--
ALTER TABLE `rental_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `security_deposits`
--
ALTER TABLE `security_deposits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `condition_records`
--
ALTER TABLE `condition_records`
  ADD CONSTRAINT `condition_records_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `condition_records_rental_id_foreign` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `condition_record_photos`
--
ALTER TABLE `condition_record_photos`
  ADD CONSTRAINT `condition_record_photos_condition_record_id_foreign` FOREIGN KEY (`condition_record_id`) REFERENCES `condition_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `damage_reports`
--
ALTER TABLE `damage_reports`
  ADD CONSTRAINT `damage_reports_condition_record_id_foreign` FOREIGN KEY (`condition_record_id`) REFERENCES `condition_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `damage_reports_rental_id_foreign` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `damage_report_photos`
--
ALTER TABLE `damage_report_photos`
  ADD CONSTRAINT `damage_report_photos_damage_report_id_foreign` FOREIGN KEY (`damage_report_id`) REFERENCES `damage_reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `disputes_damage_report_id_foreign` FOREIGN KEY (`damage_report_id`) REFERENCES `damage_reports` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `disputes_raised_by_foreign` FOREIGN KEY (`raised_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disputes_rental_id_foreign` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disputes_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `listings_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `listings_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `listings_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `listing_images`
--
ALTER TABLE `listing_images`
  ADD CONSTRAINT `listing_images_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_rental_request_id_foreign` FOREIGN KEY (`rental_request_id`) REFERENCES `rental_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_rental_id_foreign` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `rentals_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rentals_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rentals_rental_request_id_foreign` FOREIGN KEY (`rental_request_id`) REFERENCES `rental_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rentals_renter_id_foreign` FOREIGN KEY (`renter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rental_requests`
--
ALTER TABLE `rental_requests`
  ADD CONSTRAINT `rental_requests_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rental_requests_renter_id_foreign` FOREIGN KEY (`renter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_rental_id_foreign` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `security_deposits`
--
ALTER TABLE `security_deposits`
  ADD CONSTRAINT `security_deposits_rental_id_foreign` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
