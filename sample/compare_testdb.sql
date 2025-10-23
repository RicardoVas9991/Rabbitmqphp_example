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
INSERT INTO `user_cookies` VALUES ('0153e4edf4424adbe985ce5e564613f69fac9f7a31d4836535b40c4ca00a740f','1234','83acf48d4c6ca0595c1e9d93ad9a5f3ad4b2bea2a9a47a9fd4bba91f4a977973','2025-10-01 15:35:02','2025-10-31 19:35:02','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('2793a3c3283613df5a413dd52f08461d2a2971321dc02652a057d7513d473c4e','testuser','ba0b22a6956b7aef533460716afd328e3ff8c2434f729c56481706e74d273170','2025-09-29 23:04:47','2025-10-30 03:04:47','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('2dab8bce244a08681262f053a3aaf9d8ee2907254ac9677d468051327a5b4a9f','007','be933f60a48205cf9d34d8dbb20c2b8be5387a8b3b41fb3a62861a0ae0168083','2025-09-29 19:53:49','2025-10-29 23:53:49','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('3256c032fc07f29c56d6a2b99dcf7b4ed76e2563b5e8bc016a34cdb339ae668d','testuser','4bf051a3369caf8f6a00f2da61c871adc1844aae1ce752ed80bd64a30e24e885','2025-09-29 23:00:49','2025-10-30 03:00:49','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('428651797a77cf466768f906fcbabcf8cc05324b8826d8d09c048f368ffbd722','007','13a45154819a7ebe103eb0fe956f4767625fcb5d283b407168127c706d2574dc','2025-09-29 19:19:12','2025-10-29 23:19:12','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('5733b14482a087eedf6c6c09de3642fcc7423d77a6b7db79077612e05ae11734','testuser','916665ddf572548af62d992b3dffc4d108d8b4459ddbd642c2b2b80640a75498','2025-10-01 15:37:31','2025-10-31 19:37:31','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('71e2478b7bb2f4e312bc54b5bd0ba1333feac59df5c9be2c1949fc3aba38d00e','testuser','9eb8c6e5369b8a1dab460b6dd28995c207a9cd347c956752c6d36b5b465bc0f5','2025-09-29 23:00:46','2025-10-30 03:00:46','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('801608fa92707ff0703ae6f88e247777f75fca1b573f2a5095df9719910f86d6','007','b2cd5cf9a742c222f90bc4a13d26beb0eccc5c084183394e468eec6b326eaf76','2025-09-29 22:59:42','2025-10-30 02:59:42','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('c0b3def71829cc3443513d4217a54b4e77b9cd59c3e7d4a66f7c3bbb4c292c9c','testuser','5f256e1c0b961918081a1e6506a9a030db5d0ca00e2dff91007a9bf6276d2e44','2025-10-01 15:37:34','2025-10-31 19:37:34','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('c7b8a8568a807bd3809ed7659b37674345bf78a2fe9e1abeb373bd6070b17133','testuser','6596557d4e8f13daa123cce64d9a304529db064fc313bdf5ff9dd0b0b8e3ca82','2025-09-29 23:02:34','2025-10-30 03:02:34','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('cc2614638e1eafd1ea092e8c5812e8a1b06eb682fd4cc52bf125ab8638334474','testuser','1fe116172a5840f3a58657901a02c352cf44c1678181c272ce2a4fef951689b9','2025-09-29 23:24:34','2025-10-30 03:24:34','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0'),('cd6989ca43af009754e72fb73efc1c3fefa3122150f92693f449617ab0b5ab87','pewdiepie1234','d9b2c155631ac1d6d6ff8c820f333e2edea01d1816fa7f9297fc128ce2e1d099','2025-09-30 23:41:50','2025-10-31 03:41:50','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'1234','hello@sample.com','$2y$12$dtdrM/325JCf62p7Bs0X9emjcT9NWW2HkdoKytmlZS8p9oldE4JbS'),(2,'007','lewis@sample.com','$2y$12$.LTacS.sIxah/RVKMl1PQe1tqiBiVoAaS7sy17BuVOdIBOPQ7950.'),(3,'p123','p123@sample.com','$2y$12$QGrFTHUV7wv9g7Zrhga0duwVIVAYD54LZ01H4wPj7u4KquZE3NvRS'),(4,'p1234','p1234@sample.com','$2y$12$ljsgoQkykOuTCyIM6ecw6ugH4OfLb9ZH8egXxFuO4LW/MOapvAEKq'),(5,'testuser','testuser@sample.com','$2y$12$qJPGYw6xREC9UqLfflXAYuRiV9tahXaMA3wgYLrMEKS8HkBhKzmxe'),(6,'pewdiepie1234','pewds@gmail.com','$2y$12$YaCaN7V1.EUu62EfYvh0FumzjQewHxMGJvHCrQwuPHdTcLPxelb1K'),(7,'lolcow','lolcow@sample.com','$2y$12$qOHHRh6xV5ne9ysfZBgoZOpCM2uGzYNQ0wPVt3MIdin.0QXwD87e2'),(8,'mary','mary@sample.com','$2y$12$GqVjfMVJ.kNyGrwAWJzFuOMCojSjyFH.mDK2R4pOPllWDTbhN6UkO'),(9,'abc','abc@gmail.com','$2y$12$eEJWP5mjgsrWHKTM3bZpQeUAvJRteMTX9oClGdDsdVhygKYUAQSyu'),(10,'5GUYS','5GUYS@sample.com','$2y$12$AVHSlMlt1zCj5Wybhtysc.fZ3oYSJH04c1r7eooF1Dt9p.hy9ABTG'),(11,'nathan','nathan@sample.com','$2y$12$r4Hoe1HquIctkaADm2QU.uUckiSVBTwhVcnqtaihiestGhspbuUZK'),(12,'applepie','nam@njit.edu','$2y$12$opR/O/LI9SR7hz6r9YLljesx/0k.oYyqYC21pOPudw25sFuGO8/1a'),(13,'wendys','mytest@sample.com','$2y$12$qMJMH9ZLoi1TLiEquV.OCeks3psrHHtKyvERW8IXTu4HHdXqOmyK6');
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

-- Dump completed on 2025-10-03 21:38:09
