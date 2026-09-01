-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tit_educations_2
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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_institute_id_created_at_index` (`institute_id`,`created_at`),
  CONSTRAINT `activity_logs_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_message_reads`
--

DROP TABLE IF EXISTS `admin_message_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_message_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_message_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_message_reads_admin_message_id_user_id_unique` (`admin_message_id`,`user_id`),
  KEY `admin_message_reads_user_id_foreign` (`user_id`),
  CONSTRAINT `admin_message_reads_admin_message_id_foreign` FOREIGN KEY (`admin_message_id`) REFERENCES `admin_messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_message_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_message_reads`
--

LOCK TABLES `admin_message_reads` WRITE;
/*!40000 ALTER TABLE `admin_message_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_message_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_messages`
--

DROP TABLE IF EXISTS `admin_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `body` text NOT NULL,
  `target_type` varchar(20) NOT NULL,
  `target_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_messages_target_user_id_foreign` (`target_user_id`),
  KEY `admin_messages_target_type_target_user_id_index` (`target_type`,`target_user_id`),
  KEY `admin_messages_institute_id_foreign` (`institute_id`),
  CONSTRAINT `admin_messages_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`),
  CONSTRAINT `admin_messages_target_user_id_foreign` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_messages`
--

LOCK TABLES `admin_messages` WRITE;
/*!40000 ALTER TABLE `admin_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'info',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `target_role` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_submissions`
--

DROP TABLE IF EXISTS `assignment_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(191) DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assignment_submissions_assignment_id_foreign` (`assignment_id`),
  KEY `assignment_submissions_student_id_foreign` (`student_id`),
  CONSTRAINT `assignment_submissions_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_submissions`
--

LOCK TABLES `assignment_submissions` WRITE;
/*!40000 ALTER TABLE `assignment_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `grade` varchar(191) DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `file_path` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assignments_teacher_id_foreign` (`teacher_id`),
  KEY `assignments_institute_id_foreign` (`institute_id`),
  CONSTRAINT `assignments_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`),
  CONSTRAINT `assignments_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `zoom_schedule_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `role` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'absent',
  `marked_at` timestamp NULL DEFAULT NULL,
  `source` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_zoom_schedule_id_student_id_unique` (`zoom_schedule_id`,`student_id`),
  KEY `attendances_institute_id_foreign` (`institute_id`),
  KEY `attendances_zoom_schedule_id_index` (`zoom_schedule_id`),
  KEY `attendances_student_id_foreign` (`student_id`),
  CONSTRAINT `attendances_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`),
  CONSTRAINT `attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_zoom_schedule_id_foreign` FOREIGN KEY (`zoom_schedule_id`) REFERENCES `zoom_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
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
  `key` varchar(191) NOT NULL,
  `owner` varchar(191) NOT NULL,
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
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'event',
  `priority` varchar(191) NOT NULL DEFAULT 'low',
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `duration` varchar(191) DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `attendees` text DEFAULT NULL,
  `reminders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reminders`)),
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_events_user_id_foreign` (`user_id`),
  CONSTRAINT `calendar_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_events`
--

