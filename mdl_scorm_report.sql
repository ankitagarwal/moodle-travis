-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 09, 2011 at 10:10 AM
-- Server version: 5.1.36
-- PHP Version: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `moodle`
--

-- --------------------------------------------------------

--
-- Table structure for table `mdl_scorm_report`
--

CREATE TABLE IF NOT EXISTS `mdl_scorm_report` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `displayorder` bigint(10) unsigned NOT NULL,
  `lastcron` bigint(10) unsigned NOT NULL DEFAULT '0',
  `cron` bigint(10) unsigned NOT NULL DEFAULT '0',
  `capability` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Lists all the installed quiz reports and their display order' AUTO_INCREMENT=6 ;
