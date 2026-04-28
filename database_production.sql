-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: temu_clone
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
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'France',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES (1,4,'ibrahima sy','778569332','rue12',NULL,'ZDZDNKZDJZKDJ',NULL,'1200','France',0,'2026-04-02 11:02:39'),(2,5,'mamour sarr','778569332','DKJEDZDLDZLK',NULL,'DAKAR',NULL,'1200','france',0,'2026-04-13 03:32:54');
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `subtitle` varchar(300) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES (1,'🎉 Méga Soldes Jusqu\'à -90%','Des milliers de produits à prix imbattables','https://picsum.photos/seed/banner1/1200/400','/pages/search.php',1,1,'2026-04-02 10:44:44'),(2,'📱 High-Tech à Petit Prix','Smartphones, montres, écouteurs...','https://picsum.photos/seed/banner2/1200/400','/pages/category.php?id=3',2,1,'2026-04-02 10:44:44'),(3,'👗 Mode Tendance','Nouveautés femme & homme chaque jour','https://picsum.photos/seed/banner3/1200/400','/pages/category.php?id=1',3,1,'2026-04-02 10:44:44');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (1,NULL,'cart_69ceab6ad904c1.68834732',4,NULL,1,'2026-04-02 10:46:48'),(4,NULL,'cart_69ceb53d5a0107.18698423',11,NULL,12,'2026-04-02 11:40:33'),(5,4,NULL,1,NULL,1,'2026-04-05 19:07:48'),(6,1,NULL,2,NULL,1,'2026-04-06 12:11:37'),(7,NULL,'cart_69dc39684f9335.02055106',4,NULL,1,'2026-04-13 03:28:55');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(10) DEFAULT '?',
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Mode Femme','mode-femme',NULL,'👗',NULL,NULL,1,1,'2026-04-02 10:44:44'),(2,'Mode Homme','mode-homme',NULL,'👔',NULL,NULL,2,1,'2026-04-02 10:44:44'),(3,'Électronique','electronique',NULL,'📱',NULL,NULL,3,1,'2026-04-02 10:44:44'),(4,'Maison & Jardin','maison-jardin',NULL,'🏡',NULL,NULL,4,1,'2026-04-02 10:44:44'),(5,'Beauté & Santé','beaute-sante',NULL,'💄',NULL,NULL,5,1,'2026-04-02 10:44:44'),(6,'Sports & Loisirs','sports-loisirs',NULL,'⚽',NULL,NULL,6,1,'2026-04-02 10:44:44'),(7,'Jouets & Enfants','jouets-enfants',NULL,'🧸',NULL,NULL,7,1,'2026-04-02 10:44:44'),(8,'Bijoux & Accessoires','bijoux-accessoires',NULL,'💍',NULL,NULL,8,1,'2026-04-02 10:44:44');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percent','fixed') DEFAULT 'percent',
  `value` decimal(10,2) NOT NULL,
  `min_order` decimal(10,2) DEFAULT 0.00,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'BIENVENUE10','percent',10.00,20.00,NULL,0,'2026-05-02 10:44:44',1,'2026-04-02 10:44:44'),(2,'FLASH20','percent',20.00,50.00,NULL,0,'2026-04-09 10:44:44',1,'2026-04-02 10:44:44'),(3,'LIVRAISON5','fixed',5.00,30.00,NULL,0,'2026-06-01 10:44:44',1,'2026-04-02 10:44:44'),(4,'N050907020','percent',10.00,20.00,8,0,'2026-04-15 08:08:00',1,'2026-04-14 08:08:31');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,4,NULL,'Montre Connectée Sport','https://picsum.photos/seed/watch1/400/400',29.99,1,29.99),(2,2,1,NULL,'Robe Fleurie Été Bohème','https://picsum.photos/seed/dress1/400/500',12.99,1,12.99),(3,3,1,NULL,'Robe Fleurie Été Bohème','https://picsum.photos/seed/dress1/400/500',12.99,1,12.99),(7,7,1,NULL,'Robe Fleurie Été Bohème','https://picsum.photos/seed/dress1/400/500',12.99,1,12.99),(9,9,6,NULL,'Lampe LED Décorative','https://picsum.photos/seed/lamp1/400/400',14.99,1,14.99),(10,10,1,NULL,'Robe Fleurie Été Bohème','https://picsum.photos/seed/dress1/400/500',12.99,1,12.99),(11,11,4,NULL,'Montre Connectée Sport','https://picsum.photos/seed/watch1/400/400',29.99,1,29.99),(12,12,4,NULL,'Montre Connectée Sport','https://picsum.photos/seed/watch1/400/400',29.99,1,29.99);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `coupon_code` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'card',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  KEY `address_id` (`address_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'TC-F3FF2AEE',4,1,29.99,0.00,0.00,29.99,NULL,'card','pending','confirmed','',NULL,'2026-04-02 11:02:39','2026-04-02 11:58:37'),(2,'TC-2F326C3C',4,1,12.99,4.99,0.00,17.98,NULL,'card','pending','processing','',NULL,'2026-04-02 11:18:27','2026-04-04 08:53:16'),(3,'TC-65668E52',5,2,12.99,4.99,0.00,17.98,NULL,'card','pending','pending',NULL,NULL,'2026-04-13 03:32:54','2026-04-13 03:32:54'),(7,'TC-9413F9ED',5,2,12.99,4.99,0.00,17.98,NULL,'card','pending','pending',NULL,NULL,'2026-04-13 03:45:21','2026-04-13 03:45:21'),(9,'TC-C8D4DD1A',5,2,14.99,4.99,0.00,19.98,NULL,'paytech','pending','pending',NULL,NULL,'2026-04-13 03:59:25','2026-04-13 03:59:25'),(10,'TC-7122CDA0',5,2,12.99,4.99,0.00,17.98,NULL,'paytech','paid','confirmed',NULL,NULL,'2026-04-13 04:44:18','2026-04-13 04:46:06'),(11,'TC-55E02351',5,2,29.99,0.00,0.00,29.99,NULL,'paytech','pending','pending',NULL,NULL,'2026-04-14 07:55:26','2026-04-14 07:55:26'),(12,'TC-79832902',5,2,29.99,0.00,0.00,29.99,NULL,'paytech','pending','pending',NULL,NULL,'2026-04-14 08:04:56','2026-04-14 08:04:56');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (1,1,'https://picsum.photos/seed/dress1/400/500',NULL,1,0),(2,1,'https://picsum.photos/seed/dress2/400/500',NULL,0,1),(3,2,'https://picsum.photos/seed/top1/400/500',NULL,1,0),(4,3,'https://picsum.photos/seed/tshirt1/400/500',NULL,1,0),(5,4,'https://picsum.photos/seed/watch1/400/400',NULL,1,0),(6,5,'https://picsum.photos/seed/earbuds1/400/400',NULL,1,0),(7,6,'https://picsum.photos/seed/lamp1/400/400',NULL,1,0),(8,7,'https://picsum.photos/seed/pillow1/400/400',NULL,1,0),(9,8,'https://picsum.photos/seed/makeup1/400/400',NULL,1,0),(10,9,'https://picsum.photos/seed/serum1/400/400',NULL,1,0),(11,10,'https://picsum.photos/seed/yoga1/400/400',NULL,1,0),(12,11,'https://picsum.photos/seed/lego1/400/400',NULL,1,0),(13,12,'https://picsum.photos/seed/bracelet1/400/400',NULL,1,0),(14,13,'http://localhost/temu-clone/assets/images/uploads/products/img_69dfb9496d1d1.png',NULL,1,0);
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `variant_name` varchar(100) NOT NULL,
  `variant_value` varchar(100) NOT NULL,
  `extra_price` decimal(10,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(280) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `discount_percent` int(11) DEFAULT 0,
  `stock` int(11) DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0,
  `sold_count` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_flash_sale` tinyint(1) DEFAULT 0,
  `flash_sale_end` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `video_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Robe Fleurie Été Bohème','robe-fleurie-ete-boheme','Belle robe d\'été légère avec imprimé floral. Parfaite pour toutes occasions. Tissu respirant et confortable.',NULL,12.99,45.00,71,146,NULL,NULL,4.50,328,2454,1,1,NULL,0,'2026-04-02 10:44:44','2026-04-13 05:37:21',NULL),(2,1,'Top Crop Tendance','top-crop-tendance','Top court moderne, disponible en plusieurs couleurs.',NULL,7.99,25.00,68,200,NULL,NULL,4.30,156,1200,1,0,NULL,1,'2026-04-02 10:44:44','2026-04-02 10:44:44',NULL),(3,2,'T-Shirt Oversize Homme','t-shirt-oversize-homme','T-shirt oversize streetwear, coton premium, coupe relaxée.',NULL,9.99,30.00,67,300,NULL,NULL,4.40,412,3100,1,0,NULL,1,'2026-04-02 10:44:44','2026-04-02 10:44:44',NULL),(4,3,'Montre Connectée Sport','montre-connectee-sport','Smartwatch avec suivi fitness, GPS, notifications. Étanche 5ATM.',NULL,29.99,89.99,67,77,NULL,NULL,4.20,589,4203,1,1,NULL,1,'2026-04-02 10:44:44','2026-04-14 08:04:56',NULL),(5,3,'Écouteurs Bluetooth Sans Fil','ecouteurs-bluetooth-sans-fil','True wireless earbuds, autonomie 30h, réduction de bruit active.',NULL,19.99,79.99,75,120,NULL,NULL,4.60,1023,8900,1,1,NULL,1,'2026-04-02 10:44:44','2026-04-02 10:44:44',NULL),(6,4,'Lampe LED Décorative','lampe-led-decorative','Lampe de bureau LED tactile, 3 températures, USB-C.',NULL,14.99,39.99,63,89,NULL,NULL,4.10,234,1801,0,0,NULL,1,'2026-04-02 10:44:44','2026-04-13 03:59:25',NULL),(7,4,'Coussin Décoratif Velours','coussin-decoratif-velours','Coussin velours doux, 45x45cm, housse amovible lavable.',NULL,6.99,19.99,65,250,NULL,NULL,4.30,178,2300,0,0,NULL,1,'2026-04-02 10:44:44','2026-04-02 10:44:44',NULL),(8,5,'Palette Maquillage 36 Couleurs','palette-maquillage-36-couleurs','Palette de fards à paupières longue tenue, pigments intenses.',NULL,8.99,35.00,74,180,NULL,NULL,4.50,892,6700,1,1,NULL,1,'2026-04-02 10:44:44','2026-04-02 10:44:44',NULL),(9,5,'Sérum Vitamine C Anti-âge','serum-vitamine-c-anti-age','Sérum concentré 20% Vit. C + acide hyaluronique.',NULL,11.99,49.99,76,140,NULL,NULL,4.40,445,3200,0,0,NULL,1,'2026-04-02 10:44:44','2026-04-02 10:44:44',NULL),(10,6,'Tapis de Yoga Antidérapant','tapis-yoga-antiderapant','Tapis yoga 183x61cm, épaisseur 6mm, matière écologique.',NULL,13.99,49.99,72,100,NULL,NULL,4.70,567,4100,0,0,NULL,1,'2026-04-02 10:44:44','2026-04-02 10:44:44',NULL),(11,7,'Lego Créatif 500 pièces','lego-creatif-500-pieces','Set de construction créatif 500 pièces, compatible LEGO.',NULL,16.99,55.00,69,60,NULL,NULL,4.60,234,1500,1,0,NULL,1,'2026-04-02 10:44:44','2026-04-02 10:44:44',NULL),(12,8,'Bracelet Perles Bohème','bracelet-perles-boheme','Bracelet artisanal perles naturelles, réglable, fait main.',NULL,4.99,15.99,69,500,NULL,NULL,4.20,189,3400,0,0,NULL,1,'2026-04-02 10:44:44','2026-04-02 10:44:44',NULL),(13,5,'sy','sy','ERSDTFHGJHKGJFHDGSF',NULL,233.00,34545.00,5,12,NULL,'parfum',0.00,0,0,0,0,NULL,1,'2026-04-15 09:14:01','2026-04-15 09:30:51','http://localhost/temu-clone/assets/images/uploads/videos/vid_69dfbd3ba69ec.mp4');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `helpful_count` int(11) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role` enum('client','admin') DEFAULT 'client',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Temu','admin@temu-clone.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'admin',1,'2026-04-02 10:44:43','2026-04-02 10:44:43'),(2,'Jean Dupont','jean@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'client',1,'2026-04-02 10:44:43','2026-04-02 10:44:43'),(3,'Marie Martin','marie@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'client',1,'2026-04-02 10:44:43','2026-04-02 10:44:43'),(4,'ibrahima sy','ibrahimasy021@gmail.com','$2y$10$W9YBqTcApOLpz5AFBV481ukghPkgqbrOoA/HpziddrLrBJJxFWsT6',NULL,NULL,'client',1,'2026-04-02 11:01:43','2026-04-02 11:01:43'),(5,'mamour sarr','mamoursarr021@gmail.com','$2y$10$8IJ40y9DGk.F7W/b/c2YTeHFmRugCUcUPupaWhrS8ijENFvdleFH.',NULL,NULL,'client',1,'2026-04-13 03:30:39','2026-04-13 03:30:39');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (1,4,8,'2026-04-02 12:01:17');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-15  9:46:22
