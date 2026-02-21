-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 21, 2026 at 01:12 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `schema_normalized`
--

-- --------------------------------------------------------

--
-- Table structure for table `cuti`
--

CREATE TABLE `cuti` (
  `id_cuti` int NOT NULL,
  `jenis_cuti` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cuti`
--

INSERT INTO `cuti` (`id_cuti`, `jenis_cuti`) VALUES
(6, 'Cuti Alasan Penting'),
(2, 'Cuti Bersama'),
(4, 'Cuti Besar'),
(9, 'Cuti di Luar Tanggungan Negara'),
(5, 'Cuti Melahirkan'),
(7, 'Cuti Sakit'),
(1, 'Cuti Tahunan'),
(3, 'Cuti Tahunan Pengganti Cuti Bersama'),
(8, 'Cuti Tambahan');

-- --------------------------------------------------------

--
-- Table structure for table `detail_cuti`
--

CREATE TABLE `detail_cuti` (
  `id_dc` int NOT NULL,
  `nip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_cuti` int NOT NULL,
  `alasan` text COLLATE utf8mb4_unicode_ci,
  `lama_cuti` int DEFAULT NULL,
  `stgh_hari` tinyint(1) NOT NULL DEFAULT '0',
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detail_cuti`
--

INSERT INTO `detail_cuti` (`id_dc`, `nip`, `id_cuti`, `alasan`, `lama_cuti`, `stgh_hari`, `tgl_mulai`, `tgl_selesai`) VALUES
(25, '1897', 1, 'Liburan', 3, 0, '2025-12-17', '2025-12-20'),
(26, '1762', 7, 'Demam', 4, 0, '2025-12-15', '2025-12-19'),
(27, '2002', 1, 'Natal', 7, 0, '2025-12-22', '2025-12-29');

-- --------------------------------------------------------

--
-- Table structure for table `detail_st`
--

CREATE TABLE `detail_st` (
  `id_dst` int NOT NULL,
  `id_st` int NOT NULL,
  `nip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor_st` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_st` date NOT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `lokasi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `perihal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spd` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detail_st`
--

INSERT INTO `detail_st` (`id_dst`, `id_st`, `nip`, `nomor_st`, `tgl_st`, `tgl_mulai`, `tgl_selesai`, `lokasi`, `perihal`, `spd`) VALUES
(11, 1, '1991', '2005/01/ST', '2025-12-17', '2025-12-17', '2025-12-19', 'Palembang', 'Pendidikan', 'Ya'),
(12, 1, '1762', '13/2020/ST', '2025-12-05', '2025-12-08', '2025-12-10', 'Jakarta', 'Rapat Bersama', 'Ya'),
(13, 1, '1897', '14/2020/ST', '2025-12-19', '2025-12-22', '2025-12-24', 'Banyuasin', 'Audiensi', 'Ya');

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `nip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `golongan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bagian` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`nip`, `nama`, `golongan`, `jabatan`, `bagian`, `status_aktif`) VALUES
('1762', 'Anisa', 'II/C', 'Staf', 'Penindakan dan Penyidikan', 1),
('1897', 'Randi', 'III/A', 'Staf', 'Kepatuhan Internal', 1),
('1991', 'Ahmad', 'IV/B', 'Kepala Bagian', 'Umum', 1),
('2002', 'Aldi', 'III/B', 'Kepala Sub Bagian', 'Umum', 1),
('3902', 'Yadi', 'IV/B', 'Kepala Sub Bagian', 'Penindakan dan Penyidikan', 1);

-- --------------------------------------------------------

--
-- Table structure for table `presensi`
--

CREATE TABLE `presensi` (
  `id_presensi` int NOT NULL,
  `nip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL,
  `status` enum('HADIR','CUTI','ST','SAKIT','IZIN','WFH') COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_cuti` int DEFAULT NULL,
  `id_st` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `presensi`
--

INSERT INTO `presensi` (`id_presensi`, `nip`, `timestamp`, `status`, `id_cuti`, `id_st`) VALUES
(68, '1991', '2025-12-22 02:58:36', 'HADIR', NULL, NULL),
(70, '3902', '2025-12-22 02:58:36', 'HADIR', NULL, NULL),
(73, '1991', '2025-12-22 12:37:16', 'ST', NULL, 1),
(74, '2002', '2025-12-22 05:38:18', 'CUTI', 1, NULL),
(75, '1762', '2025-12-22 05:39:44', 'ST', NULL, 1),
(76, '1897', '2025-12-22 05:40:44', 'ST', NULL, 1),
(77, '1762', '2026-02-21 13:05:05', 'HADIR', NULL, NULL),
(78, '1897', '2026-02-21 13:05:05', 'HADIR', NULL, NULL),
(79, '1991', '2026-02-21 13:05:05', 'HADIR', NULL, NULL),
(80, '2002', '2026-02-21 13:05:05', 'HADIR', NULL, NULL),
(81, '3902', '2026-02-21 13:05:05', 'HADIR', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `st`
--

CREATE TABLE `st` (
  `id_st` int NOT NULL,
  `jenis_st` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `st`
--

INSERT INTO `st` (`id_st`, `jenis_st`) VALUES
(2, '2025-12-19'),
(1, 'Surat Tugas Umum');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','user') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', 'admin123', 'admin'),
(2, 'user', 'user123', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cuti`
--
ALTER TABLE `cuti`
  ADD PRIMARY KEY (`id_cuti`),
  ADD UNIQUE KEY `uniq_jenis_cuti` (`jenis_cuti`);

--
-- Indexes for table `detail_cuti`
--
ALTER TABLE `detail_cuti`
  ADD PRIMARY KEY (`id_dc`),
  ADD KEY `fk_dc_cuti` (`id_cuti`),
  ADD KEY `idx_dc_nip` (`nip`),
  ADD KEY `idx_dc_tgl` (`tgl_mulai`,`tgl_selesai`);

--
-- Indexes for table `detail_st`
--
ALTER TABLE `detail_st`
  ADD PRIMARY KEY (`id_dst`),
  ADD KEY `fk_dst_st` (`id_st`),
  ADD KEY `idx_dst_nip` (`nip`),
  ADD KEY `idx_dst_tgl` (`tgl_st`,`tgl_mulai`,`tgl_selesai`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`nip`);

--
-- Indexes for table `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id_presensi`),
  ADD KEY `fk_presensi_cuti` (`id_cuti`),
  ADD KEY `fk_presensi_st` (`id_st`),
  ADD KEY `idx_presensi_nip` (`nip`),
  ADD KEY `idx_presensi_ts` (`timestamp`),
  ADD KEY `idx_presensi_status` (`status`);

--
-- Indexes for table `st`
--
ALTER TABLE `st`
  ADD PRIMARY KEY (`id_st`),
  ADD UNIQUE KEY `uniq_jenis_st` (`jenis_st`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cuti`
--
ALTER TABLE `cuti`
  MODIFY `id_cuti` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `detail_cuti`
--
ALTER TABLE `detail_cuti`
  MODIFY `id_dc` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `detail_st`
--
ALTER TABLE `detail_st`
  MODIFY `id_dst` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id_presensi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `st`
--
ALTER TABLE `st`
  MODIFY `id_st` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_cuti`
--
ALTER TABLE `detail_cuti`
  ADD CONSTRAINT `fk_dc_cuti` FOREIGN KEY (`id_cuti`) REFERENCES `cuti` (`id_cuti`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dc_karyawan` FOREIGN KEY (`nip`) REFERENCES `karyawan` (`nip`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `detail_st`
--
ALTER TABLE `detail_st`
  ADD CONSTRAINT `fk_dst_karyawan` FOREIGN KEY (`nip`) REFERENCES `karyawan` (`nip`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dst_st` FOREIGN KEY (`id_st`) REFERENCES `st` (`id_st`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `fk_presensi_cuti` FOREIGN KEY (`id_cuti`) REFERENCES `cuti` (`id_cuti`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presensi_karyawan` FOREIGN KEY (`nip`) REFERENCES `karyawan` (`nip`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_presensi_st` FOREIGN KEY (`id_st`) REFERENCES `st` (`id_st`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
