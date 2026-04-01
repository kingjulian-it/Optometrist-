-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 04:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `optometris_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cawangan`
--

CREATE TABLE `cawangan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cawangan`
--

INSERT INTO `cawangan` (`id`, `nama`, `alamat`, `telefon`, `created_at`) VALUES
(5, 'SHAHARUDIN OPTOMETRIS', 'NO. 23 C , JALAN KAMPUNG BARU , 08000 , SUNGAI PETANI , KEDAH', '019-9487848', '2026-03-19 03:46:04'),
(6, 'OPTOMETRIS WAHIDA', 'LOT 39 , PLOT 21 , CMART BDI , 06000 JITRA , KEDAH', '019-4746648', '2026-03-19 03:56:49'),
(7, 'EYEMASTER OPTOMETRIST', '1589 , WISMA DTC , JALAN SULTAN BADLISHAH , 05000 ALOR SETAR , KEDAH', '019-3347638', '2026-03-19 03:59:15'),
(8, 'AMEERA OPTOMETRIST', 'NO. 17 PERSIARAN PENDANG SQUARE 1 , 06700 , PENDANG', '013-334 7848', '2026-03-19 04:01:23');

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `session_data` text DEFAULT NULL,
  `last_message` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `janji`
--

CREATE TABLE `janji` (
  `id` int(11) NOT NULL,
  `pelanggan` varchar(100) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ic` varchar(20) DEFAULT NULL,
  `cawangan_id` int(11) DEFAULT NULL,
  `staf_id` int(11) DEFAULT NULL,
  `tarikh` date DEFAULT NULL,
  `masa` time DEFAULT NULL,
  `jenis_layanan` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `source` varchar(20) DEFAULT 'web',
  `reminder_sent` tinyint(1) DEFAULT 0,
  `reminder_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `pelanggan_id` int(11) DEFAULT NULL,
  `r_sph` varchar(10) DEFAULT NULL,
  `r_cyl` varchar(10) DEFAULT NULL,
  `r_axis` varchar(10) DEFAULT NULL,
  `l_sph` varchar(10) DEFAULT NULL,
  `l_cyl` varchar(10) DEFAULT NULL,
  `l_axis` varchar(10) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `telefon` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`id`, `nama`, `telefon`, `email`, `created_at`) VALUES
(1, 'HAZIQ BIN ADNAN', '01151399115', 'haziqadnan405@gmail.com', '2026-03-28 12:27:03');

-- --------------------------------------------------------

--
-- Table structure for table `preskripsi`
--

CREATE TABLE `preskripsi` (
  `id` int(11) NOT NULL,
  `pelanggan_id` int(11) NOT NULL,
  `sph_od` varchar(10) DEFAULT NULL,
  `cyl_od` varchar(10) DEFAULT NULL,
  `axis_od` varchar(5) DEFAULT NULL,
  `add_od` varchar(10) DEFAULT NULL,
  `sph_os` varchar(10) DEFAULT NULL,
  `cyl_os` varchar(10) DEFAULT NULL,
  `axis_os` varchar(5) DEFAULT NULL,
  `add_os` varchar(10) DEFAULT NULL,
  `jenis_cermin` varchar(50) DEFAULT NULL,
  `tarikh_preskripsi` date NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `preskripsi`
--

INSERT INTO `preskripsi` (`id`, `pelanggan_id`, `sph_od`, `cyl_od`, `axis_od`, `add_od`, `sph_os`, `cyl_os`, `axis_os`, `add_os`, `jenis_cermin`, `tarikh_preskripsi`, `catatan`, `created_at`) VALUES
(1, 1, '', '', '', '', '', '', '', '', 'Single Vision', '2026-03-28', '', '2026-03-28 12:27:03'),
(2, 1, '', '', '', '', '', '', '', '', 'Reading', '2026-03-28', '', '2026-03-28 12:27:12'),
(3, 1, '', '', '', '', '', '', '', '', 'Single Vision', '2026-04-03', '', '2026-03-28 12:32:40');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `janji_id` int(11) DEFAULT NULL,
  `pelanggan` varchar(100) DEFAULT NULL,
  `staf_id` int(11) DEFAULT NULL,
  `cawangan_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staf`
--

CREATE TABLE `staf` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `cawangan_id` int(11) DEFAULT NULL,
  `peranan` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staf`
--

INSERT INTO `staf` (`id`, `nama`, `cawangan_id`, `peranan`, `password`, `created_at`) VALUES
(6, 'PN. ERNI NORLIANA', 5, 'Optometris', 'ERNI123', '2026-03-19 03:47:40'),
(7, 'NURUL NABIHAH ', 5, 'Optometris', 'NAB123', '2026-03-19 03:48:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cawangan`
--
ALTER TABLE `cawangan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `janji`
--
ALTER TABLE `janji`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cawangan_id` (`cawangan_id`),
  ADD KEY `staf_id` (`staf_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `preskripsi`
--
ALTER TABLE `preskripsi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pelanggan_id` (`pelanggan_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `janji_id` (`janji_id`),
  ADD KEY `staf_id` (`staf_id`),
  ADD KEY `cawangan_id` (`cawangan_id`);

--
-- Indexes for table `staf`
--
ALTER TABLE `staf`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cawangan_id` (`cawangan_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cawangan`
--
ALTER TABLE `cawangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `janji`
--
ALTER TABLE `janji`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `preskripsi`
--
ALTER TABLE `preskripsi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staf`
--
ALTER TABLE `staf`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `janji`
--
ALTER TABLE `janji`
  ADD CONSTRAINT `janji_ibfk_1` FOREIGN KEY (`cawangan_id`) REFERENCES `cawangan` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `janji_ibfk_2` FOREIGN KEY (`staf_id`) REFERENCES `staf` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `preskripsi`
--
ALTER TABLE `preskripsi`
  ADD CONSTRAINT `preskripsi_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`janji_id`) REFERENCES `janji` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`staf_id`) REFERENCES `staf` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`cawangan_id`) REFERENCES `cawangan` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staf`
--
ALTER TABLE `staf`
  ADD CONSTRAINT `staf_ibfk_1` FOREIGN KEY (`cawangan_id`) REFERENCES `cawangan` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