LOCK TABLES `calendar_events` WRITE;
/*!40000 ALTER TABLE `calendar_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `calendar_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `subject` varchar(191) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) NOT NULL,
  `type` enum('fixed','percentage') NOT NULL DEFAULT 'percentage',
  `value` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_bounces`
--

DROP TABLE IF EXISTS `email_bounces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_bounces` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `bounce_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_bounced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_bounces_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_bounces`
--

LOCK TABLES `email_bounces` WRITE;
/*!40000 ALTER TABLE `email_bounces` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_bounces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_results`
--

DROP TABLE IF EXISTS `exam_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_name` varchar(191) NOT NULL,
  `index_no` varchar(50) NOT NULL,
  `term` varchar(50) NOT NULL,
  `grade` varchar(50) NOT NULL,
  `subject` varchar(191) NOT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `result_grade` varchar(20) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL,
  `year` varchar(20) DEFAULT NULL,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_results_institute_id_foreign` (`institute_id`),
  KEY `exam_results_index_no_term_grade_index` (`index_no`,`term`,`grade`),
  KEY `exam_results_term_grade_year_index` (`term`,`grade`,`year`),
  CONSTRAINT `exam_results_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_results`
--

LOCK TABLES `exam_results` WRITE;
/*!40000 ALTER TABLE `exam_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_terms`
--

DROP TABLE IF EXISTS `exam_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_terms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_terms_name_institute_id_unique` (`name`,`institute_id`),
  KEY `exam_terms_institute_id_foreign` (`institute_id`),
  CONSTRAINT `exam_terms_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_terms`
--

LOCK TABLES `exam_terms` WRITE;
/*!40000 ALTER TABLE `exam_terms` DISABLE KEYS */;
INSERT INTO `exam_terms` VALUES (1,'1st Term',NULL,'2026-08-29 08:57:09','2026-08-29 08:57:09'),(2,'2nd Term',NULL,'2026-08-29 08:57:09','2026-08-29 08:57:09'),(3,'3rd Term',NULL,'2026-08-29 08:57:09','2026-08-29 08:57:09');
/*!40000 ALTER TABLE `exam_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) NOT NULL,
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
-- Table structure for table `institutes`
--

DROP TABLE IF EXISTS `institutes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `institutes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `logo_path` varchar(191) DEFAULT NULL,
  `theme_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`theme_settings`)),
  `status` enum('active','suspended','expired') NOT NULL DEFAULT 'active',
  `subscription_plan_id` bigint(20) unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `institutes_slug_unique` (`slug`),
  KEY `institutes_subscription_plan_id_foreign` (`subscription_plan_id`),
  CONSTRAINT `institutes_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institutes`
--

LOCK TABLES `institutes` WRITE;
/*!40000 ALTER TABLE `institutes` DISABLE KEYS */;
INSERT INTO `institutes` VALUES (1,'TiT education main academy','main',NULL,NULL,'active',1,NULL,'2026-08-29 16:34:49','2026-08-29 16:34:49',NULL);
/*!40000 ALTER TABLE `institutes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
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
  `queue` varchar(191) NOT NULL,
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
-- Table structure for table `learning_materials`
--

DROP TABLE IF EXISTS `learning_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `zoom_schedule_id` bigint(20) unsigned DEFAULT NULL,
  `teacher_id` bigint(20) unsigned DEFAULT NULL,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(191) NOT NULL,
  `grade` varchar(191) DEFAULT NULL,
  `medium` enum('english','tamil','both') NOT NULL DEFAULT 'tamil',
  `title` varchar(191) NOT NULL,
  `file_path` varchar(191) DEFAULT NULL,
  `file_size` varchar(191) DEFAULT NULL,
  `url` varchar(191) DEFAULT NULL,
  `group` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learning_materials_institute_id_foreign` (`institute_id`),
  KEY `learning_materials_teacher_id_foreign` (`teacher_id`),
  KEY `learning_materials_zoom_schedule_id_foreign` (`zoom_schedule_id`),
  CONSTRAINT `learning_materials_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`),
  CONSTRAINT `learning_materials_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `learning_materials_zoom_schedule_id_foreign` FOREIGN KEY (`zoom_schedule_id`) REFERENCES `zoom_schedules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_materials`
--

LOCK TABLES `learning_materials` WRITE;
/*!40000 ALTER TABLE `learning_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `learning_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_reads`
--

DROP TABLE IF EXISTS `message_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_reads_message_id_user_id_unique` (`message_id`,`user_id`),
  KEY `message_reads_user_id_foreign` (`user_id`),
  CONSTRAINT `message_reads_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_reads`
--

LOCK TABLES `message_reads` WRITE;
/*!40000 ALTER TABLE `message_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned DEFAULT NULL,
  `receiver_role` varchar(30) DEFAULT NULL,
  `subject` varchar(191) NOT NULL,
  `body` text NOT NULL,
  `is_broadcast` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_receiver_id_read_at_index` (`receiver_id`,`read_at`),
  KEY `messages_receiver_role_created_at_index` (`receiver_role`,`created_at`),
  KEY `messages_sender_id_created_at_index` (`sender_id`,`created_at`),
  CONSTRAINT `messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_12_17_094814_create_personal_access_tokens_table',1),(5,'2025_12_17_101937_add_role_to_users_table',1),(6,'2025_12_31_031217_add_student_fields_to_users_table',1),(7,'2025_12_31_033207_add_phone_and_name_fields_to_users_table',1),(8,'2026_01_04_094417_add_stream_and_subjects_to_users_table',1),(9,'2026_01_18_065051_create_site_settings_table',1),(10,'2026_01_24_000000_create_learning_materials_table',1),(11,'2026_01_24_165307_add_grade_to_learning_materials_table',1),(12,'2026_02_01_000001_add_registration_and_deactivated_to_users_table',1),(13,'2026_02_01_000002_create_payments_table',1),(14,'2026_02_01_000003_create_zoom_schedules_table',1),(15,'2026_02_01_000004_create_attendances_table',1),(16,'2026_02_01_000005_create_admin_messages_tables',1),(17,'2026_02_07_050534_create_assignments_table',1),(18,'2026_02_07_050535_create_assignment_submissions_table',1),(19,'2026_02_07_053609_create_institutes_and_plans_tables',1),(20,'2026_02_07_053611_add_institute_id_to_existing_tables',1),(21,'2026_02_10_104136_create_activity_logs_table',1),(22,'2026_02_17_050000_add_soft_deletes_to_institutes_table',1),(23,'2026_02_26_102500_add_zoom_api_fields_to_zoom_schedules_table',1),(24,'2026_02_28_094236_create_announcements_table',1),(25,'2026_02_28_094518_create_coupons_table',1),(26,'2026_03_04_072211_add_plain_password_to_users_table',1),(27,'2026_03_04_074031_add_teacher_fields_to_users_table',1),(28,'2026_03_04_085841_create_subjects_table',1),(29,'2026_03_04_153000_add_category_to_subjects_table',1),(30,'2026_03_04_154000_change_subjects_unique_constraint',1),(31,'2026_03_04_200000_create_messages_table',1),(32,'2026_03_06_050912_add_profile_fields_to_users_table',1),(33,'2026_03_06_074925_create_calendar_events_table',1),(34,'2026_03_07_171255_create_notifications_table',1),(35,'2026_03_09_160057_add_reminded_at_to_zoom_schedules_table',1),(36,'2026_03_09_161138_create_timetables_table',1),(37,'2026_03_09_161139_add_zoom_host_email_to_timetables_table',1),(38,'2026_03_27_201006_create_zoom_accounts_table',1),(39,'2026_03_27_201011_add_zoom_account_id_to_related_tables',1),(40,'2026_03_31_093026_add_google_id_to_users_table',1),(41,'2026_04_01_075955_add_custom_fields_to_users_table',1),(42,'2026_04_06_100000_update_site_settings_unique_constraint',1),(43,'2026_04_10_224730_create_contact_messages_table',1),(44,'2026_04_24_113658_add_translations_to_subjects_table',1),(45,'2026_04_24_162919_add_missing_columns_to_payments_table',1),(46,'2026_07_30_004249_add_timetable_id_to_zoom_schedules_table',1),(47,'2026_07_31_200000_create_exam_results_table',1),(48,'2026_07_31_200001_create_exam_terms_table',1),(49,'2026_08_03_130424_drop_plain_password_from_users_table',1),(50,'2026_08_09_141835_add_teacher_id_to_learning_materials_table',1),(51,'2026_08_15_200000_add_medium_to_content_tables',1),(52,'2026_08_16_145046_create_packages_table',1),(53,'2026_08_19_181500_add_medium_to_subjects_unique_constraint',1),(54,'2026_08_20_232259_modify_medium_enum_in_timetables_and_zoom_schedules',1),(55,'2026_08_26_033713_add_zoom_schedule_id_to_learning_materials_table',1),(56,'2026_08_26_130000_create_email_bounces_table',1),(57,'2026_08_29_141500_create_students_table',2),(58,'2026_08_29_141505_migrate_existing_students_data',3),(59,'2026_08_29_194941_update_attendances_and_assignments_to_use_student_id',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(191) NOT NULL,
  `notifiable_type` varchar(191) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `name_ta` varchar(191) DEFAULT NULL,
  `name_si` varchar(191) DEFAULT NULL,
  `category` varchar(191) NOT NULL,
  `medium` enum('tamil','english','both') NOT NULL DEFAULT 'tamil',
  `package_price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `addon_price` decimal(10,2) DEFAULT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'all_subjects',
  `applicable_grades` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`applicable_grades`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

LOCK TABLES `packages` WRITE;
/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
/*!40000 ALTER TABLE `packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
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
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gateway_ref` varchar(191) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `payment_method` varchar(20) DEFAULT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `year_month` varchar(7) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_user_id_year_month_index` (`user_id`,`year_month`),
  KEY `payments_institute_id_foreign` (`institute_id`),
  CONSTRAINT `payments_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`),
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',19,'auth_token','b39a6da2c7011bc73ccbeecc2b6f3ed59af602c67e0f03554e15cc8ea96c9fd1','[\"*\"]',NULL,NULL,'2026-08-29 17:19:06','2026-08-29 17:19:06'),(2,'App\\Models\\User',18,'auth_token','2ae1576d5c7bde456965d500c30c75763d1b8bbd822e627c10bfc88d537189f3','[\"*\"]','2026-08-30 07:20:55',NULL,'2026-08-30 07:06:36','2026-08-30 07:20:55');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
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
INSERT INTO `sessions` VALUES ('1XFiURAIrs6RRrXCBedvLKicZ4OiP3Rh5cOAXr8F',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoieGppY2twUllXN1dFNWFGbXlYVklQeXI4SVVkdm9zTlZnOWl6azJseiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zYW5jdHVtL2NzcmYtY29va2llIjtzOjU6InJvdXRlIjtzOjE5OiJzYW5jdHVtLmNzcmYtY29va2llIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1788073582),('9NazyLMNoE95SpENacuxEvUJ2cDf3LVPFkhH7Mjh',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoid25ZeVRCM0FKa1RzZ3RndE8xdjF4V3F6ZjhUTGc5Vk5WT2lLVUU0ZCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zYW5jdHVtL2NzcmYtY29va2llIjtzOjU6InJvdXRlIjtzOjE5OiJzYW5jdHVtLmNzcmYtY29va2llIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1788070845),('C1WHJ7umG2UNzGhISfgXG6Tzqa5gMldQ1SQHrzaA',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSUdXaUhqblNFZXJwOVpDNnpwalNTUUxyTDBoSnVtODZVZ1R2WlR6ZSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zYW5jdHVtL2NzcmYtY29va2llIjtzOjU6InJvdXRlIjtzOjE5OiJzYW5jdHVtLmNzcmYtY29va2llIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1788023932),('ULtaKZNmqyBNsaFYQ3nN0TCAquiUQocjwfVCOsRU',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiU3MzbkJaOTJmYnB4blFRU3hBTGkzdGRYUzRtT2VVYVZkSmNZejFQVCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zYW5jdHVtL2NzcmYtY29va2llIjtzOjU6InJvdXRlIjtzOjE5OiJzYW5jdHVtLmNzcmYtY29va2llIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1788070972);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `key` varchar(191) NOT NULL,
  `value` longtext DEFAULT NULL,
  `group` varchar(191) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_settings_key_institute_id_unique` (`key`,`institute_id`),
  KEY `site_settings_institute_id_foreign` (`institute_id`),
  CONSTRAINT `site_settings_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(191) DEFAULT NULL,
  `first_name` varchar(191) DEFAULT NULL,
  `last_name` varchar(191) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(191) DEFAULT NULL,
  `school_name` varchar(191) DEFAULT NULL,
  `medium` varchar(191) DEFAULT NULL,
  `current_grade` varchar(191) DEFAULT NULL,
  `stream` varchar(191) DEFAULT NULL,
  `selected_subjects` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`selected_subjects`)),
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `students_user_id_foreign` (`user_id`),
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,4,'Karthik Kumar','Karthik','Kumar',NULL,'male',NULL,'english','Grade 5',NULL,'\"Maths,Science\"',NULL,'2026-08-29 09:00:19','2026-08-29 09:03:39'),(2,2,'Child Arun','Arun',NULL,NULL,NULL,NULL,NULL,'Grade 5',NULL,'\"Maths\"',NULL,'2026-08-29 09:01:36','2026-08-29 09:01:36'),(3,2,'Child Karthik','Karthik',NULL,NULL,NULL,NULL,NULL,'Grade 4',NULL,'\"Science\"',NULL,'2026-08-29 09:01:36','2026-08-29 09:01:36'),(4,4,'Child Arun','Arun',NULL,NULL,NULL,NULL,NULL,'Grade 5',NULL,'\"Maths\"',NULL,'2026-08-29 09:03:36','2026-08-29 09:03:36'),(5,4,'Child Karthik','Karthik',NULL,NULL,NULL,NULL,NULL,'Grade 4',NULL,'\"Science\"',NULL,'2026-08-29 09:03:36','2026-08-29 09:03:36'),(6,5,'Arun',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 10',NULL,'\"[\\\"Maths\\\"]\"',NULL,'2026-08-29 13:49:36','2026-08-29 13:49:36'),(7,5,'Karthik',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 11',NULL,'\"[\\\"Science\\\"]\"',NULL,'2026-08-29 13:49:36','2026-08-29 13:49:36'),(8,5,'Karthik Old',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 10',NULL,'\"[\\\"English\\\"]\"',NULL,'2026-08-29 13:49:37','2026-08-29 13:49:38'),(9,7,'Arun',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 10',NULL,'\"[\\\"Maths\\\"]\"',NULL,'2026-08-29 14:02:14','2026-08-29 14:02:14'),(10,7,'Karthik',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 11',NULL,'\"[\\\"Science\\\"]\"',NULL,'2026-08-29 14:02:14','2026-08-29 14:02:14'),(11,7,'Karthik Old',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 10',NULL,'\"[\\\"English\\\"]\"',NULL,'2026-08-29 14:02:15','2026-08-29 14:02:16'),(15,11,'Child One',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 6',NULL,'\"Science\"',NULL,'2026-08-29 16:08:57','2026-08-29 16:08:57'),(16,11,'Child Two',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 8',NULL,'\"Maths\"',NULL,'2026-08-29 16:08:57','2026-08-29 16:08:57'),(17,12,'Old Sibling',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 10',NULL,NULL,NULL,'2026-08-29 16:08:58','2026-08-29 16:08:58'),(18,13,'Other Child',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-29 16:08:58','2026-08-29 16:08:58'),(19,14,'Child One',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 6',NULL,'\"Science\"',NULL,'2026-08-29 16:10:14','2026-08-29 16:10:14'),(20,14,'Child Two',NULL,NULL,NULL,NULL,NULL,NULL,'Grade 8',NULL,'\"Maths\"',NULL,'2026-08-29 16:10:14','2026-08-29 16:10:14'),(21,19,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-29 17:19:04','2026-08-29 17:19:04');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `name_ta` varchar(100) DEFAULT NULL,
  `name_si` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(100) NOT NULL DEFAULT 'grade_6_to_11',
  `medium` enum('english','tamil','both') NOT NULL DEFAULT 'tamil',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_name_category_medium_unique` (`name`,`category`,`medium`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (3,'தமிழ்',NULL,NULL,500.00,'grade_1_to_5','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(4,'ஆங்கிலம்',NULL,NULL,550.00,'grade_1_to_5','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(5,'சூழற்றாடல்',NULL,NULL,500.00,'grade_1_to_5','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(6,'சமயம்',NULL,NULL,500.00,'grade_1_to_5','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(7,'சிங்களம்',NULL,NULL,500.00,'grade_1_to_5','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(8,'புலமைப்பரிசில் வகுப்புகள்',NULL,NULL,600.00,'grade_1_to_5','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(9,'தமிழ்',NULL,NULL,500.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(10,'ஆங்கிலம்',NULL,NULL,550.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(11,'கணிதம்',NULL,NULL,600.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(12,'வரலாறு',NULL,NULL,500.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(13,'சமயம்',NULL,NULL,500.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(14,'விஞ்ஞானம்',NULL,NULL,600.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(15,'குடியியல் கல்வி',NULL,NULL,500.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(16,'புவியியல்',NULL,NULL,500.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(17,'சிங்களம்',NULL,NULL,500.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(18,'ICT',NULL,NULL,700.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(19,'சுகாதாரம் உள்கல்வியும்',NULL,NULL,500.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(20,'வணிகக் கல்வி',NULL,NULL,500.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(21,'இலக்கியம் (தமிழ்)',NULL,NULL,500.00,'grade_6_to_11','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(22,'தமிழ்',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(23,'வரலாறு',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(24,'புவியியல்',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(25,'ICT',NULL,NULL,700.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(26,'அரசியல் விஞ்ஞானம்',NULL,NULL,600.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(27,'இந்து நாகரிகம்',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(28,'மனைப்பொருளியல்',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(29,'ஊடகக் கல்வி',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(30,'நடனம்',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(31,'நாடகம்',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(32,'சித்திரம்',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(33,'சங்கீதம்',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(34,'கிறிஸ்தவ நாகரிகம்',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(35,'அளவையியல்',NULL,NULL,500.00,'arts_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(36,'இணைந்த கணிதம்',NULL,NULL,700.00,'bio_maths_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(37,'உயிரியல்',NULL,NULL,700.00,'bio_maths_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(38,'பெளதிகவியல்',NULL,NULL,700.00,'bio_maths_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(39,'இரசாயனவியல்',NULL,NULL,700.00,'bio_maths_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50'),(40,'ICT',NULL,NULL,700.00,'bio_maths_stream','tamil','2026-08-29 16:34:50','2026-08-29 16:34:50');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `monthly_price` decimal(10,2) NOT NULL,
  `yearly_price` decimal(10,2) NOT NULL,
  `max_students` int(11) NOT NULL DEFAULT -1,
  `max_teachers` int(11) NOT NULL DEFAULT -1,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_plans_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_plans`
--

LOCK TABLES `subscription_plans` WRITE;
/*!40000 ALTER TABLE `subscription_plans` DISABLE KEYS */;
INSERT INTO `subscription_plans` VALUES (1,'Growth Plan','growth',2500.00,25000.00,500,25,'[\"Zoom Integration\",\"Grade Management\",\"WhatsApp Alerts\"]','2026-08-29 16:34:49','2026-08-29 16:34:49');
/*!40000 ALTER TABLE `subscription_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetables`
--

DROP TABLE IF EXISTS `timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `day_of_week` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `duration` int(11) NOT NULL DEFAULT 60,
  `grade` varchar(50) NOT NULL,
  `medium` enum('english','tamil','both') DEFAULT 'tamil',
  `subject_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `zoom_host_email` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timetables_subject_id_foreign` (`subject_id`),
  KEY `timetables_institute_id_foreign` (`institute_id`),
  KEY `timetables_day_of_week_grade_index` (`day_of_week`,`grade`),
  KEY `timetables_teacher_id_index` (`teacher_id`),
  CONSTRAINT `timetables_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timetables_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetables_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetables`
--

LOCK TABLES `timetables` WRITE;
/*!40000 ALTER TABLE `timetables` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `first_name` varchar(191) DEFAULT NULL,
  `last_name` varchar(191) DEFAULT NULL,
  `phone_number` varchar(191) DEFAULT NULL,
  `full_name` varchar(191) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `school_name` varchar(191) DEFAULT NULL,
  `medium` enum('english','tamil') DEFAULT NULL,
  `online_experience` tinyint(1) DEFAULT NULL,
  `device_used` varchar(191) DEFAULT NULL,
  `current_grade` varchar(191) DEFAULT NULL,
  `stream` varchar(191) DEFAULT NULL,
  `selected_subjects` text DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `google_id` varchar(191) DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `avatar` varchar(191) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `profile_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`profile_settings`)),
  `role` varchar(191) NOT NULL DEFAULT 'user',
  `teacher_unique_id` varchar(191) DEFAULT NULL,
  `teacher_class` varchar(191) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `admin_confirmed_at` timestamp NULL DEFAULT NULL,
  `registration_status` varchar(50) DEFAULT 'pending_payment',
  `deactivated_at` timestamp NULL DEFAULT NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_teacher_unique_id_unique` (`teacher_unique_id`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_institute_id_foreign` (`institute_id`),
  KEY `users_google_id_index` (`google_id`),
  CONSTRAINT `users_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'legacy_karthik','Karthik','Kumar','0771234567','Karthik Kumar',NULL,'male',NULL,'english',NULL,NULL,'Grade 5',NULL,'\"Maths,Science\"','merged_karthik@legacy.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$Q9BrfTl45hXAHGxsO8KKPOaBfhEdOwNjeaVSeWxwUhC/BeHGgvrO2',NULL,'2026-08-29 09:00:19','2026-08-29 16:34:49',NULL,'approved','2026-08-29 09:03:39',NULL),(2,1,'parent_test1',NULL,NULL,'0779998888','Test Parent',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'parent1@test.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$qrHmWAdg4MKljdBla.2Z5esPo0tYXlAtNv65qtftXHvPObjqc03xy',NULL,'2026-08-29 09:01:36','2026-08-29 16:34:49',NULL,'approved',NULL,NULL),(4,1,'parent_test2',NULL,NULL,'0779998888','Test Parent',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'parent2@test.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$RMleAeoNwwMwdm662FPMfO2UC.jGWvOLz/pgj.45tikX2ByTBTZTe',NULL,'2026-08-29 09:03:36','2026-08-29 16:34:49',NULL,'approved',NULL,NULL),(5,1,'Test Parent',NULL,NULL,'0712345676','Test Parent',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'parent1788011374@test.com',NULL,'test_parent_1788011374',NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$6wnuZ4UcLK1BiwvIXznP4uVwRxCeiPqMXIDiC/mODmnhNCSKhsc0C',NULL,'2026-08-29 13:49:36','2026-08-29 16:34:49',NULL,'pending_payment',NULL,NULL),(6,1,'Karthik Old',NULL,NULL,'0799999999','Karthik Old',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'\"[\\\"English\\\"]\"','karthik1788011376@old.com',NULL,'karthik_old_1788011376',NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$SvwQRPQWdoeFtj920gxIuuEm64AbP/mAytkce7EcMgDoTT6Ed30Ai',NULL,'2026-08-29 13:49:37','2026-08-29 16:34:49',NULL,'pending_payment',NULL,NULL),(7,1,'Test Parent',NULL,NULL,'0712345671','Test Parent',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'parent1788012133@test.com',NULL,'test_parent_1788012133',NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$Q.y5.qhiPaRyoAFHVQnQuOMWJ1Aos/j3wmH8jUMjTMtxSzh/MFMaa',NULL,'2026-08-29 14:02:14','2026-08-29 16:34:49',NULL,'pending_payment',NULL,NULL),(8,1,'Karthik Old',NULL,NULL,'0799999999','Karthik Old',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'\"[\\\"English\\\"]\"','karthik1788012134@old.com',NULL,'karthik_old_1788012134',NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$cpyHNwGsNMz7Q1XEiJceo.29golIu6kRlNQa/Do8nlSXe0eDOwEB.',NULL,'2026-08-29 14:02:15','2026-08-29 16:34:49',NULL,'pending_payment',NULL,NULL),(11,1,'testparent_1788019736@test.com',NULL,NULL,'0771234567','Test Parent',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'testparent_1788019736@test.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$EPdMNiH3wENAex685Mq5o.t5Hy9NsDn6EQWMSjWUIFWOiRkfz7xQi',NULL,'2026-08-29 16:08:56','2026-08-29 16:34:49',NULL,'pending_payment',NULL,NULL),(12,1,'oldsibling@test.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'oldsibling@test.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$O.QwYYziLhmx5yd2j9TIAeFoNRCnNIwVltA799R3bcGgvMsojo2Ta',NULL,'2026-08-29 16:08:58','2026-08-29 16:34:49',NULL,'pending_payment',NULL,NULL),(13,1,'other@test.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'other@test.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$S3QFr.bSdbpwMFH.X99roeC/3G570gcNO3FgMO1wvMI/fybSReW1m',NULL,'2026-08-29 16:08:58','2026-08-29 16:34:49',NULL,'pending_payment',NULL,NULL),(14,1,'testparent_1788019814@test.com',NULL,NULL,'0771234567','Test Parent',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'testparent_1788019814@test.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$sX3t6XdEb.SCdwjk/UPF/eHKgkLO4RF8lGNcYDHTWgPFhIF/X2/A6',NULL,'2026-08-29 16:10:14','2026-08-29 16:34:49',NULL,'pending_payment',NULL,NULL),(17,NULL,'Super Admin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'superadmin@lenova.lk',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'super_admin',NULL,NULL,'2026-08-29 16:34:50','$2y$12$9wJqXkLTsqR4aQ7TGjT64OaIOkWTpyErz3WeKgKu9zQKoJRVKHxNa',NULL,'2026-08-29 16:34:50','2026-08-29 16:34:50',NULL,'pending_payment',NULL,NULL),(18,1,'Admin Koventhan',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'koventhanventhan153@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'admin',NULL,NULL,'2026-08-29 16:34:50','$2y$12$CfPVxo5kfFNYAJd71MnXueBzFft9PPL1B/2e6H41R3CzqeqpYtU3C',NULL,'2026-08-29 16:34:50','2026-08-30 07:06:35',NULL,'pending_payment',NULL,NULL),(19,1,'testparent@example.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'testparent@example.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user',NULL,NULL,NULL,'$2y$12$kKqTVYAnJCCNtfdBZ15SJeGOzKdkPGnBOuCgSErBiKx5gRlE8NHuG',NULL,'2026-08-29 17:19:04','2026-08-29 17:19:04',NULL,'pending',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zoom_accounts`
--

DROP TABLE IF EXISTS `zoom_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zoom_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `account_id` varchar(191) DEFAULT NULL,
  `client_id` varchar(191) NOT NULL,
  `client_secret` varchar(191) NOT NULL,
  `max_concurrent` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `institute_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `zoom_accounts_institute_id_foreign` (`institute_id`),
  CONSTRAINT `zoom_accounts_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zoom_accounts`
--

LOCK TABLES `zoom_accounts` WRITE;
/*!40000 ALTER TABLE `zoom_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `zoom_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zoom_schedule_teacher`
--

DROP TABLE IF EXISTS `zoom_schedule_teacher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zoom_schedule_teacher` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `zoom_schedule_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zoom_schedule_teacher_zoom_schedule_id_user_id_unique` (`zoom_schedule_id`,`user_id`),
  KEY `zoom_schedule_teacher_user_id_foreign` (`user_id`),
  CONSTRAINT `zoom_schedule_teacher_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `zoom_schedule_teacher_zoom_schedule_id_foreign` FOREIGN KEY (`zoom_schedule_id`) REFERENCES `zoom_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zoom_schedule_teacher`
--

LOCK TABLES `zoom_schedule_teacher` WRITE;
/*!40000 ALTER TABLE `zoom_schedule_teacher` DISABLE KEYS */;
/*!40000 ALTER TABLE `zoom_schedule_teacher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zoom_schedules`
--

DROP TABLE IF EXISTS `zoom_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zoom_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `institute_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `zoom_link` text NOT NULL,
  `meeting_id` varchar(191) DEFAULT NULL,
  `start_url` text DEFAULT NULL,
  `join_url` text DEFAULT NULL,
  `password` varchar(191) DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 60,
  `reminded_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` datetime NOT NULL,
  `subject` varchar(191) DEFAULT NULL,
  `grade` varchar(191) DEFAULT NULL,
  `medium` enum('english','tamil','both') DEFAULT 'tamil',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `zoom_account_id` bigint(20) unsigned DEFAULT NULL,
  `timetable_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `zoom_schedules_created_by_foreign` (`created_by`),
  KEY `zoom_schedules_scheduled_at_index` (`scheduled_at`),
  KEY `zoom_schedules_institute_id_foreign` (`institute_id`),
  KEY `zoom_schedules_zoom_account_id_foreign` (`zoom_account_id`),
  KEY `zoom_schedules_timetable_id_index` (`timetable_id`),
  CONSTRAINT `zoom_schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `zoom_schedules_institute_id_foreign` FOREIGN KEY (`institute_id`) REFERENCES `institutes` (`id`),
  CONSTRAINT `zoom_schedules_zoom_account_id_foreign` FOREIGN KEY (`zoom_account_id`) REFERENCES `zoom_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zoom_schedules`
--

LOCK TABLES `zoom_schedules` WRITE;
/*!40000 ALTER TABLE `zoom_schedules` DISABLE KEYS */;
INSERT INTO `zoom_schedules` VALUES (1,NULL,'Maths Revision Class','https://zoom.us/j/123456789',NULL,NULL,NULL,NULL,60,'2026-08-29 09:03:36','2026-08-29 14:43:36','Maths','Grade 5','tamil',NULL,'2026-08-29 09:03:36','2026-08-29 09:03:36',NULL,NULL);
/*!40000 ALTER TABLE `zoom_schedules` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-30 14:55:47
