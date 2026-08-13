-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: al_raza_pos_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (13,'Panaflex',NULL,'2026-01-12 08:36:57','2026-01-12 08:36:57'),(14,'INK',NULL,'2026-01-12 09:03:21','2026-01-12 09:03:21'),(15,'Rings',NULL,'2026-01-12 09:42:10','2026-01-12 09:42:10');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_settings`
--

DROP TABLE IF EXISTS `company_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `phone_1` varchar(255) DEFAULT NULL,
  `phone_2` varchar(255) DEFAULT NULL,
  `whatsapp_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `ntn` varchar(255) DEFAULT NULL,
  `sales_tax_no` varchar(255) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'PKR',
  `invoice_prefix` varchar(10) NOT NULL DEFAULT 'INV-',
  `footer_note` varchar(500) DEFAULT NULL,
  `print_footer_message` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_settings`
--

LOCK TABLES `company_settings` WRITE;
/*!40000 ALTER TABLE `company_settings` DISABLE KEYS */;
INSERT INTO `company_settings` VALUES (1,'Al-Raza Graphics','Quality Printing Solutions','company/YBZwR23LbcDaaIn3iHqb6tYZrAOx9Y4UF4dEem9e.png','03067288442','03016577642','03067288442','alrazagrafix786@gmail.com','Near Meezan Bank, 50-A Timber Market, Vehari',NULL,NULL,NULL,'PKR','INV-','Thank you for choosing our services!','Quality guaranteed! Visit us again.','2025-11-08 10:47:33','2026-01-12 13:06:45');
/*!40000 ALTER TABLE `company_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_advances`
--

DROP TABLE IF EXISTS `customer_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_advances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` text DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_advances_customer_id_foreign` (`customer_id`),
  KEY `customer_advances_user_id_foreign` (`user_id`),
  CONSTRAINT `customer_advances_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_advances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_advances`
--

LOCK TABLES `customer_advances` WRITE;
/*!40000 ALTER TABLE `customer_advances` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_advances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_credit_payments`
--

DROP TABLE IF EXISTS `customer_credit_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_credit_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` text DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_credit_payments_customer_id_foreign` (`customer_id`),
  KEY `customer_credit_payments_sale_id_foreign` (`sale_id`),
  KEY `customer_credit_payments_user_id_foreign` (`user_id`),
  CONSTRAINT `customer_credit_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_credit_payments_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_credit_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_credit_payments`
--

LOCK TABLES `customer_credit_payments` WRITE;
/*!40000 ALTER TABLE `customer_credit_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_credit_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `customer_type` enum('individual','business') NOT NULL DEFAULT 'individual',
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `credit_used` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (36,'Walk-in Customer',NULL,NULL,NULL,NULL,NULL,NULL,'individual',0.00,168.00,NULL,0.00,'2026-01-12 08:33:09','2026-01-13 05:11:55'),(37,'Waseem Saab',NULL,NULL,NULL,NULL,NULL,NULL,'individual',0.00,112300.00,NULL,112300.00,'2026-01-12 08:46:27','2026-01-12 08:47:06'),(38,'Sir Younas Watto',NULL,NULL,NULL,NULL,NULL,NULL,'individual',0.00,66720.00,NULL,59620.00,'2026-01-12 08:48:32','2026-01-12 08:53:05'),(39,'Usman Saab','033334343164',NULL,NULL,NULL,NULL,NULL,'individual',0.00,311645.00,'10-12-2025 remaining balance',311645.00,'2026-01-12 09:27:30','2026-01-12 09:27:30'),(40,'Qaser Saab','03006383556',NULL,NULL,NULL,NULL,NULL,'individual',0.00,24335.00,'9-9-2025 remaining balance',24335.00,'2026-01-12 09:29:15','2026-01-12 09:29:15'),(41,'Abdullah Saab','03074992452',NULL,NULL,NULL,NULL,NULL,'individual',0.00,28140.00,'6-11-2025 remining balance',28140.00,'2026-01-12 09:30:43','2026-01-12 09:30:43'),(42,'Asif Mari Saab','03057684387',NULL,NULL,NULL,NULL,NULL,'individual',0.00,36400.00,'Bill no. 1610, 1619,',36400.00,'2026-01-12 09:31:57','2026-01-12 09:32:54'),(43,'Nadeem Butt Saab','03037186200',NULL,NULL,NULL,NULL,NULL,'individual',0.00,29908.00,'16-10-2025 remianing balance',29908.00,'2026-01-12 09:34:35','2026-01-12 09:34:35'),(44,'Ashraf Chawala','03007735673',NULL,NULL,NULL,NULL,NULL,'individual',0.00,60000.00,'Mehfil bill 35000\nDHQ bill 25000',60000.00,'2026-01-12 09:38:05','2026-01-12 09:38:05'),(45,'Rana Khalil',NULL,NULL,NULL,NULL,NULL,NULL,'individual',0.00,10000.00,NULL,10000.00,'2026-01-12 09:54:19','2026-01-12 09:54:19');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'Entertainment',NULL,'2025-12-14 06:24:20','2025-12-14 06:24:20'),(2,'chae',NULL,'2025-12-14 08:47:10','2025-12-14 08:47:10'),(3,'roti',NULL,'2025-12-14 08:47:19','2025-12-14 08:47:19'),(4,'bill bijli',NULL,'2025-12-14 08:48:28','2025-12-14 08:48:28'),(5,'Internet Bill',NULL,'2026-01-12 08:43:47','2026-01-12 08:43:47'),(6,'INk',NULL,'2026-01-12 09:18:37','2026-01-12 09:18:37');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expense_category_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `payment_source` enum('drawer','external') NOT NULL DEFAULT 'drawer',
  `user_id` bigint(20) unsigned NOT NULL,
  `register_session_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_expense_category_id_foreign` (`expense_category_id`),
  KEY `expenses_user_id_foreign` (`user_id`),
  KEY `expenses_register_session_id_foreign` (`register_session_id`),
  CONSTRAINT `expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expenses_register_session_id_foreign` FOREIGN KEY (`register_session_id`) REFERENCES `register_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (5,5,4000.00,NULL,'2026-01-12','external',2,NULL,'2026-01-12 08:45:05','2026-01-12 08:45:05'),(6,6,44000.00,'1 Carton Ink','2026-01-12','external',2,NULL,'2026-01-12 09:22:03','2026-01-12 09:22:03');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_10_22_180745_create_company_settings_table',1),(5,'2025_10_22_184407_create_units_table',1),(6,'2025_10_22_184412_create_categories_table',1),(7,'2025_10_22_184718_create_products_table',1),(8,'2025_10_22_184722_create_panaflex_specs_table',1),(9,'2025_10_23_013921_create_customers_table',1),(10,'2025_10_23_013925_create_sales_table',1),(11,'2025_10_23_013930_create_sale_items_table',1),(12,'2025_10_23_013933_create_pending_payments_table',1),(13,'2025_10_23_123032_create_sale_returns_table',1),(14,'2025_10_23_123039_create_sale_return_items_table',1),(15,'2025_10_23_133539_create_suppliers_table',1),(16,'2025_10_23_133558_create_purchases_table',1),(17,'2025_10_23_133630_create_purchase_items_table',1),(18,'2025_10_23_133659_create_stock_batches_table',1),(19,'2025_10_23_133719_create_stock_moves_table',1),(20,'2025_10_23_133746_create_stock_adjustments_table',1),(21,'2025_10_23_133810_add_min_stock_fields_to_products_table',1),(22,'2025_10_23_164854_add_role_to_users_table',1),(23,'2025_10_24_000000_add_symbol_to_units_table',1),(24,'2025_10_24_000001_add_stock_columns_to_products_table',1),(25,'2025_10_24_000002_add_email_to_customers_table',1),(26,'2025_10_25_190410_add_description_to_products_table',1),(27,'2025_10_25_193116_add_additional_fields_to_customers_table',1),(28,'2025_10_25_193527_fix_credit_limit_nullable',1),(29,'2025_10_27_000001_add_advance_field_to_customers_table',1),(30,'2025_10_27_054431_add_contact_person_to_suppliers_table',1),(31,'2025_10_27_055417_add_is_active_to_suppliers_table',1),(32,'2025_10_27_055912_add_status_to_purchases_table',1),(33,'2025_10_27_193253_add_billing_fields_to_sales_table',1),(34,'2025_10_27_202420_add_advance_used_to_sales_table',1),(35,'2025_10_27_205410_add_credit_used_to_customers_table',1),(36,'2025_10_28_160620_remove_advance_field_from_customers_table',1),(37,'2025_10_28_161321_remove_advance_used_from_sales_table',1),(38,'2025_10_28_161940_create_customer_advances_table',1),(39,'2025_10_28_162009_create_customer_credit_payments_table',1),(40,'2025_10_28_165453_add_advance_used_to_sales_table',1),(41,'2025_11_06_000001_add_description_to_categories_table',1),(42,'2025_11_07_060244_add_expected_date_and_shipping_charges_to_purchases_table',1),(43,'2025_11_07_063059_add_received_quantity_to_purchase_items_table',1),(44,'2025_11_07_073101_add_unique_constraints_to_suppliers_table',1),(45,'2025_11_08_000001_create_permissions_table',1),(46,'2025_11_08_000002_create_roles_table',1),(47,'2025_11_08_000003_create_role_permissions_table',1),(48,'2025_11_08_000004_create_user_permissions_table',1),(49,'2025_11_08_073322_update_purchase_status_enum',1),(50,'2025_11_08_080717_create_register_sessions_table',1),(51,'2025_11_23_000001_add_supplier_prepayment_to_pending_payments',1),(52,'2025_11_26_000001_add_register_session_id_to_sales_table',1),(53,'2025_11_29_094257_add_opening_balance_to_suppliers_table',1),(54,'2025_12_14_000000_create_walk_in_customer',2),(55,'2025_12_14_105759_create_expense_categories_table',3),(56,'2025_12_14_105800_create_expenses_table',3),(57,'2025_12_15_022737_create_payments_table',4),(58,'2026_01_12_154203_make_product_id_nullable_in_sale_items_table',5),(59,'2026_01_12_162202_add_system_description_to_sales_table',6),(60,'2026_01_12_171247_change_rolls_count_to_decimal_in_purchase_items_table',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `panaflex_specs`
--

DROP TABLE IF EXISTS `panaflex_specs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `panaflex_specs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `roll_width_inch` decimal(8,2) NOT NULL,
  `roll_length_meter` decimal(8,2) NOT NULL,
  `rate_per_sqft` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `panaflex_specs_product_id_unique` (`product_id`),
  CONSTRAINT `panaflex_specs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `panaflex_specs`
--

LOCK TABLES `panaflex_specs` WRITE;
/*!40000 ALTER TABLE `panaflex_specs` DISABLE KEYS */;
INSERT INTO `panaflex_specs` VALUES (17,28,126.00,49.99,22.00,'2026-01-12 08:38:32','2026-01-12 08:38:32');
/*!40000 ALTER TABLE `panaflex_specs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('received','paid') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(255) NOT NULL DEFAULT 'cash',
  `note` text DEFAULT NULL,
  `current_balance` decimal(12,2) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_customer_id_foreign` (`customer_id`),
  KEY `payments_supplier_id_foreign` (`supplier_id`),
  KEY `payments_sale_id_foreign` (`sale_id`),
  KEY `payments_purchase_id_foreign` (`purchase_id`),
  KEY `payments_user_id_foreign` (`user_id`),
  CONSTRAINT `payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (46,38,NULL,NULL,NULL,'received',6000.00,'2026-01-12','cash',NULL,60720.00,2,'2026-01-12 12:19:27','2026-01-12 12:19:27');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pending_payments`
--

DROP TABLE IF EXISTS `pending_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pending_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `amount_due` decimal(12,2) NOT NULL,
  `settled` tinyint(1) NOT NULL DEFAULT 0,
  `is_prepayment` tinyint(1) NOT NULL DEFAULT 0,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pending_payments_sale_id_foreign` (`sale_id`),
  KEY `pending_payments_customer_id_foreign` (`customer_id`),
  KEY `pending_payments_supplier_id_foreign` (`supplier_id`),
  KEY `pending_payments_purchase_id_foreign` (`purchase_id`),
  CONSTRAINT `pending_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pending_payments_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pending_payments_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pending_payments_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pending_payments`
--

LOCK TABLES `pending_payments` WRITE;
/*!40000 ALTER TABLE `pending_payments` DISABLE KEYS */;
INSERT INTO `pending_payments` VALUES (76,100,NULL,37,NULL,NULL,112300.00,0,0,112300.00,NULL,'Opening Balance','2026-01-12 08:47:06','2026-01-12 08:47:06'),(77,101,NULL,38,NULL,NULL,59620.00,0,0,59620.00,NULL,'Opening Balance','2026-01-12 08:49:08','2026-01-12 08:49:08'),(78,102,NULL,38,NULL,'2026-02-11',7100.00,0,0,7100.00,NULL,NULL,'2026-01-12 08:53:05','2026-01-12 08:53:05'),(79,103,NULL,39,NULL,NULL,311645.00,0,0,311645.00,NULL,'Opening Balance','2026-01-12 09:27:30','2026-01-12 09:27:30'),(80,104,NULL,40,NULL,NULL,24335.00,0,0,24335.00,NULL,'Opening Balance','2026-01-12 09:29:15','2026-01-12 09:29:15'),(81,105,NULL,41,NULL,NULL,28140.00,0,0,28140.00,NULL,'Opening Balance','2026-01-12 09:30:43','2026-01-12 09:30:43'),(82,106,NULL,42,NULL,NULL,36400.00,0,0,36400.00,NULL,'Opening Balance','2026-01-12 09:31:57','2026-01-12 09:31:57'),(83,107,NULL,43,NULL,NULL,29908.00,0,0,29908.00,NULL,'Opening Balance','2026-01-12 09:34:35','2026-01-12 09:34:35'),(84,108,NULL,44,NULL,NULL,60000.00,0,0,60000.00,NULL,'Opening Balance','2026-01-12 09:38:05','2026-01-12 09:38:05'),(85,109,NULL,45,NULL,NULL,10000.00,0,0,10000.00,NULL,'Opening Balance','2026-01-12 09:54:19','2026-01-12 09:54:19');
/*!40000 ALTER TABLE `pending_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `module` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_slug_unique` (`slug`),
  KEY `permissions_module_is_active_index` (`module`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'View Users','users.view','View list of users','users',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(2,'Create Users','users.create','Create new users','users',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(3,'Edit Users','users.edit','Edit existing users','users',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(4,'Delete Users','users.delete','Delete users','users',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(5,'Manage User Roles','users.roles','Manage user roles and permissions','users',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(6,'View Products','products.view','View product listings','products',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(7,'Create Products','products.create','Create new products','products',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(8,'Edit Products','products.edit','Edit existing products','products',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(9,'Delete Products','products.delete','Delete products','products',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(10,'Manage Product Categories','products.categories','Manage product categories','products',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(11,'Manage Product Units','products.units','Manage product units','products',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(12,'Import/Export Products','products.import_export','Import and export products','products',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(13,'View Customers','customers.view','View customer list','customers',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(14,'Create Customers','customers.create','Create new customers','customers',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(15,'Edit Customers','customers.edit','Edit customer information','customers',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(16,'Delete Customers','customers.delete','Delete customers','customers',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(17,'Manage Customer Payments','customers.payments','Manage customer advance payments','customers',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(18,'View All Sales','sales.view_all','View all sales records','sales',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(19,'View Own Sales','sales.view_own','View only own sales records','sales',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(20,'Create Sales','sales.create','Create new sales/invoices','sales',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(21,'Edit Sales','sales.edit','Edit sales records','sales',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(22,'Delete Sales','sales.delete','Delete sales records','sales',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(23,'Process Returns','sales.returns','Process sales returns','sales',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(24,'Use POS System','sales.pos','Access POS system','sales',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(25,'Print Invoices','sales.print','Print sales invoices','sales',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(26,'View Suppliers','suppliers.view','View supplier list','suppliers',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(27,'Create Suppliers','suppliers.create','Create new suppliers','suppliers',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(28,'Edit Suppliers','suppliers.edit','Edit supplier information','suppliers',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(29,'Delete Suppliers','suppliers.delete','Delete suppliers','suppliers',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(30,'View Purchases','purchases.view','View purchase records','purchases',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(31,'Create Purchases','purchases.create','Create purchase orders','purchases',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(32,'Edit Purchases','purchases.edit','Edit purchase orders','purchases',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(33,'Delete Purchases','purchases.delete','Delete purchase orders','purchases',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(34,'Receive Purchases','purchases.receive','Receive and process purchases','purchases',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(35,'View Inventory','inventory.view','View inventory levels','inventory',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(36,'Adjust Stock','inventory.adjust','Make stock adjustments','inventory',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(37,'View Stock History','inventory.history','View stock movement history','inventory',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(38,'View Sales Reports','reports.sales','View sales reports and analytics','reports',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(39,'View Purchase Reports','reports.purchases','View purchase reports','reports',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(40,'View Financial Reports','reports.financial','View financial and profit/loss reports','reports',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(41,'View Inventory Reports','reports.inventory','View inventory reports','reports',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(42,'Export Reports','reports.export','Export reports to various formats','reports',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(43,'Manage Company Settings','settings.company','Manage company information and settings','settings',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(44,'Manage Tax Settings','settings.tax','Configure tax rates and settings','settings',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(45,'Manage System Settings','settings.system','Configure system-wide settings','settings',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(46,'Create Backups','system.backup','Create system backups','system',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(47,'Restore Backups','system.restore','Restore system from backups','system',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(48,'Database Cleanup','system.cleanup','Perform database cleanup operations','system',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(49,'View System Info','system.info','View system information','system',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(50,'Approve Transactions','transactions.approve','Approve or reject transactions','transactions',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(51,'View Payment Entries','payments.view','View payment entries','payments',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(52,'Approve Payments','payments.approve','Approve or reject payment entries','payments',1,'2025-11-08 10:47:31','2025-11-08 10:47:31');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('simple','panaflex_roll') NOT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sale_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `purchase_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `taxable` tinyint(1) NOT NULL DEFAULT 0,
  `barcode` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `stock_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_meters` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `min_qty` int(11) DEFAULT NULL,
  `min_meters` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_unit_id_foreign` (`unit_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (28,'China FLC','PFCHI9555',13,'panaflex_roll',NULL,'',22.00,13.50,0,'SPCHI1018',NULL,1,199.96,192.60,'2026-01-12 08:38:32','2026-01-13 05:41:09',0,49.99),(29,'Rings','SPRIN3237',15,'simple',15,'',10.00,2.50,0,'SPRIN4942',NULL,1,0.00,0.00,'2026-01-12 09:47:08','2026-01-12 09:47:08',0,0.00);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `received_quantity` int(11) NOT NULL DEFAULT 0,
  `roll_width_inch` decimal(8,2) DEFAULT NULL,
  `roll_length_meter` decimal(8,2) DEFAULT NULL,
  `rolls_count` decimal(10,2) DEFAULT NULL,
  `rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_items_product_id_foreign` (`product_id`),
  CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` VALUES (46,38,28,6888,6888,126.00,49.99,4.00,13.50,92988.00,'2026-01-12 08:40:35','2026-01-12 08:40:35');
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_no` varchar(255) NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `purchased_at` datetime NOT NULL,
  `expected_date` datetime DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `other_charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','ordered','received','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchases_purchase_no_unique` (`purchase_no`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_user_id_foreign` (`user_id`),
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (38,'PUR-0000001',21,2,'2026-01-12 00:00:00','2026-01-12 00:00:00',92988.00,0.00,0.00,0.00,0.00,92988.00,'received',NULL,'2026-01-12 08:40:35','2026-01-12 08:40:35');
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `register_sessions`
--

DROP TABLE IF EXISTS `register_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `register_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `opening_cash` decimal(10,2) NOT NULL,
  `closing_cash` decimal(10,2) DEFAULT NULL,
  `expected_cash` decimal(10,2) DEFAULT NULL,
  `cash_difference` decimal(10,2) DEFAULT NULL,
  `opening_notes` text DEFAULT NULL,
  `closing_notes` text DEFAULT NULL,
  `opened_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `register_sessions_user_id_foreign` (`user_id`),
  CONSTRAINT `register_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `register_sessions`
--

LOCK TABLES `register_sessions` WRITE;
/*!40000 ALTER TABLE `register_sessions` DISABLE KEYS */;
INSERT INTO `register_sessions` VALUES (8,2,500.00,NULL,NULL,NULL,NULL,NULL,'2025-12-17 13:10:55',NULL,'open','2025-12-17 13:10:55','2025-12-17 13:10:55');
/*!40000 ALTER TABLE `register_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,2,13,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(2,2,14,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(3,2,15,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(4,2,17,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(5,2,19,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(6,2,20,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(7,2,24,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(8,2,25,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(9,2,6,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(10,2,35,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(11,2,38,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(12,3,38,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(13,3,39,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(14,3,40,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(15,3,41,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(16,3,42,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(17,3,51,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(18,3,52,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(19,3,50,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(20,3,18,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(21,3,30,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(22,3,13,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(23,3,26,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(24,3,6,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(25,3,35,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(28,3,14,'2025-12-16 07:11:21','2025-12-16 07:11:21'),(31,3,20,'2025-12-16 07:29:01','2025-12-16 07:29:01');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrator','admin','Full system access with all permissions',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(2,'Sales Staff','sales','Limited access focused on sales operations',1,'2025-11-08 10:47:31','2025-11-08 10:47:31'),(3,'Accountant','accountant','Access to financial data and reports',1,'2025-11-08 10:47:31','2025-11-08 10:47:31');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `rate` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `units_sqft` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL,
  `length_input` decimal(12,4) DEFAULT NULL,
  `length_unit` enum('m','ft') DEFAULT NULL,
  `width_input` decimal(12,4) DEFAULT NULL,
  `width_unit` enum('in','ft') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_items_sale_id_foreign` (`sale_id`),
  KEY `sale_items_product_id_foreign` (`product_id`),
  CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=166 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
INSERT INTO `sale_items` VALUES (122,102,28,'4ft ?? 2ft ?? 20',20,28.00,0.00,0.00,160.00,4480.00,4.0000,'ft',2.0000,'ft','2026-01-12 08:53:05','2026-01-12 08:53:05'),(123,102,28,'8ft ?? 11.7ft ?? 1',1,28.00,0.00,0.00,93.60,2620.80,8.0000,'ft',11.7000,'ft','2026-01-12 08:53:05','2026-01-12 08:53:05');
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_return_items`
--

DROP TABLE IF EXISTS `sale_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_return_id` bigint(20) unsigned NOT NULL,
  `sale_item_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `units_sqft` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `length_input` decimal(12,4) DEFAULT NULL,
  `length_unit` enum('m','ft') DEFAULT NULL,
  `width_input` decimal(12,4) DEFAULT NULL,
  `width_unit` enum('in','ft') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_return_items_sale_return_id_foreign` (`sale_return_id`),
  KEY `sale_return_items_sale_item_id_foreign` (`sale_item_id`),
  CONSTRAINT `sale_return_items_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_return_items_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_return_items`
--

LOCK TABLES `sale_return_items` WRITE;
/*!40000 ALTER TABLE `sale_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_returns`
--

DROP TABLE IF EXISTS `sale_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `return_no` varchar(255) NOT NULL,
  `returned_at` datetime NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `other_adjustments` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_returns_return_no_unique` (`return_no`),
  KEY `sale_returns_sale_id_foreign` (`sale_id`),
  KEY `sale_returns_user_id_foreign` (`user_id`),
  CONSTRAINT `sale_returns_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_returns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_returns`
--

LOCK TABLES `sale_returns` WRITE;
/*!40000 ALTER TABLE `sale_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `register_session_id` bigint(20) unsigned DEFAULT NULL,
  `sold_at` datetime NOT NULL,
  `payment_type` enum('cash','credit') NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `utilities_charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bill_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `previous_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `advance_used` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `system_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_invoice_no_unique` (`invoice_no`),
  KEY `sales_customer_id_foreign` (`customer_id`),
  KEY `sales_user_id_foreign` (`user_id`),
  KEY `sales_register_session_id_foreign` (`register_session_id`),
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_register_session_id_foreign` FOREIGN KEY (`register_session_id`) REFERENCES `register_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (100,'OPB-00037',37,2,NULL,'2026-01-12 08:47:06','credit',112300.00,0.00,0.00,0.00,0.00,112300.00,0.00,112300.00,0.00,-112300.00,0.00,'Opening Balance',NULL,'2026-01-12 08:47:06','2026-01-12 08:47:06'),(101,'OPB-00038',38,2,NULL,'2026-01-12 08:49:08','credit',59620.00,0.00,0.00,0.00,0.00,59620.00,0.00,59620.00,0.00,-59620.00,0.00,'Opening Balance',NULL,'2026-01-12 08:49:08','2026-01-12 08:49:08'),(102,'INV-0000001',38,2,8,'2026-01-12 08:53:05','credit',7100.80,0.80,0.00,0.00,0.00,7100.00,59620.00,66720.00,0.00,66720.00,0.00,NULL,NULL,'2026-01-12 08:53:05','2026-01-12 08:53:05'),(103,'OPB-00039',39,2,NULL,'2026-01-12 09:27:30','credit',311645.00,0.00,0.00,0.00,0.00,311645.00,0.00,311645.00,0.00,-311645.00,0.00,'Opening Balance',NULL,'2026-01-12 09:27:30','2026-01-12 09:27:30'),(104,'OPB-00040',40,2,NULL,'2026-01-12 09:29:15','credit',24335.00,0.00,0.00,0.00,0.00,24335.00,0.00,24335.00,0.00,-24335.00,0.00,'Opening Balance',NULL,'2026-01-12 09:29:15','2026-01-12 09:29:15'),(105,'OPB-00041',41,2,NULL,'2026-01-12 09:30:43','credit',28140.00,0.00,0.00,0.00,0.00,28140.00,0.00,28140.00,0.00,-28140.00,0.00,'Opening Balance',NULL,'2026-01-12 09:30:43','2026-01-12 09:30:43'),(106,'OPB-00042',42,2,NULL,'2026-01-12 09:31:57','credit',36400.00,0.00,0.00,0.00,0.00,36400.00,0.00,36400.00,0.00,-36400.00,0.00,'Opening Balance',NULL,'2026-01-12 09:31:57','2026-01-12 09:31:57'),(107,'OPB-00043',43,2,NULL,'2026-01-12 09:34:35','credit',29908.00,0.00,0.00,0.00,0.00,29908.00,0.00,29908.00,0.00,-29908.00,0.00,'Opening Balance',NULL,'2026-01-12 09:34:35','2026-01-12 09:34:35'),(108,'OPB-00044',44,2,NULL,'2026-01-12 09:38:05','credit',60000.00,0.00,0.00,0.00,0.00,60000.00,0.00,60000.00,0.00,-60000.00,0.00,'Opening Balance',NULL,'2026-01-12 09:38:05','2026-01-12 09:38:05'),(109,'OPB-00045',45,2,NULL,'2026-01-12 09:54:19','credit',10000.00,0.00,0.00,0.00,0.00,10000.00,0.00,10000.00,0.00,-10000.00,0.00,'Opening Balance',NULL,'2026-01-12 09:54:19','2026-01-12 09:54:19');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `reason` enum('damage','shrinkage','correction','other') NOT NULL,
  `qty_delta` decimal(12,2) DEFAULT NULL,
  `meters_delta` decimal(12,2) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_adjustments_product_id_foreign` (`product_id`),
  KEY `stock_adjustments_user_id_foreign` (`user_id`),
  CONSTRAINT `stock_adjustments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_adjustments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustments`
--

LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_batches`
--

DROP TABLE IF EXISTS `stock_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `batch_no` varchar(255) DEFAULT NULL,
  `purchase_item_id` bigint(20) unsigned DEFAULT NULL,
  `qty_total` int(11) DEFAULT NULL,
  `qty_remaining` int(11) DEFAULT NULL,
  `roll_width_inch` decimal(8,2) DEFAULT NULL,
  `meters_total` decimal(12,2) DEFAULT NULL,
  `meters_remaining` decimal(12,2) DEFAULT NULL,
  `received_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_batches_product_id_foreign` (`product_id`),
  KEY `stock_batches_purchase_item_id_foreign` (`purchase_item_id`),
  CONSTRAINT `stock_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_batches_purchase_item_id_foreign` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_batches`
--

LOCK TABLES `stock_batches` WRITE;
/*!40000 ALTER TABLE `stock_batches` DISABLE KEYS */;
INSERT INTO `stock_batches` VALUES (36,28,'PFCHI9555-20260112-001',46,NULL,NULL,126.00,199.96,199.96,'2026-01-12','2026-01-12 08:40:35','2026-01-12 08:40:35');
/*!40000 ALTER TABLE `stock_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_moves`
--

DROP TABLE IF EXISTS `stock_moves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_moves` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `type` enum('purchase','sale','return','adjustment') NOT NULL,
  `ref_id` bigint(20) unsigned DEFAULT NULL,
  `ref_table` varchar(255) DEFAULT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `qty_change` decimal(12,2) DEFAULT NULL,
  `meters_change` decimal(12,2) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_moves_product_id_foreign` (`product_id`),
  KEY `stock_moves_batch_id_foreign` (`batch_id`),
  KEY `stock_moves_user_id_foreign` (`user_id`),
  CONSTRAINT `stock_moves_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `stock_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_moves_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_moves_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_moves`
--

LOCK TABLES `stock_moves` WRITE;
/*!40000 ALTER TABLE `stock_moves` DISABLE KEYS */;
INSERT INTO `stock_moves` VALUES (27,28,'purchase',38,'purchases',36,NULL,199.96,2,'Purchase received - 4 rolls','2026-01-12 08:40:35','2026-01-12 08:40:35'),(30,28,'purchase',49,'purchase_items',NULL,NULL,154969.00,2,'Received purchase item - Batch: 2','2026-01-12 12:30:47','2026-01-12 12:30:47');
/*!40000 ALTER TABLE `stock_moves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `contact_person` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_name_unique` (`name`),
  KEY `suppliers_email_index` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (21,'Al-Raza Trader','03067288442','03067288442',NULL,NULL,0.00,'Zohaib Siddiqe',1,'2026-01-12 08:35:31','2026-01-12 08:35:31');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (13,'0001','Feet','ft','2026-01-12 08:36:45','2026-01-12 08:36:45'),(14,'0002','Litter','Ltr','2026-01-12 09:03:12','2026-01-12 09:03:12'),(15,'0003','Pieces','Pcs','2026-01-12 09:42:00','2026-01-12 09:42:00');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_permissions_user_id_permission_id_unique` (`user_id`,`permission_id`),
  KEY `user_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `user_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('admin','sales','accountant') NOT NULL DEFAULT 'sales',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Narmer Solutions','narmersolutionsadmin@pos.com','admin','2025-11-08 10:47:32','$2y$12$svsuYElNQJ6ZngbNlmiOneqnsG7rrq4XHWb/udpDiuV8P5GEZRZn2','sf1pscEajcgb5XHkivlspsZyM8HpUEO4bzwohEV4Cak1E9aT7jc5LuWsSO42','2025-11-08 10:47:32','2026-01-10 17:19:32'),(2,'Zohaib Siddiqui','zohaibsiddiqui@ns.com','admin',NULL,'$2y$12$j.uBNyg83O3J50e6znFeDuFzhm3NsU4obszujcB2Q6888.ATkxrw.','0t59ABQXKfyQVBF7eEuZEhOJPofaxbGu9PzxbYX3d3XrNdRLu9sL2xehMTRE','2025-12-16 06:37:45','2025-12-16 06:37:45');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-13 16:19:11
