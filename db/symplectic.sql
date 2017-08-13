/*
 Navicat MySQL Data Transfer

 Source Server         : hana.oii.ox.ac.uk
 Source Server Type    : MySQL
 Source Server Version : 50554
 Source Host           : localhost
 Source Database       : oii_symplectic_dev

 Target Server Type    : MySQL
 Target Server Version : 50554
 File Encoding         : utf-8

 Date: 08/13/2017 22:21:48 PM
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `author`
-- ----------------------------
DROP TABLE IF EXISTS `author`;
CREATE TABLE `author` (
  `id` int(11) NOT NULL,
  `proprietary-id` text CHARACTER SET utf8mb4,
  `username` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `initials` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `lastname` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `academic` tinyint(4) DEFAULT NULL,
  `currentstaff` tinyint(4) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `synched` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `author_publication`
-- ----------------------------
DROP TABLE IF EXISTS `author_publication`;
CREATE TABLE `author_publication` (
  `author` int(11) NOT NULL,
  `publication` int(11) NOT NULL,
  `visible` tinyint(1) DEFAULT '0',
  `favourite` tinyint(1) DEFAULT '0',
  `synched` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`author`,`publication`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `log`
-- ----------------------------
DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `description` longtext,
  `tags` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `publication`
-- ----------------------------
DROP TABLE IF EXISTS `publication`;
CREATE TABLE `publication` (
  `id` int(11) NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4,
  `published` date DEFAULT NULL,
  `citation` text COLLATE utf8mb4_unicode_ci,
  `abstract` longtext CHARACTER SET utf8mb4,
  `doi` text CHARACTER SET utf8mb4,
  `status` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `labels` text CHARACTER SET utf8mb4,
  `keywords` text CHARACTER SET utf8mb4,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `synched` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `publication_tag`
-- ----------------------------
DROP TABLE IF EXISTS `publication_tag`;
CREATE TABLE `publication_tag` (
  `publication` int(11) NOT NULL,
  `tag` int(11) NOT NULL,
  `synched` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tag`,`publication`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `tag`
-- ----------------------------
DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `synched` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `type`
-- ----------------------------
DROP TABLE IF EXISTS `type`;
CREATE TABLE `type` (
  `id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `singular` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `plural` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `fields` text COLLATE utf8mb4_unicode_ci,
  `synched` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPACT;

SET FOREIGN_KEY_CHECKS = 1;
