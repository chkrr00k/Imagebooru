-- phpMyAdmin SQL Dump
-- version 4.1.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Ago 23, 2017 alle 17:54
-- Versione del server: 5.6.33-log
-- PHP Version: 5.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `my_octavia`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `BANNED`
--

CREATE TABLE IF NOT EXISTS `BANNED` (
  `IP` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`IP`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `IMAGE`
--

CREATE TABLE IF NOT EXISTS `IMAGE` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `SRC` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `DESC` text COLLATE utf8mb4_unicode_ci,
  `UPLOADER` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `HASH` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `HASH` (`HASH`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=21 ;

--
-- Struttura della tabella `MODS`
--

CREATE TABLE IF NOT EXISTS `MODS` (
  `USER` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `PASS` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`USER`,`PASS`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Struttura della tabella `REL`
--

CREATE TABLE IF NOT EXISTS `REL` (
  `ID` int(11) NOT NULL,
  `NAME` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`ID`,`NAME`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Struttura della tabella `TAG`
--

CREATE TABLE IF NOT EXISTS `TAG` (
  `NAME` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `DESC` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`NAME`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
