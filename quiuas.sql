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
  `quiz_id` bigint unsigned DEFAULT NULL,
  `question_text` text NOT NULL,
  `correct_answer` text NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`question_id`),
  KEY `created_by` (`created_by`),
  KEY `fk_dm_questions_quiz` (`quiz_id`),
  CONSTRAINT `decision_maker_questions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_dm_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decision_maker_questions`
--

LOCK TABLES `decision_maker_questions` WRITE;
/*!40000 ALTER TABLE `decision_maker_questions` DISABLE KEYS */;
INSERT INTO `decision_maker_questions` VALUES (6,54,'kuku bima energi ?','roso',6);
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
-- Table structure for table `live_session_state`
--

DROP TABLE IF EXISTS `live_session_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_session_state` (
  `session_id` bigint unsigned NOT NULL,
  `current_question_number` int DEFAULT '0',
  `total_questions` int DEFAULT '0',
  PRIMARY KEY (`session_id`),
  CONSTRAINT `live_session_state_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`session_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_session_state`
--

LOCK TABLES `live_session_state` WRITE;
/*!40000 ALTER TABLE `live_session_state` DISABLE KEYS */;
INSERT INTO `live_session_state` VALUES (4,0,1),(5,0,1),(6,0,1),(7,0,1),(8,0,2),(9,0,1),(10,0,1);
/*!40000 ALTER TABLE `live_session_state` ENABLE KEYS */;
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
  `is_host` tinyint(1) DEFAULT '0',
  `connection_status` enum('connected','disconnected') DEFAULT 'connected',
  `join_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`participant_id`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `participants_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `quiz_sessions` (`session_id`) ON DELETE CASCADE,
  CONSTRAINT `participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `participants`
--

LOCK TABLES `participants` WRITE;
/*!40000 ALTER TABLE `participants` DISABLE KEYS */;
INSERT INTO `participants` VALUES (1,1,2,0,NULL,0,'connected','2025-05-30 03:48:51'),(2,2,3,0,NULL,0,'connected','2025-05-30 03:48:51'),(3,3,NULL,1,'Guest Rofi',0,'connected','2025-05-30 03:48:51'),(4,1,6,0,NULL,0,'connected','2025-06-15 18:41:53'),(5,3,6,0,NULL,0,'connected','2025-06-15 18:42:35'),(6,4,8,0,NULL,1,'connected','2025-06-16 00:01:47'),(7,4,8,0,NULL,0,'connected','2025-06-16 00:02:05'),(8,5,8,0,NULL,1,'connected','2025-06-16 00:04:41'),(9,4,9,0,'damar',0,'connected','2025-06-16 00:45:21'),(10,6,10,0,NULL,1,'connected','2025-06-16 04:13:14'),(11,7,6,0,NULL,1,'connected','2025-06-17 15:14:55'),(12,8,6,0,NULL,1,'connected','2025-06-17 15:49:16'),(13,9,6,0,NULL,1,'connected','2025-06-17 16:02:38'),(14,10,6,0,NULL,1,'connected','2025-06-17 16:35:51');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
INSERT INTO `questions` VALUES (1,5,'lullabu','1','2','3','1','A','',0),(2,11,'kelapa itu bulat?','Benar','Salah','','','T','',0),(3,42,'jhvhv','bukub','gvggv','gvjgv','gjvjgv','C','no expla',0);
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
  `room_code` varchar(6) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `is_live_room` tinyint(1) DEFAULT '0',
  `room_status` enum('waiting','active','ended','cancelled') NOT NULL DEFAULT 'waiting',
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `room_code` (`room_code`),
  KEY `quiz_id` (`quiz_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `quiz_sessions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE,
  CONSTRAINT `quiz_sessions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_sessions`
--

LOCK TABLES `quiz_sessions` WRITE;
/*!40000 ALTER TABLE `quiz_sessions` DISABLE KEYS */;
INSERT INTO `quiz_sessions` VALUES (1,1,'Sesi Normal Pagi',NULL,'2025-05-30 03:48:51',NULL,0,'waiting',1),(2,2,'Sesi Keputusan Siang',NULL,'2025-05-30 03:48:51',NULL,0,'waiting',1),(3,3,'Sesi ROF Malam',NULL,'2025-05-30 03:48:51',NULL,0,'waiting',1),(4,11,'Live Quiz','81D6E4','2025-06-16 03:03:14',NULL,1,'active',8),(5,11,'Live Quiz','3D9319',NULL,NULL,1,'waiting',8),(6,5,'Live Quiz','F110A8',NULL,NULL,1,'waiting',10),(7,5,'Live Quiz','7668CA',NULL,NULL,1,'waiting',6),(8,33,'Live Quiz','DEAAF0',NULL,NULL,1,'waiting',6),(9,42,'Live Quiz','2EBA6B',NULL,NULL,1,'waiting',6),(10,54,'Live Quiz','8D0DEB',NULL,NULL,1,'waiting',6);
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quizzes`
--

LOCK TABLES `quizzes` WRITE;
/*!40000 ALTER TABLE `quizzes` DISABLE KEYS */;
INSERT INTO `quizzes` VALUES (1,'Quiz Normal 1','Tes pengetahuan umum.','normal',1,'2025-05-30 03:48:51'),(2,'Quiz Decision Maker 1','Simulasi pengambilan keputusan.','decision_maker',1,'2025-05-30 03:48:51'),(3,'Quiz ROF 1','Right or False quiz.','rof',1,'2025-05-30 03:48:51'),(4,'123','123','rof',6,'2025-06-15 16:05:09'),(5,'12311','123','normal',6,'2025-06-15 16:16:11'),(6,'ken','ken','rof',6,'2025-06-15 16:17:28'),(7,'ken','ken','rof',6,'2025-06-15 16:23:44'),(8,'ken','ken','rof',6,'2025-06-15 16:24:08'),(9,'ken','ken','rof',6,'2025-06-15 16:27:07'),(10,'ken','ken','rof',6,'2025-06-15 16:27:28'),(11,'lemari','kelapamuda','rof',6,'2025-06-15 16:30:22'),(12,'PEMWEB UAS','apkaah bisa tamat ?','rof',6,'2025-06-15 16:36:56'),(13,'PEMWEB UAS','apkaah bisa tamat ?','rof',6,'2025-06-15 16:39:26'),(14,'lidar','keusais','rof',6,'2025-06-15 16:40:47'),(15,'lidar','keusais','rof',6,'2025-06-15 16:43:41'),(16,'ledakan','duar','rof',6,'2025-06-15 16:44:50'),(17,'reno','123','rof',6,'2025-06-15 16:53:29'),(18,'Pemweb 1234','sekian','rof',6,'2025-06-15 19:11:25'),(19,'ken','123','rof',10,'2025-06-16 04:11:21'),(20,'sadasd','asdasd','normal',10,'2025-06-16 04:12:53'),(21,'pemweb','sadsda','decision_maker',10,'2025-06-16 04:16:45'),(22,'pemweb','sadsda','decision_maker',10,'2025-06-16 04:23:15'),(23,'pemweb','1234','rof',11,'2025-06-16 04:49:17'),(24,'pemweb','1234','rof',11,'2025-06-16 04:49:38'),(25,'Quiz hewani','ini isinya soal hewan hewan','rof',8,'2025-06-16 05:29:30'),(26,'Quiz hewani','ini isinya soal hewan hewan','rof',8,'2025-06-16 05:44:17'),(27,'Hewan','12345678','rof',8,'2025-06-16 06:04:28'),(28,'Hewan','12345678','rof',8,'2025-06-16 06:08:55'),(29,'Selamat','selamat','rof',6,'2025-06-16 06:22:55'),(30,'123','123','rof',6,'2025-06-17 13:40:47'),(31,'123','123','rof',6,'2025-06-17 13:42:08'),(32,'lambe','123','rof',6,'2025-06-17 14:27:45'),(33,'lebay sekalii','bkhbhbhb','rof',6,'2025-06-17 15:22:39'),(34,'lama lama','lelah juga','normal',6,'2025-06-17 15:51:25'),(35,'lama lama','lelah juga','normal',6,'2025-06-17 15:53:58'),(36,'rendi','123','normal',6,'2025-06-17 15:54:32'),(37,'rendi','123','normal',6,'2025-06-17 15:55:33'),(38,'rendi','123','normal',6,'2025-06-17 15:56:07'),(39,'rendi','123','normal',6,'2025-06-17 15:57:39'),(40,'rendi','123','normal',6,'2025-06-17 15:58:24'),(41,'rendi','123','normal',6,'2025-06-17 15:58:41'),(42,'rendi','123','normal',6,'2025-06-17 16:01:21'),(43,'weweww','wuwuwuwu','decision_maker',6,'2025-06-17 16:16:59'),(44,'weweww','wuwuwuwu','decision_maker',6,'2025-06-17 16:17:59'),(45,'weweww','wuwuwuwu','decision_maker',6,'2025-06-17 16:19:43'),(46,'weweww','wuwuwuwu','decision_maker',6,'2025-06-17 16:20:09'),(47,'weweww','wuwuwuwu','decision_maker',6,'2025-06-17 16:20:30'),(48,'weweww','wuwuwuwu','decision_maker',6,'2025-06-17 16:23:21'),(49,'weweww','wuwuwuwu','decision_maker',6,'2025-06-17 16:24:12'),(50,'weweww','wuwuwuwu','decision_maker',6,'2025-06-17 16:25:30'),(51,'weweww','wuwuwuwu','decision_maker',6,'2025-06-17 16:25:47'),(52,'weweww','wuwuwuwu','decision_maker',6,'2025-06-17 16:27:55'),(53,'ronin','123','decision_maker',6,'2025-06-17 16:28:32'),(54,'ronin','123','decision_maker',6,'2025-06-17 16:35:18');
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
  `rof_quiz_id` bigint unsigned NOT NULL,
  `question_text` text NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `correct_answer` enum('true','false') NOT NULL,
  PRIMARY KEY (`rof_question_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `rof_questions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rof_questions`
--

LOCK TABLES `rof_questions` WRITE;
/*!40000 ALTER TABLE `rof_questions` DISABLE KEYS */;
INSERT INTO `rof_questions` VALUES (3,17,'kacaww',6,'true'),(4,18,'bla blabla',6,'true'),(5,18,'bla blabla',6,'false'),(6,18,'ufugu gafafa',6,'true'),(7,31,'fwcccw',6,'true'),(8,32,'niga',6,'true'),(9,33,'h hgg',6,'true'),(10,33,'hctcytvbh',6,'false');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rof_sessions`
--

LOCK TABLES `rof_sessions` WRITE;
/*!40000 ALTER TABLE `rof_sessions` DISABLE KEYS */;
INSERT INTO `rof_sessions` VALUES (1,'ROF Session for Quiz ID: 26',8,NULL,NULL,'2025-06-16 05:44:17');
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin1','hashed_admin_password','admin1@example.com','admin','2025-05-30 03:48:51'),(2,'user1','hashed_user1_password','user1@example.com','participant','2025-05-30 03:48:51'),(3,'user2','hashed_user2_password','user2@example.com','participant','2025-05-30 03:48:51'),(4,'dzakiputra93','$2y$10$AiVuSjJcucfuDW04NLYsq./XssMxEQF5rmxeHtxQOMXjt2OF0b8gC','dzakiputra93@gmail.com','participant','2025-05-30 05:08:22'),(5,'assoy','$2y$10$8ROj//wqHB1aqdPtJQxH7uRPDTGDBpA6dWhuBE2ogjqSEcxUDCJ9q','assoy@gmail.com','participant','2025-06-15 10:16:05'),(6,'dzakyhebat','$2y$10$l8REM89i11skEucCC1UGTO0WG90u2J/pcRHnPMW2CzCZ/xQoVNaQm','123@ga.op','participant','2025-06-15 10:52:10'),(7,'roronoa','$2y$10$o7KrJUDbMqLeYmJW1y.buee5Yj4zOEEB4UrH2gPt1lHB5sWNMo8ku','dza@email.co','participant','2025-06-15 20:37:01'),(8,'new','$2y$10$KQtjvaFxR2T2hY5TZY/HR.2slrRQlC7Tpmq9M9XUJOFF/4vYz9NBS','new@email.com','participant','2025-06-15 23:25:07'),(9,'damar','$2y$10$FSIkTm6jcJEMRmviBze0T.y3nxedzFTfVoloYNzcZH86elHPalSNq','re@email.com','participant','2025-06-16 00:41:18'),(10,'layar','$2y$10$G/y5S2EEKcHHXAIb1npiTe1/fTh4peojpbgM2Q9R94BP61ts8.5kK','layar@123.co','participant','2025-06-16 04:07:44'),(11,'dzaky','$2y$10$1osEaMNffnWduWKhR8nts.K7/.UJPNARGtwtoJkrKf8VFxpqWBc0C','v@email.co','participant','2025-06-16 04:46:04');
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

-- Dump completed on 2025-06-18  6:53:55
