mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: anonymize_demo
-- ------------------------------------------------------
-- Server version	8.0.44

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
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `anonymized` tinyint(1) NOT NULL DEFAULT '0',
  `reference_code` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (26,'customer1@example.com','Customer One','active',0,'CUST-00000001'),(27,'customer2@example.com','Customer Two','active',0,'CUST-00000002'),(28,'customer3@example.com','Customer Three','active',0,'CUST-00000003'),(29,'customer4@example.com','Customer Four','active',0,'CUST-00000004'),(30,'customer5@example.com','Customer Five','active',0,'CUST-00000005'),(31,'customer6@example.com','Customer Six','active',0,'CUST-00000006'),(32,'customer7@example.com','Customer Seven','active',0,'CUST-00000007'),(33,'customer8@example.com','Customer Eight','active',0,'CUST-00000008'),(34,'customer9@example.com','Customer Nine','active',0,'CUST-00000009'),(35,'customer10@example.com','Customer Ten','active',0,'CUST-00000010'),(36,'customer11@example.com','Customer Eleven','active',0,'CUST-00000011'),(37,'customer12@example.com','Customer Twelve','active',0,'CUST-00000012'),(38,'customer13@example.com','Customer Thirteen','active',0,'CUST-00000013'),(39,'customer14@example.com','Customer Fourteen','active',0,'CUST-00000014'),(40,'customer15@example.com','Customer Fifteen','active',0,'CUST-00000015'),(41,'customer16@example.com','Customer Sixteen','inactive',0,'CUST-00000016'),(42,'customer17@example.com','Customer Seventeen','inactive',0,'CUST-00000017'),(43,'customer18@example.com','Customer Eighteen','inactive',0,'CUST-00000018'),(44,'customer19@example.com','Customer Nineteen','inactive',0,'CUST-00000019'),(45,'customer20@example.com','Customer Twenty','inactive',0,'CUST-00000020'),(46,'customer21@example.com','Customer Twenty-One','active',0,'CUST-00000021'),(47,'customer22@example.com','Customer Twenty-Two','active',0,'CUST-00000022'),(48,'customer23@example.com','Customer Twenty-Three','active',0,'CUST-00000023'),(49,'customer24@example.com','Customer Twenty-Four','active',0,'CUST-00000024'),(50,'customer25@example.com','Customer Twenty-Five','active',0,'CUST-00000025');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_subscriptions`
--

DROP TABLE IF EXISTS `email_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL,
  `backup_email` varchar(100) DEFAULT NULL,
  `subscribed_at` datetime NOT NULL,
  `unsubscribed_at` datetime DEFAULT NULL,
  `source` varchar(50) NOT NULL,
  `notes` longtext,
  `anonymized` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_subscriptions`
--

LOCK TABLES `email_subscriptions` WRITE;
/*!40000 ALTER TABLE `email_subscriptions` DISABLE KEYS */;
INSERT INTO `email_subscriptions` VALUES (81,'john.doe@test-domain.com','John Doe','active','john.backup@example.com','2025-07-20 14:49:20',NULL,'website','Regular subscriber',0),(82,'jane.smith@test-domain.com','Jane Smith','active','jane.backup@company.com','2025-10-20 14:49:20',NULL,'newsletter','Newsletter subscriber',0),(83,'bob.wilson@test-domain.com','Bob Wilson','inactive','bob.backup@example.com','2025-01-20 14:49:20','2025-11-20 14:49:20','promotion','Inactive user - should anonymize backup email and notes',0),(84,'alice.brown@test-domain.com','Alice Brown','unsubscribed','alice.backup@test-domain.com','2025-05-20 14:49:20','2025-12-20 14:49:20','partner','Unsubscribed user - should anonymize backup email, date and notes',0),(85,'charlie.davis@test-domain.com','Charlie Davis','active',NULL,'2025-09-20 14:49:20',NULL,'website',NULL,0),(86,'david.miller@example.com','David Miller','active','david.backup@test-domain.com','2025-08-20 14:49:20',NULL,'newsletter','Active subscriber',0),(87,'emma.jones@example.com','Emma Jones','inactive','emma.backup@example.com','2025-03-20 14:49:20','2025-10-20 14:49:20','promotion','Inactive - backup email and notes should be anonymized',0),(88,'frank.taylor@example.com','Frank Taylor','unsubscribed','frank.backup@company.com','2025-02-20 14:49:20','2025-08-20 14:49:20','website','Unsubscribed - all conditional fields should be anonymized',0),(89,'grace.anderson@example.com','Grace Anderson','active',NULL,'2025-11-20 14:49:20',NULL,'partner','Active with no backup',0),(90,'henry.thomas@example.com','Henry Thomas','inactive',NULL,'2025-04-20 14:49:20','2025-09-20 14:49:20','newsletter','Inactive without backup - notes should be anonymized',0),(91,'isabella.martinez@demo.local','Isabella Martinez','active','isabella.backup@demo.local','2025-06-20 14:49:20',NULL,'website','Demo account active',0),(92,'james.rodriguez@demo.local','James Rodriguez','inactive','james.backup@test-domain.com','2025-01-20 14:49:20','2025-07-20 14:49:20','promotion','Demo inactive - backup and notes anonymized',0),(93,'karen.white@demo.local','Karen White','unsubscribed','karen.backup@example.com','2024-12-20 14:49:20','2025-06-20 14:49:20','partner','Demo unsubscribed - all conditional fields anonymized',0),(94,'lucas.harris@demo.local','Lucas Harris','active',NULL,'2025-12-20 14:49:20',NULL,'newsletter',NULL,0),(95,'maria.clark@company.com','Maria Clark','active','maria.backup@company.com','2025-07-20 14:49:20',NULL,'website','Company email active - email NOT anonymized',0),(96,'noah.lewis@company.com','Noah Lewis','active',NULL,'2025-09-20 14:49:20',NULL,'newsletter',NULL,0),(97,'olivia.walker@company.com','Olivia Walker','inactive','olivia.backup@company.com','2025-05-20 14:49:20','2025-11-20 14:49:20','promotion','Company email inactive - email NOT anonymized, but backup and notes YES',0),(98,'peter.hall@company.com','Peter Hall','unsubscribed','peter.backup@company.com','2025-03-20 14:49:20','2025-10-20 14:49:20','partner','Company email unsubscribed - email NOT anonymized, but backup, date and notes YES',0),(99,'quinn.allen@business.org','Quinn Allen','active','quinn.backup@business.org','2025-08-20 14:49:20',NULL,'website','Business email active',0),(100,'rachel.young@business.org','Rachel Young','inactive','rachel.backup@business.org','2025-04-20 14:49:20','2025-09-20 14:49:20','newsletter','Business email inactive - backup and notes anonymized',0),(101,'samuel.king@business.org','Samuel King','unsubscribed','samuel.backup@business.org','2025-02-20 14:49:20','2025-08-20 14:49:20','promotion','Business email unsubscribed - backup, date and notes anonymized',0),(102,'tina.scott@real-domain.net','Tina Scott','active','tina.backup@real-domain.net','2025-06-20 14:49:20',NULL,'partner','Real domain active',0),(103,'victor.green@real-domain.net','Victor Green','inactive','victor.backup@real-domain.net','2025-01-20 14:49:20','2025-07-20 14:49:20','website','Real domain inactive',0),(104,'wendy.adams@real-domain.net','Wendy Adams','unsubscribed','wendy.backup@real-domain.net','2024-12-20 14:49:20','2025-05-20 14:49:20','newsletter','Real domain unsubscribed',0),(105,'xavier.baker@other-domain.com','Xavier Baker','active','xavier.backup@other-domain.com','2025-10-20 14:49:20',NULL,'promotion','Other domain active',0),(106,'yolanda.cook@other-domain.com','Yolanda Cook','inactive','yolanda.backup@other-domain.com','2025-05-20 14:49:20','2025-11-20 14:49:20','partner','Other domain inactive',0),(107,'zachary.morris@other-domain.com','Zachary Morris','unsubscribed','zachary.backup@other-domain.com','2025-03-20 14:49:20','2025-09-20 14:49:20','website','Other domain unsubscribed',0),(108,'anna.lee@test-domain.com','Anna Lee','active','anna.backup@example.com','2025-08-20 14:49:20',NULL,'newsletter','Active with backup - backup NOT anonymized',0),(109,'benjamin.wright@example.com','Benjamin Wright','inactive',NULL,'2025-04-20 14:49:20','2025-10-20 14:49:20','promotion','Inactive without backup - notes anonymized',0),(110,'catherine.hill@demo.local','Catherine Hill','unsubscribed',NULL,'2025-02-20 14:49:20','2025-08-20 14:49:20','partner','Unsubscribed without backup - date and notes anonymized',0),(111,'daniel.ward@test-domain.com','Daniel Ward','active','daniel.backup@example.com','2025-09-20 14:49:20',NULL,'website',NULL,0),(112,'elizabeth.turner@example.com','Elizabeth Turner','inactive','elizabeth.backup@demo.local','2025-05-20 14:49:20','2025-11-20 14:49:20','newsletter',NULL,0),(113,'frederick.cooper@demo.local','Frederick Cooper','unsubscribed','frederick.backup@test-domain.com','2025-03-20 14:49:20','2025-09-20 14:49:20','promotion',NULL,0),(114,'george.richardson@test-domain.com','George Richardson','active',NULL,'2025-07-20 14:49:20',NULL,'website','Source: website',0),(115,'helen.cox@example.com','Helen Cox','active',NULL,'2025-08-20 14:49:20',NULL,'newsletter','Source: newsletter',0),(116,'ian.howard@demo.local','Ian Howard','active',NULL,'2025-09-20 14:49:20',NULL,'promotion','Source: promotion',0),(117,'julia.ward@test-domain.com','Julia Ward','active',NULL,'2025-10-20 14:49:20',NULL,'partner','Source: partner',0),(118,'kevin.torres@example.com','Kevin Torres','active',NULL,'2024-01-20 14:49:20',NULL,'website','Subscribed 2 years ago',0),(119,'linda.peterson@demo.local','Linda Peterson','unsubscribed',NULL,'2025-01-20 14:49:20','2025-12-20 14:49:20','newsletter','Unsubscribed 1 month ago',0),(120,'michael.gray@test-domain.com','Michael Gray','unsubscribed',NULL,'2025-07-20 14:49:20','2026-01-13 14:49:20','promotion','Recently unsubscribed',0);
/*!40000 ALTER TABLE `email_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int NOT NULL,
  `department` varchar(100) NOT NULL,
  `previous_company` varchar(100) DEFAULT NULL,
  `hire_date` datetime NOT NULL,
  `anonymized` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_BA82C300F85E0677` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (49,'Alice','Johnson','alice.johnson','alice.johnson@company.com','+1-555-0101','1985-05-15',38,'HR','HR Solutions Inc.','2021-01-20 14:49:20',0),(50,'Bob','Smith','bob.smith','bob.smith@company.com','+1-555-0102','1990-08-20',33,'HR','People Management LLC','2023-01-20 14:49:20',0),(51,'Carol','Williams','carol.williams','carol.williams@company.com','+1-555-0103','1988-12-10',35,'HR','Talent Acquisition Corp','2022-01-20 14:49:20',0),(52,'David','Brown','david.brown','david.brown@company.com','+1-555-0104','1992-03-25',31,'Engineering','Tech Innovations Ltd.','2024-01-20 14:49:20',0),(53,'Emma','Jones','emma.jones','emma.jones@company.com','+1-555-0105','1995-07-30',28,'Engineering','Software Solutions Inc.','2025-01-20 14:49:20',0),(54,'Frank','Garcia','frank.garcia','frank.garcia@company.com','+1-555-0106','1987-11-05',36,'Sales','Sales Pro LLC','2020-01-20 14:49:20',0),(55,'Grace','Miller','grace.miller','grace.miller@company.com','+1-555-0107','1993-01-18',31,'Marketing','Digital Marketing Corp','2023-01-20 14:49:20',0),(56,'Henry','Davis','henry.davis','henry.davis@company.com','+1-555-0108','1989-09-22',34,'Engineering','Code Masters Inc.','2022-01-20 14:49:20',0),(57,'Ivy','Rodriguez','ivy.rodriguez','ivy.rodriguez@company.com','+1-555-0109','1994-04-12',29,'Sales','Business Solutions LLC','2024-01-20 14:49:20',0),(58,'Jack','Martinez','jack.martinez','jack.martinez@company.com','+1-555-0110','1991-06-28',32,'Marketing','Creative Agency Ltd.','2025-01-20 14:49:20',0),(59,'Kate','Anderson','kate.anderson','kate.anderson@company.com','+1-555-0111','1996-10-15',27,'Engineering','Startup Tech Inc.','2025-07-20 14:49:20',0),(60,'Liam','Taylor','liam.taylor','liam.taylor@company.com','+1-555-0112','1986-02-08',37,'Sales','Enterprise Sales Corp','2019-01-20 14:49:20',0);
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_address` varchar(255) NOT NULL,
  `bank_account` varchar(34) DEFAULT NULL,
  `credit_card` varchar(19) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `issue_date` datetime NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `anonymized` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (33,'INV-000000000001','Acme Corporation','100 Business Park, New York, NY 10001','ES9121000418450200051332','4532123456789012',1250.50,'2025-10-20 14:49:20','2026-02-16 14:49:20','paid',0),(34,'INV-000000000002','Tech Solutions Inc.','200 Innovation Drive, San Francisco, CA 94102','ES9121000418450200051333','5555123456789012',3450.75,'2025-11-20 14:49:20','2026-02-17 14:49:20','pending',0),(35,'INV-000000000003','Global Services LLC','300 Commerce Blvd, Chicago, IL 60601','ES9121000418450200051334','4111111111111111',890.25,'2025-12-20 14:49:20','2026-02-18 14:49:20','paid',0),(36,'INV-000000000004','Digital Marketing Corp','400 Media Street, Los Angeles, CA 90001','ES9121000418450200051335','378282246310005',5678.90,'2025-12-30 14:49:20','2026-01-27 14:49:20','overdue',0),(37,'INV-000000000005','Software Development Ltd.','500 Code Avenue, Seattle, WA 98101','ES9121000418450200051336','6011111111111117',2345.67,'2026-01-06 14:49:20','2026-02-05 14:49:20','pending',0),(38,'INV-000000000006','Consulting Group Inc.','600 Strategy Way, Boston, MA 02101','ES9121000418450200051337','5105105105105100',4567.89,'2026-01-13 14:49:20','2026-02-12 14:49:20','paid',0),(39,'INV-000000000007','E-commerce Solutions LLC','700 Online Plaza, Miami, FL 33101','ES9121000418450200051338','4242424242424242',1234.56,'2026-01-15 14:49:20','2026-02-14 14:49:20','pending',0),(40,'INV-000000000008','Cloud Services Corp','800 Cloud Drive, Austin, TX 78701','ES9121000418450200051339','371449635398431',7890.12,'2026-01-16 14:49:20','2026-02-15 14:49:20','paid',0);
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) DEFAULT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `billing_address` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `anonymized` tinyint NOT NULL DEFAULT '0',
  `type_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E52FFDEEC54C8C93` (`type_id`),
  CONSTRAINT `FK_E52FFDEEC54C8C93` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'ORD-0000000001','123 Main St, New York, NY 10001','123 Main St, New York, NY 10001',299.99,'2025-11-20 14:49:20','completed','customer1@example.com',0,15),(2,'ORD-0000000002','456 Oak Ave, Los Angeles, CA 90001','456 Oak Ave, Los Angeles, CA 90001',599.99,'2025-12-20 14:49:20','completed','customer2@example.com',0,17),(3,'ORD-0000000003','789 Pine Rd, Chicago, IL 60601','789 Pine Rd, Chicago, IL 60601',149.99,'2025-12-30 14:49:20','completed','customer3@example.com',0,18),(4,'ORD-0000000004','321 Elm St, Houston, TX 77001','321 Elm St, Houston, TX 77001',899.99,'2026-01-06 14:49:20','completed','customer4@example.com',0,19),(5,'ORD-0000000005','654 Maple Dr, Phoenix, AZ 85001','654 Maple Dr, Phoenix, AZ 85001',249.99,'2026-01-13 14:49:20','completed','customer5@example.com',0,20),(6,'ORD-0000000006','987 Cedar Ln, Philadelphia, PA 19101','987 Cedar Ln, Philadelphia, PA 19101',399.99,'2026-01-15 14:49:20','completed','customer6@example.com',0,15),(7,'ORD-0000000007','147 Birch Way, San Antonio, TX 78201','147 Birch Way, San Antonio, TX 78201',549.99,'2026-01-16 14:49:20','completed','customer7@example.com',0,16),(8,'ORD-0000000008','258 Willow St, San Diego, CA 92101','258 Willow St, San Diego, CA 92101',199.99,'2026-01-17 14:49:20','completed','customer8@example.com',0,17),(9,'ORD-0000000009','369 Spruce Ave, Dallas, TX 75201','369 Spruce Ave, Dallas, TX 75201',799.99,'2026-01-18 14:49:20','completed','customer9@example.com',0,18),(10,'ORD-0000000010','741 Ash Blvd, San Jose, CA 95101','741 Ash Blvd, San Jose, CA 95101',349.99,'2026-01-19 14:49:20','completed','customer10@example.com',0,15),(11,'ORD-0000000011','852 Poplar Rd, Austin, TX 78701','852 Poplar Rd, Austin, TX 78701',449.99,'2026-01-13 14:49:20','pending','customer11@example.com',0,15),(12,'ORD-0000000012','963 Hickory Dr, Jacksonville, FL 32201','963 Hickory Dr, Jacksonville, FL 32201',299.99,'2026-01-15 14:49:20','processing','customer12@example.com',0,16),(13,'ORD-0000000013','159 Sycamore Ln, Fort Worth, TX 76101','159 Sycamore Ln, Fort Worth, TX 76101',649.99,'2026-01-17 14:49:20','shipped','customer13@example.com',0,17);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` longtext,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `anonymized` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (11,'Laptop Pro 15','High-performance laptop with 16GB RAM and 512GB SSD',1299.99,'https://example.com/products/laptop-pro-15.jpg','active','2025-07-20 14:49:20',0),(12,'Wireless Mouse','Ergonomic wireless mouse with long battery life',29.99,'https://example.com/products/mouse.jpg','active','2025-10-20 14:49:20',0),(13,'Mechanical Keyboard','RGB mechanical keyboard with cherry switches',149.99,'https://example.com/products/keyboard.jpg','active','2025-11-20 14:49:20',0),(14,'4K Monitor','27-inch 4K UHD monitor with HDR support',599.99,'https://example.com/products/monitor.jpg','active','2025-01-20 14:49:20',0),(15,'USB-C Hub','Multi-port USB-C hub with HDMI and SD card reader',49.99,'https://example.com/products/hub.jpg','active','2025-09-20 14:49:20',0),(16,'Webcam HD','1080p HD webcam with auto-focus and noise cancellation',79.99,'https://example.com/products/webcam.jpg','active','2025-08-20 14:49:20',0),(17,'Gaming Headset','7.1 surround sound gaming headset with RGB lighting',129.99,'https://example.com/products/headset.jpg','active','2025-05-20 14:49:20',0),(18,'External SSD 1TB','Portable external SSD with USB 3.2 Gen 2',199.99,'https://example.com/products/ssd.jpg','active','2025-06-20 14:49:20',0),(19,'Old Product Model','This product is no longer available',99.99,NULL,'discontinued','2024-01-20 14:49:20',0),(20,'Pending Product','Product awaiting approval',199.99,NULL,'pending','2026-01-13 14:49:20',0);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(36) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `mac_address` varchar(17) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `location` varchar(50) NOT NULL,
  `theme_color` varchar(7) NOT NULL,
  `is_active` tinyint NOT NULL,
  `score` decimal(10,2) NOT NULL,
  `log_file` varchar(255) NOT NULL,
  `metadata` longtext,
  `description` longtext,
  `log_level` varchar(50) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `language_code` varchar(5) NOT NULL,
  `created_at` datetime NOT NULL,
  `user_id_hash` varchar(64) DEFAULT NULL,
  `process_status` varchar(50) DEFAULT NULL,
  `data_classification` varchar(50) DEFAULT NULL,
  `anonymized` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (11,'550e8400-e29b-41d4-a716-446655440000','192.168.1.100','00:1b:44:11:3a:b7','secret_api_key_12345','a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3','40.4168,-3.7038','#FF5733',1,85.50,'logs/application.log','{\"user_id\": 123, \"action\": \"login\", \"timestamp\": \"2024-01-15T10:30:00Z\"}','User successfully logged into the system and accessed the dashboard.','info','ES','es','2026-01-19 14:49:20','user123','completed','SENSITIVE',0),(12,'550e8400-e29b-41d4-a716-446655440001','10.0.0.50','aa:bb:cc:dd:ee:ff','another_secret_key','b665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae4','41.3851,2.1734','#33FF57',0,92.75,'logs/error.log','{\"error_code\": 500, \"message\": \"Internal server error\", \"stack_trace\": \"...\"}','An error occurred while processing the payment request. The transaction was not completed.','error','FR','fr','2026-01-18 14:49:20','user456','failed','CONFIDENTIAL',0),(13,'550e8400-e29b-41d4-a716-446655440002','172.16.0.10','11:22:33:44:55:66','test_api_key_789','c665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae5','51.5074,-0.1278','#3357FF',1,78.25,'logs/debug.log','{\"debug_level\": 3, \"component\": \"authentication\", \"details\": \"...\"}','Debug information for authentication module. Checking user credentials and permissions.','debug','GB','en','2026-01-17 14:49:20','user789','processing','PUBLIC',0),(14,'550e8400-e29b-41d4-a716-446655440003','203.0.113.1','ff:ee:dd:cc:bb:aa','production_key_xyz','d665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae6','48.8566,2.3522','#FF33F5',1,95.00,'logs/warning.log','{\"warning_type\": \"deprecation\", \"affected_component\": \"legacy_api\", \"recommendation\": \"migrate\"}','Warning: A deprecated API endpoint was accessed. Please migrate to the new version as soon as possible.','warning','DE','de','2026-01-16 14:49:20','user012','pending','INTERNAL',0),(15,'550e8400-e29b-41d4-a716-446655440004','198.51.100.1','12:34:56:78:90:ab','staging_key_abc','e665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae7','40.7128,-74.0060','#F5FF33',0,67.33,'logs/info.log','{\"event\": \"user_registration\", \"user_id\": 999, \"registration_method\": \"oauth\"}','New user registered successfully through OAuth authentication. Account verification email sent.','info','US','en','2026-01-15 14:49:20','user345','completed','RESTRICTED',0);
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `types`
--

DROP TABLE IF EXISTS `types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `types`
--

