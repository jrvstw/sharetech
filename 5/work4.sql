-- MySQL dump 10.13  Distrib 5.7.25, for Linux (x86_64)
--
-- Host: localhost    Database: work4
-- ------------------------------------------------------
-- Server version	5.7.25-0ubuntu0.18.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `books` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `isbn` char(13) NOT NULL,
  `publisher` varchar(30) NOT NULL,
  `name` varchar(60) NOT NULL,
  `author` varchar(20) NOT NULL,
  `price` int(8) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (1,'957-442-243-7','旗標','Firewall Management','William',560,'2009-07-02'),(2,'957-442-217-8','旗標','PHP libraries\\','Cannon Wang',580,'2004-06-13'),(8,'111-222-333-4','旗標','打符號，#$%^&*','打數字1234',712,'2010-09-24'),(9,'111-222-333-4','b\'c\"c','\"hel\\lo\"','Jarvis\"',501,'2019-03-21'),(13,'111-222-333-4','中文','中\\文名','中文,',500,'2019-03-05'),(16,'111-222-333-4','~`!@#$%','^&*()|\'\";:,<.>/?-=','Jarvis',500,'2019-03-06'),(17,'111-222-333-4','flag','&*\\\\\\e,)|\\','J,ar,vis',500,'2019-03-12'),(18,'111-222-333-4','flag','Jarvis','Jarvis',500,'2019-03-19'),(19,'111-222-333-4','keystore','C--','Jarvis',1,'2019-03-26'),(20,'111-222-333-4','keystore','C--','Jarvis',2,'2019-03-26'),(21,'111-222-333-4','keystore','C--','Jarvis',3,'2019-03-26'),(22,'111-222-333-4','keystore','C--','Jarvis',4,'2019-03-26'),(23,'111-222-333-4','keystore','C--','Jarvis',5,'2019-03-26'),(24,'111-222-333-4','keystore','C--','Jarvis',6,'2019-03-26'),(25,'111-222-333-4','keystore','C--','Jarvis',7,'2019-03-26'),(26,'111-222-333-4','keystore','C--','Jarvis',8,'2019-03-26'),(27,'111-222-333-4','keystore','C--','Jarvis',9,'2019-03-26'),(28,'111-222-333-4','keystore','C--','Jarvis',10,'2019-03-26'),(29,'111-222-333-4','keystore','C--','Jarvis',11,'2019-03-26'),(30,'111-222-333-4','keystore','C--','Jarvis',12,'2019-03-26'),(31,'111-222-333-4','keystore','C--','Jarvis',13,'2019-03-26'),(32,'111-222-333-4','keystore','C--','Jarvis',14,'2019-03-26'),(33,'111-222-333-4','keystore','C--','Jarvis',15,'2019-03-26'),(34,'111-222-333-4','keystore','C--','Jarvis',16,'2019-03-26'),(35,'111-222-333-4','keystore','C--','Jarvis',17,'2019-03-26'),(36,'111-222-333-4','keystore','C--','Jarvis',18,'2019-03-26'),(37,'111-222-333-4','keystore','C--','Jarvis',19,'2019-03-26'),(38,'111-222-333-4','keystore','C--','Jarvis',20,'2019-03-26'),(39,'111-222-333-4','keystore','C--','Jarvis',21,'2019-03-26'),(40,'111-222-333-4','keystore','C--','Jarvis',22,'2019-03-26'),(41,'111-222-333-4','keystore','C--','Jarvis',23,'2019-03-26'),(42,'111-222-333-4','keystore','C--','Jarvis',24,'2019-03-26'),(43,'111-222-333-4','keystore','C--','Jarvis',25,'2019-03-26'),(44,'111-222-333-4','keystore','C--','Jarvis',26,'2019-03-26'),(45,'111-222-333-4','keystore','C--','Jarvis',27,'2019-03-26'),(46,'111-222-333-4','keystore','C--','Jarvis',28,'2019-03-26'),(47,'111-222-333-4','keystore','C--','Jarvis',29,'2019-03-26'),(48,'111-222-333-4','keystore','C--','Jarvis',30,'2019-03-26'),(49,'111-222-333-4','keystore','C--','Jarvis',31,'2019-03-26'),(50,'111-222-333-4','keystore','C--','Jarvis',32,'2019-03-26'),(51,'111-222-333-4','keystore','C--','Jarvis',34,'2019-03-26'),(52,'432-543-321-9','旗標','防火牆頻寬管理連線管制','施威銘',560,'2006-07-01');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `publishers`
--

DROP TABLE IF EXISTS `publishers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `publishers` (
  `publisher` varchar(30) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`publisher`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publishers`
--

LOCK TABLES `publishers` WRITE;
/*!40000 ALTER TABLE `publishers` DISABLE KEYS */;
INSERT INTO `publishers` VALUES ('flag','04-23331234','台中市大安區中正路一號'),('keystore','04-23456789','台中市西屯區羅斯福路一號'),('旗標','04-27050888','台中市西屯區西屯路二段256巷6號3樓之6');
/*!40000 ALTER TABLE `publishers` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-03-29 15:30:34
