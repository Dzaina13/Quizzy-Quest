-- MySQL dump 10.13  Distrib 8.0.42, for Linux (x86_64)
--
-- Host: localhost    Database: quizuas
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `decision_maker_questions`
--

DROP TABLE IF EXISTS `decision_maker_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `decision_maker_questions` (
  `question_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `question_text` text NOT NULL,
  `correct_answer` text NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`question_id`),
  KEY `session_id` (`session_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `decision_maker_questions_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `decision_maker_sessions` (`session_id`) ON DELETE CASCADE,
  CONSTRAINT `decision_maker_questions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_maker_questions`
--

LOCK TABLES `decision_maker_questions` WRITE;
/*!40000 ALTER TABLE `decision_maker_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_maker_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `decision_maker_sessions`
--

DROP TABLE IF EXISTS `decision_maker_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `decision_maker_sessions` (
  `session_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_name` varchar(100) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `decision_maker_sessions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_maker_sessions`
--

LOCK TABLES `decision_maker_sessions` WRITE;
/*!40000 ALTER TABLE `decision_maker_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `decision_maker_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `participants`
--

DROP TABLE IF EXISTS `participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `participants` (
  `participant_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `is_guest` tinyint(1) DEFAULT '0',
  `guest_name` varchar(100) DEFAULT NULL,
  `join_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`participant_id`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `participants_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`session_id`) ON DELETE CASCADE,
  CONSTRAINT `participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `participants`
--

LOCK TABLES `participants` WRITE;
/*!40000 ALTER TABLE `participants` DISABLE KEYS */;
INSERT INTO `participants` VALUES (1,1,2,0,NULL,'2025-05-30 03:48:51'),(2,2,3,0,NULL,'2025-05-30 03:48:51'),(3,3,NULL,1,'Guest Rofi','2025-05-30 03:48:51');
/*!40000 ALTER TABLE `participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `points`
--

DROP TABLE IF EXISTS `points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `points` (
  `point_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` bigint unsigned NOT NULL,
  `total_correct` int DEFAULT '0',
  `total_questions` int DEFAULT NULL,
  `score` float DEFAULT NULL,
  `evaluated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`point_id`),
  KEY `participant_id` (`participant_id`),
  CONSTRAINT `points_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`participant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `points`
--

LOCK TABLES `points` WRITE;
/*!40000 ALTER TABLE `points` DISABLE KEYS */;
/*!40000 ALTER TABLE `points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `question_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quiz_id` bigint unsigned NOT NULL,
  `question_text` text NOT NULL,
  `option_a` text,
  `option_b` text,
  `option_c` text,
  `option_d` text,
  `correct_answer` char(1) DEFAULT NULL,
  `explanation` text,
  `is_decision_critical` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`question_id`),
  KEY `quiz_id` (`quiz_id`),
  CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_sessions`
--

DROP TABLE IF EXISTS `quiz_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_sessions` (
  `session_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quiz_id` bigint unsigned NOT NULL,
  `session_name` varchar(100) NOT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `quiz_sessions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE,
  CONSTRAINT `quiz_sessions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_sessions`
--

LOCK TABLES `quiz_sessions` WRITE;
/*!40000 ALTER TABLE `quiz_sessions` DISABLE KEYS */;
INSERT INTO `quiz_sessions` VALUES (1,1,'Sesi Normal Pagi','2025-05-30 03:48:51',NULL,1),(2,2,'Sesi Keputusan Siang','2025-05-30 03:48:51',NULL,1),(3,3,'Sesi ROF Malam','2025-05-30 03:48:51',NULL,1);
/*!40000 ALTER TABLE `quiz_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quizzes` (
  `quiz_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text,
  `quiz_type` enum('normal','decision_maker','rof') NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`quiz_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quizzes`
--

LOCK TABLES `quizzes` WRITE;
/*!40000 ALTER TABLE `quizzes` DISABLE KEYS */;
INSERT INTO `quizzes` VALUES (1,'Quiz Normal 1','Tes pengetahuan umum.','normal',1,'2025-05-30 03:48:51'),(2,'Quiz Decision Maker 1','Simulasi pengambilan keputusan.','decision_maker',1,'2025-05-30 03:48:51'),(3,'Quiz ROF 1','Right or False quiz.','rof',1,'2025-05-30 03:48:51');
/*!40000 ALTER TABLE `quizzes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rof_answers`
--

DROP TABLE IF EXISTS `rof_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rof_answers` (
  `rof_answer_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rof_participant_id` bigint unsigned NOT NULL,
  `rof_question_id` bigint unsigned NOT NULL,
  `answer` enum('T','F') DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rof_answer_id`),
  KEY `rof_participant_id` (`rof_participant_id`),
  KEY `rof_question_id` (`rof_question_id`),
  CONSTRAINT `rof_answers_ibfk_1` FOREIGN KEY (`rof_participant_id`) REFERENCES `rof_participants` (`rof_participant_id`) ON DELETE CASCADE,
  CONSTRAINT `rof_answers_ibfk_2` FOREIGN KEY (`rof_question_id`) REFERENCES `rof_questions` (`rof_question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rof_answers`
--

LOCK TABLES `rof_answers` WRITE;
/*!40000 ALTER TABLE `rof_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `rof_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rof_participants`
--

DROP TABLE IF EXISTS `rof_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rof_participants` (
  `rof_participant_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rof_session_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `is_guest` tinyint(1) DEFAULT '0',
  `guest_name` varchar(100) DEFAULT NULL,
  `join_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rof_participant_id`),
  KEY `rof_session_id` (`rof_session_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `rof_participants_ibfk_1` FOREIGN KEY (`rof_session_id`) REFERENCES `rof_sessions` (`rof_session_id`) ON DELETE CASCADE,
  CONSTRAINT `rof_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rof_participants`
--

LOCK TABLES `rof_participants` WRITE;
/*!40000 ALTER TABLE `rof_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `rof_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rof_questions`
--

DROP TABLE IF EXISTS `rof_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rof_questions` (
  `rof_question_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rof_session_id` bigint unsigned NOT NULL,
  `question_text` text NOT NULL,
  `correct_answer` enum('T','F') NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`rof_question_id`),
  KEY `rof_session_id` (`rof_session_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `rof_questions_ibfk_1` FOREIGN KEY (`rof_session_id`) REFERENCES `rof_sessions` (`rof_session_id`) ON DELETE CASCADE,
  CONSTRAINT `rof_questions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rof_questions`
--

LOCK TABLES `rof_questions` WRITE;
/*!40000 ALTER TABLE `rof_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `rof_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rof_sessions`
--

DROP TABLE IF EXISTS `rof_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rof_sessions` (
  `rof_session_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_name` varchar(100) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rof_session_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `rof_sessions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rof_sessions`
--

LOCK TABLES `rof_sessions` WRITE;
/*!40000 ALTER TABLE `rof_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `rof_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_answers`
--

DROP TABLE IF EXISTS `user_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_answers` (
  `answer_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `participant_id` bigint unsigned NOT NULL,
  `question_id` bigint unsigned NOT NULL,
  `chosen_answer` char(1) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`answer_id`),
  KEY `participant_id` (`participant_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `user_answers_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`participant_id`) ON DELETE CASCADE,
  CONSTRAINT `user_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_answers`
--

LOCK TABLES `user_answers` WRITE;
/*!40000 ALTER TABLE `user_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` text NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','participant') DEFAULT 'participant',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin1','hashed_admin_password','admin1@example.com','admin','2025-05-30 03:48:51'),(2,'user1','hashed_user1_password','user1@example.com','participant','2025-05-30 03:48:51'),(3,'user2','hashed_user2_password','user2@example.com','participant','2025-05-30 03:48:51'),(4,'dzakiputra93','$2y$10$AiVuSjJcucfuDW04NLYsq./XssMxEQF5rmxeHtxQOMXjt2OF0b8gC','dzakiputra93@gmail.com','participant','2025-05-30 05:08:22');
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

-- Dump completed on 2025-05-30 12:12:42