LOCK TABLES `types` WRITE;
/*!40000 ALTER TABLE `types` DISABLE KEYS */;
INSERT INTO `types` VALUES (15,'HR','Human Resources'),(16,'HR Management','HR Management Department'),(17,'Sales','Sales Department'),(18,'IT','Information Technology'),(19,'Marketing','Marketing Department'),(20,'Finance','Finance Department'),(21,'Operations','Operations Department');
/*!40000 ALTER TABLE `types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `age` int NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `iban` varchar(34) DEFAULT NULL,
  `creditCard` varchar(19) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `anonymized` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (21,'john.doe@example.com','John','Doe',30,'+34612345678','ES9121000418450200051332','4532015112830366','active',0),(22,'jane.smith@example.com','Jane','Smith',25,'+34698765432','ES9121000418450200051333','4532015112830367','active',0),(23,'bob.wilson@example.com','Bob','Wilson',40,'+34655512345','ES9121000418450200051334','4532015112830368','active',0),(24,'alice.brown@example.com','Alice','Brown',28,'+34611122233','ES9121000418450200051335','4532015112830369','active',0),(25,'charlie.davis@example.com','Charlie','Davis',35,'+34644455566','ES9121000418450200051336','4532015112830370','active',0),(26,'david.miller@example.com','David','Miller',45,'+34677788899','ES9121000418450200051337','4532015112830371','inactive',0),(27,'emma.jones@example.com','Emma','Jones',22,'+34622233344','ES9121000418450200051338','4532015112830372','inactive',0),(28,'frank.taylor@example.com','Frank','Taylor',50,NULL,NULL,NULL,'active',0),(29,'grace.anderson@example.com','Grace','Anderson',33,'+34633344455',NULL,'4532015112830373','active',0),(30,'henry.thomas@example.com','Henry','Thomas',29,NULL,'ES9121000418450200051339',NULL,'active',0),(31,'isabella.martinez@example.com','Isabella','Martinez',18,'+34644455566','ES9121000418450200051340','4532015112830374','active',0),(32,'james.rodriguez@example.com','James','Rodriguez',100,'+34655566677','ES9121000418450200051341','4532015112830375','active',0),(33,'karen.white@example.com','Karen','White',38,'+34666677788','ES9121000418450200051342','4532015112830376','active',0),(34,'lucas.harris@example.com','Lucas','Harris',27,'+34677788899','ES9121000418450200051343','4532015112830377','active',0),(35,'maria.clark@example.com','Maria','Clark',31,'+34688899900','ES9121000418450200051344','4532015112830378','active',0),(36,'noah.lewis@example.com','Noah','Lewis',42,'+34699900011','ES9121000418450200051345','4532015112830379','active',0),(37,'olivia.walker@example.com','Olivia','Walker',26,'+34600011122','ES9121000418450200051346','4532015112830380','active',0),(38,'peter.hall@example.com','Peter','Hall',39,'+34611122233','ES9121000418450200051347','4532015112830381','active',0),(39,'quinn.allen@example.com','Quinn','Allen',24,'+34622233344','ES9121000418450200051348','4532015112830382','active',0),(40,'rachel.young@example.com','Rachel','Young',36,'+34633344455','ES9121000418450200051349','4532015112830383','active',0);
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

-- Dump completed on 2026-01-20 15:19:58
