-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: testdb
-- ------------------------------------------------------
-- Server version	8.0.43-0ubuntu0.24.04.2

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
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `student_id` int NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_cookies`
--

DROP TABLE IF EXISTS `user_cookies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_cookies` (
  `session_id` varchar(64) NOT NULL,
  `username` varchar(255) NOT NULL,
  `auth_token` varchar(64) NOT NULL,
  `creation_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expiration_time` timestamp NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_cookies`
--

LOCK TABLES `user_cookies` WRITE;
/*!40000 ALTER TABLE `user_cookies` DISABLE KEYS */;
INSERT INTO `user_cookies` VALUES ('','007','66937a3e64515c4410394366bc9935b0e0a06c0985f3ee690fb74d4f850d6fff','2025-09-29 00:14:13','2025-10-29 04:14:13','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('079c348f6adfbeab9c22f9a74a9291a65fcb85a1c903f93c0efb288d66efd796','007','5864acb83228a3abd6ee66746438d93a14e758f07c2583e392a373f764016530','2025-09-29 01:04:38','2025-10-29 05:04:38','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('08daf1f2c57941255d9105d19b918bcf387a07e98794abc52a4c5a8cc025773d','007','bb5d768e6798db1ac2b8e713022c122c0b0a247cb3ab84bf603630b919ac08cc','2025-09-29 01:05:30','2025-10-29 05:05:30','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('317fe21d4e278432cce31fafb7d69858c5acc3e7b7353da58695257d43b6ad70','007','d95c7e87fdb3e9027374e9f09fa5bb3924e3fae950975baf08bc377aaba623ad','2025-09-29 00:57:49','2025-10-29 04:57:49','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('8cf71aee7e8d04ef840862774e96163bc8c9ca8f999dc1c742671f0300708fcf','007','7b67bbc03a4ae77e2931006ece8b580f1dca2a828cdd69496c87761b352cbc92','2025-09-29 00:30:27','2025-10-29 04:30:27','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('9356297d7f8b31cea242719ec033a6acc8b65157cfd49eca8415e1b486ebfdf0','007','983d00e3bf010df8ee95db0945395bc2be2873571124d209cfce7da208b360d9','2025-09-29 00:59:04','2025-10-29 04:59:04','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('94c6ab0a2eff90885df054aa6447e308b723d74b4c133714506a85d991708075','007','2a0bd9180f3efa4142cbddc7904d16ab52636b7b7e11c3dce0c4f02a2868e674','2025-09-29 01:04:43','2025-10-29 05:04:43','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('a9b63a3567a4cf948debfa92ef4f20f09c12dd1e824c4e6ecb6597614fb72bc8','007','6a202506119e0dccc39d470cb19982d55de47682beaf23b189bbc866b6552429','2025-09-29 00:58:51','2025-10-29 04:58:51','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('f8268402a76bcae9b744d060e034622392ae1d6a0a0f8a7ce2a6db6680d0060e','007','234603db6015ca7238546212d439e5aaf181673a8b686952b2a5a6905d2614c6','2025-09-29 00:57:44','2025-10-29 04:57:44','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0');
/*!40000 ALTER TABLE `user_cookies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `userId` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`userId`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'1234','hello@sample.com','$2y$12$dtdrM/325JCf62p7Bs0X9emjcT9NWW2HkdoKytmlZS8p9oldE4JbS'),(2,'007','lewis@sample.com','$2y$12$.LTacS.sIxah/RVKMl1PQe1tqiBiVoAaS7sy17BuVOdIBOPQ7950.');
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

-- Dump completed on 2025-09-28 21:26:35
