-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: bdms
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
-- Table structure for table `agency`
--

DROP TABLE IF EXISTS `agency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agency` (
  `agency_id` varchar(20) NOT NULL,
  `agency_type` enum('IA','TA') NOT NULL,
  `agency_name` varchar(100) NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`agency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency`
--

LOCK TABLES `agency` WRITE;
/*!40000 ALTER TABLE `agency` DISABLE KEYS */;
INSERT INTO `agency` VALUES ('IA001','IA','City Central Blood Bank','Kathmandu'),('IA002','IA','Lalitpur Community IA','Lalitpur'),('TA001','TA','FastTrack Transport Co.','Kathmandu');
/*!40000 ALTER TABLE `agency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bdms_admin`
--

DROP TABLE IF EXISTS `bdms_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bdms_admin` (
  `admin_id` varchar(20) NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT 'Administrator',
  `contact_info` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bdms_admin`
--

LOCK TABLES `bdms_admin` WRITE;
/*!40000 ALTER TABLE `bdms_admin` DISABLE KEYS */;
INSERT INTO `bdms_admin` VALUES ('ADM001','System Administrator','Administrator','admin@bdms.local','admin','admin123');
/*!40000 ALTER TABLE `bdms_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bdms_system`
--

DROP TABLE IF EXISTS `bdms_system`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bdms_system` (
  `system_id` varchar(20) NOT NULL,
  `system_name` varchar(100) NOT NULL DEFAULT 'Central BDMS',
  PRIMARY KEY (`system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bdms_system`
--

LOCK TABLES `bdms_system` WRITE;
/*!40000 ALTER TABLE `bdms_system` DISABLE KEYS */;
INSERT INTO `bdms_system` VALUES ('BDMS01','Central BDMS');
/*!40000 ALTER TABLE `bdms_system` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blood_request`
--

DROP TABLE IF EXISTS `blood_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blood_request` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `receiver_id` varchar(20) NOT NULL,
  `agency_id` varchar(20) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `units_requested` int(11) NOT NULL DEFAULT 1,
  `request_date` datetime DEFAULT current_timestamp(),
  `status` enum('PENDING','FULFILLED_LOCALLY','FORWARDED_TO_BDMS','FULFILLED_BY_TRANSFER','CANCELLED') DEFAULT 'PENDING',
  PRIMARY KEY (`request_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `agency_id` (`agency_id`),
  CONSTRAINT `blood_request_ibfk_1` FOREIGN KEY (`receiver_id`) REFERENCES `receiver` (`receiver_id`) ON DELETE CASCADE,
  CONSTRAINT `blood_request_ibfk_2` FOREIGN KEY (`agency_id`) REFERENCES `intermediate_agency` (`agency_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blood_request`
--

LOCK TABLES `blood_request` WRITE;
/*!40000 ALTER TABLE `blood_request` DISABLE KEYS */;
INSERT INTO `blood_request` VALUES (1,'RCV001','IA001','A+',1,'2026-07-13 16:17:42','FULFILLED_LOCALLY');
/*!40000 ALTER TABLE `blood_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `central_inventory`
--

DROP TABLE IF EXISTS `central_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `central_inventory` (
  `system_id` varchar(20) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `units_available` int(11) NOT NULL DEFAULT 0,
  `last_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`system_id`,`blood_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `central_inventory`
--

LOCK TABLES `central_inventory` WRITE;
/*!40000 ALTER TABLE `central_inventory` DISABLE KEYS */;
INSERT INTO `central_inventory` VALUES ('BDMS01','A+',11,'2026-07-13 16:17:42'),('BDMS01','B+',3,'2026-07-13 15:57:23'),('BDMS01','AB+',4,'2026-07-13 15:57:23'),('BDMS01','O+',13,'2026-07-13 15:57:23'),('BDMS01','O-',2,'2026-07-13 15:57:23');
/*!40000 ALTER TABLE `central_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donation`
--

DROP TABLE IF EXISTS `donation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donation` (
  `donation_id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_id` varchar(20) NOT NULL,
  `agency_id` varchar(20) NOT NULL,
  `donation_date` date NOT NULL DEFAULT curdate(),
  `blood_units` int(11) NOT NULL DEFAULT 1,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`donation_id`),
  KEY `donor_id` (`donor_id`),
  KEY `agency_id` (`agency_id`),
  CONSTRAINT `donation_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `donor` (`donor_id`) ON DELETE CASCADE,
  CONSTRAINT `donation_ibfk_2` FOREIGN KEY (`agency_id`) REFERENCES `intermediate_agency` (`agency_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donation`
--

LOCK TABLES `donation` WRITE;
/*!40000 ALTER TABLE `donation` DISABLE KEYS */;
INSERT INTO `donation` VALUES (1,'DNR001','IA001','2026-07-13',1,'hello');
/*!40000 ALTER TABLE `donation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donor`
--

DROP TABLE IF EXISTS `donor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donor` (
  `donor_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `contact_info` varchar(100) NOT NULL,
  `registered_ia` varchar(20) NOT NULL,
  `certificate_issued` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`donor_id`),
  KEY `registered_ia` (`registered_ia`),
  CONSTRAINT `donor_ibfk_1` FOREIGN KEY (`registered_ia`) REFERENCES `intermediate_agency` (`agency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donor`
--

LOCK TABLES `donor` WRITE;
/*!40000 ALTER TABLE `donor` DISABLE KEYS */;
INSERT INTO `donor` VALUES ('DNR001','Ramesh Mainali',22,'Male','A+','9761688629','IA001',1);
/*!40000 ALTER TABLE `donor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donor_certificate`
--

DROP TABLE IF EXISTS `donor_certificate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donor_certificate` (
  `certificate_id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_id` varchar(20) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `issue_date` date NOT NULL DEFAULT curdate(),
  `valid_till` date DEFAULT NULL,
  `certificate_no` varchar(50) NOT NULL,
  PRIMARY KEY (`certificate_id`),
  UNIQUE KEY `certificate_no` (`certificate_no`),
  KEY `donor_id` (`donor_id`),
  KEY `donation_id` (`donation_id`),
  CONSTRAINT `donor_certificate_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `donor` (`donor_id`) ON DELETE CASCADE,
  CONSTRAINT `donor_certificate_ibfk_2` FOREIGN KEY (`donation_id`) REFERENCES `donation` (`donation_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donor_certificate`
--

LOCK TABLES `donor_certificate` WRITE;
/*!40000 ALTER TABLE `donor_certificate` DISABLE KEYS */;
INSERT INTO `donor_certificate` VALUES (1,'DNR001',1,'2026-07-13','2027-07-13','CERT-2026-0001');
/*!40000 ALTER TABLE `donor_certificate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ia_staff`
--

DROP TABLE IF EXISTS `ia_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ia_staff` (
  `staff_id` varchar(20) NOT NULL,
  `agency_id` varchar(20) NOT NULL,
  `staff_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `username` (`username`),
  KEY `agency_id` (`agency_id`),
  CONSTRAINT `ia_staff_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `agency` (`agency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ia_staff`
--

LOCK TABLES `ia_staff` WRITE;
/*!40000 ALTER TABLE `ia_staff` DISABLE KEYS */;
INSERT INTO `ia_staff` VALUES ('STF001','IA001','City Central Staff','ia1','ia123'),('STF002','IA002','Lalitpur Staff','ia2','ia123');
/*!40000 ALTER TABLE `ia_staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intermediate_agency`
--

DROP TABLE IF EXISTS `intermediate_agency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intermediate_agency` (
  `agency_id` varchar(20) NOT NULL,
  PRIMARY KEY (`agency_id`),
  CONSTRAINT `intermediate_agency_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `agency` (`agency_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intermediate_agency`
--

LOCK TABLES `intermediate_agency` WRITE;
/*!40000 ALTER TABLE `intermediate_agency` DISABLE KEYS */;
INSERT INTO `intermediate_agency` VALUES ('IA001'),('IA002');
/*!40000 ALTER TABLE `intermediate_agency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `local_inventory`
--

DROP TABLE IF EXISTS `local_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `local_inventory` (
  `agency_id` varchar(20) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `units_available` int(11) NOT NULL DEFAULT 0,
  `last_update` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`agency_id`,`blood_type`),
  CONSTRAINT `local_inventory_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `intermediate_agency` (`agency_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `local_inventory`
--

LOCK TABLES `local_inventory` WRITE;
/*!40000 ALTER TABLE `local_inventory` DISABLE KEYS */;
INSERT INTO `local_inventory` VALUES ('IA001','A+',0,'2026-07-13 16:41:36'),('IA001','B+',3,'2026-07-13 15:57:23'),('IA001','O+',5,'2026-07-13 15:57:23'),('IA001','O-',2,'2026-07-13 15:57:23'),('IA002','A+',1,'2026-07-13 15:57:23'),('IA002','AB+',4,'2026-07-13 15:57:23'),('IA002','O+',8,'2026-07-13 15:57:23');
/*!40000 ALTER TABLE `local_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receiver`
--

DROP TABLE IF EXISTS `receiver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receiver` (
  `receiver_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `blood_type_required` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `contact_info` varchar(100) NOT NULL,
  PRIMARY KEY (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `person`
-- Unified donor/receiver login: one account, one ID, reused verbatim as
-- donor_id or receiver_id the first time this person donates or requests.
--

CREATE TABLE `person` (
  `person_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `contact_info` varchar(100) NOT NULL,
  `registered_ia` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`person_id`),
  UNIQUE KEY `username` (`username`),
  KEY `registered_ia` (`registered_ia`),
  CONSTRAINT `person_ibfk_1` FOREIGN KEY (`registered_ia`) REFERENCES `intermediate_agency` (`agency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receiver`
--

LOCK TABLES `receiver` WRITE;
/*!40000 ALTER TABLE `receiver` DISABLE KEYS */;
INSERT INTO `receiver` VALUES ('RCV001','Ramesh Mainali','A+','9761688629');
/*!40000 ALTER TABLE `receiver` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report`
--

DROP TABLE IF EXISTS `report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` varchar(20) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `report_date` datetime DEFAULT current_timestamp(),
  `report_details` text DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `report_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `bdms_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report`
--

LOCK TABLES `report` WRITE;
/*!40000 ALTER TABLE `report` DISABLE KEYS */;
/*!40000 ALTER TABLE `report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ta_staff`
--

DROP TABLE IF EXISTS `ta_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ta_staff` (
  `staff_id` varchar(20) NOT NULL,
  `agency_id` varchar(20) NOT NULL,
  `staff_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `username` (`username`),
  KEY `agency_id` (`agency_id`),
  CONSTRAINT `ta_staff_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `agency` (`agency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ta_staff`
--

LOCK TABLES `ta_staff` WRITE;
/*!40000 ALTER TABLE `ta_staff` DISABLE KEYS */;
INSERT INTO `ta_staff` VALUES ('TST001','TA001','Transport Staff One','ta1','ta123');
/*!40000 ALTER TABLE `ta_staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_assignment`
--

DROP TABLE IF EXISTS `transport_assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transport_assignment` (
  `transport_id` int(11) NOT NULL AUTO_INCREMENT,
  `system_id` varchar(20) NOT NULL,
  `ta_agency_id` varchar(20) NOT NULL,
  `source_agency_id` varchar(20) NOT NULL,
  `dest_agency_id` varchar(20) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `units` int(11) NOT NULL,
  `status` enum('ASSIGNED','COLLECTED','IN_TRANSIT','DELIVERED','CONFIRMED') DEFAULT 'ASSIGNED',
  `assigned_at` datetime DEFAULT current_timestamp(),
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`transport_id`),
  KEY `system_id` (`system_id`),
  KEY `ta_agency_id` (`ta_agency_id`),
  KEY `source_agency_id` (`source_agency_id`),
  KEY `dest_agency_id` (`dest_agency_id`),
  CONSTRAINT `transport_assignment_ibfk_1` FOREIGN KEY (`system_id`) REFERENCES `bdms_system` (`system_id`),
  CONSTRAINT `transport_assignment_ibfk_2` FOREIGN KEY (`ta_agency_id`) REFERENCES `transportation_agency` (`agency_id`),
  CONSTRAINT `transport_assignment_ibfk_3` FOREIGN KEY (`source_agency_id`) REFERENCES `intermediate_agency` (`agency_id`),
  CONSTRAINT `transport_assignment_ibfk_4` FOREIGN KEY (`dest_agency_id`) REFERENCES `intermediate_agency` (`agency_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_assignment`
--

LOCK TABLES `transport_assignment` WRITE;
/*!40000 ALTER TABLE `transport_assignment` DISABLE KEYS */;
INSERT INTO `transport_assignment` VALUES (1,'BDMS01','TA001','IA001','IA001','A+',2,'ASSIGNED','2026-07-13 16:41:27',NULL),(2,'BDMS01','TA001','IA001','IA001','A+',2,'ASSIGNED','2026-07-13 16:41:31',NULL),(3,'BDMS01','TA001','IA001','IA001','A+',2,'ASSIGNED','2026-07-13 16:41:33',NULL),(4,'BDMS01','TA001','IA001','IA001','A+',2,'ASSIGNED','2026-07-13 16:41:35',NULL),(5,'BDMS01','TA001','IA001','IA001','A+',2,'ASSIGNED','2026-07-13 16:41:36',NULL);
/*!40000 ALTER TABLE `transport_assignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_record`
--

DROP TABLE IF EXISTS `transport_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transport_record` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_id` varchar(20) NOT NULL,
  `record_note` varchar(255) DEFAULT NULL,
  `record_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`record_id`),
  KEY `agency_id` (`agency_id`),
  CONSTRAINT `transport_record_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `transportation_agency` (`agency_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_record`
--

LOCK TABLES `transport_record` WRITE;
/*!40000 ALTER TABLE `transport_record` DISABLE KEYS */;
/*!40000 ALTER TABLE `transport_record` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transportation_agency`
--

DROP TABLE IF EXISTS `transportation_agency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transportation_agency` (
  `agency_id` varchar(20) NOT NULL,
  PRIMARY KEY (`agency_id`),
  CONSTRAINT `transportation_agency_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `agency` (`agency_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transportation_agency`
--

LOCK TABLES `transportation_agency` WRITE;
/*!40000 ALTER TABLE `transportation_agency` DISABLE KEYS */;
INSERT INTO `transportation_agency` VALUES ('TA001');
/*!40000 ALTER TABLE `transportation_agency` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-13 16:44:12
