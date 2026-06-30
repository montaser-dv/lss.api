-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 30, 2026 at 07:33 AM
-- Server version: 10.11.16-MariaDB-ubu2404
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trak_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `domains`
--

CREATE TABLE `domains` (
  `ID` int(25) NOT NULL,
  `Domain` varchar(30) NOT NULL,
  `Sub_Domain` varchar(30) NOT NULL,
  `DB_Name` varchar(50) NOT NULL,
  `DB_User` varchar(25) NOT NULL,
  `DB_Pass` varchar(35) NOT NULL,
  `Token` varchar(100) NOT NULL,
  `c_code` int(11) NOT NULL,
  `expire_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `domains`
--

INSERT INTO `domains` (`ID`, `Domain`, `Sub_Domain`, `DB_Name`, `DB_User`, `DB_Pass`, `Token`, `c_code`, `expire_date`) VALUES
(1, 'trakmile.com', 'demo', 'demo_trakmile_db', 'demo_tr_user', 'pr6qAxbr2WrDTJ', 'FxLo5ptqfuXanaCc65ycYNrK_cNGnyYDhasG0jhgGcTtbNs7uO-ViOSbn3DnOy05BsldRfbu3j_ZHZJwR23BU3g', 101, '2024-11-23 09:37:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `domains`
--
ALTER TABLE `domains`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `domains`
--
ALTER TABLE `domains`
  MODIFY `ID` int(25) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
