-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 12, 2026 at 04:26 PM
-- Server version: 10.11.16-MariaDB-cll-lve
-- PHP Version: 8.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ergm_akademik`
--

-- --------------------------------------------------------

--
-- Table structure for table `klien`
--

CREATE TABLE `klien` (
  `id_klien` int(11) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `foto_klien` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `institusi` varchar(100) DEFAULT NULL,
  `fakultas` varchar(100) DEFAULT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `judul_penelitian` text DEFAULT NULL,
  `tanggal_daftar` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `klien`
--

INSERT INTO `klien` (`id_klien`, `nama_lengkap`, `foto_klien`, `alamat`, `tanggal_masuk`, `no_telp`, `password`, `institusi`, `fakultas`, `program_studi`, `judul_penelitian`, `tanggal_daftar`) VALUES
(1, 'DEDY AHMADI RAMBE', NULL, 'Jl Wadassari 2, Gang Arjuna 3, No 80 Rt 02/05 Pondok Betung Tangerang Selatan', NULL, '08778128492', NULL, 'Universitas Trisakti', 'Ekonomi dan Bisnis', 'Magister Akutansi', 'Pengaruh manajemen laba dan tata kelola perusahaan terhadap agresivitas pajak dan kualitas audit sebagai variable moderasi dan ukuran perusahaan sebagai variable kontrol', '2026-06-02'),
(2, 'RAFIL ARIZONA', NULL, 'Yogyakarta', NULL, '085831265688', NULL, '', '', '', 'Hydrodynamic phenomena in gas-liquid microchannel flows with sudden geometry changesb to optimize flow characteristics and mass transfer: A comprehensive research review', '2026-06-02'),
(3, 'FITRIANI ZUHROTIN', NULL, 'KOMP. NUSA HIJAU CIMAHI', NULL, '081288352341', NULL, 'UNIVERSITAS JENDRAL ACHMAD YANI', '', 'MAGISTER MANAJEMEN TEKNOLOGI', 'PENGARUH KEPEMIMPINAN DIGITAL, PENGGUNAAN MEDIA SOSIAL DAN MANAJEMEN TALENTA BERBASIS DIGITAL TERHADAP RETENSI KARYAWAN', '2026-06-01'),
(4, 'MEIDINA NURMAYANI', NULL, 'Jl Murhadi No 3 Lembang, Kabupaten Bandung Barat', NULL, '085183280251', NULL, '', '', '', '', '2026-06-03'),
(5, 'FITRIANI ZUHROTIN', NULL, 'Komplek Nusa Hijau Cimahi', NULL, '081288352341', NULL, '', '', '', '', '2026-06-01'),
(6, 'Dr. Hasman Budiadi, SE., MM', NULL, 'Jl. Samba AG 5 Grogol Indah, Grogol,  Sukoharjo', NULL, '082135007575', NULL, '', '', '', '', '2026-06-03'),
(7, 'FELIX BENAYA', NULL, 'Gubeng Surabaya', NULL, '0813-2595-2001', NULL, 'ITS SEPULUH NOVEMBER', 'INFORMATIKA', 'PEMBUATAN JURNAL & POSTER', '', '2026-06-03'),
(8, 'RANDY RESTIANDA', NULL, 'CLUSTER GRIYA MUTIARA NO. 2 JL. H CEPIT KP. SAWAH RT 03/02 JATIMULYA DEPOK', NULL, '085669054565', NULL, '', '', '', 'Smart Green Construction: Business Plan Pengembangan Jasa Konstruksi Berbasis Super Apps dan Green Construction di Indonesia', '2026-06-04'),
(9, 'IBU LENNY MEILANY', NULL, 'JL. JUPITER SELATAN II NO. 10 MARGAHAYU RAYA BANDUNG', NULL, '087788352547', NULL, 'UNIVERSITAS PADJAJARAN', 'ILMU SOSIAL DAN ILMU POLITIK', 'PASCA SARJANA', 'IMPLEMENTASI BANGKOK RULES DI INDONESIA', '2026-06-04'),
(10, 'EMA SURYA PERTIWI ( ABAH )', NULL, 'Jakarta', NULL, '085749621999', NULL, 'Universitas MH Thamrin', 'FAKULTAS KESEHATAN', 'Magister Kesehatan Masyarakat', 'STRES, DUKUNGAN SOSIAL DAN RESILIENSI SEBAGAI PREDIKTOR DEPRESI PADA ISTRI PRAJURIT TNI AD', '2026-06-04'),
(11, 'Luqman Ash Shiddiqie', NULL, 'Depok Maharaja Blok O4/18 Kota Depok Jabar', NULL, '0811818396', NULL, 'Universitas Indonesia', '', 'Magister Ilmu Lingkungan', 'Optimasi pengelolaan sampah di kawasan wisata satwa', '2026-06-04'),
(12, 'ARINI MULIANA ( ABAH )', NULL, 'Jakarta', NULL, '082352259090', NULL, 'Universitas MH Thamrin', 'Kesehatan', 'Magister kesehatan masyarakat Jakarta', 'Faktor resiko stres kerja tenaga kesehatan puskesmas di kabupaten tanggerang tahun 2026', '2026-06-05'),
(13, 'EGI PATNIALDI IRDA PERMANA ( ABAH )', NULL, 'Jakarta', NULL, '081315244832', NULL, 'Universitas MH Tamrin', 'Kesehatan', 'Magister Kesehatan Masyarakat Jakarta', 'Hubungan posisi duduk dan lama duduk dengan kejadian Low Back pain pada Tenaga Kesehatan di Puskesmas kota tanggerang tahun 2026', '2026-06-05'),
(14, 'ARIS KRISTIAWAN', NULL, 'Sleman, Yogyakarta', NULL, '082269996588', NULL, 'UNIVERSITAS KRISTEN IMMANUEL YOGYAKARTA', 'Ekonomi dan Bisnis', 'Manajemen', 'Pengaruh kualitas pelayanan dan persepsi harga terhadap loyalitas pelanggan melalui kepuasan konsumen pada barbershop ter the cute yogyakarta', '2026-06-05'),
(15, 'Salwa Fadila Usmayanti', NULL, 'Cisaat, Jl Waspada Rt 1/9 Desa Sukamanah Bayongbong Garut', NULL, '08222861061', NULL, 'Universitas Jendral Achmad Yani', 'Kedokteran', 'Kedokteran Tahap Sarjana', 'Pemetaan spesial kelimpahan nyamuk vektor di lanskap botani cimahi utara sebagai dasar surveilans vektor penyakit', '2026-06-07'),
(16, 'Abdul Qodir', NULL, 'Asrama Wali Songo (AWS), Jalan Kompos No. 19,   RT.11/RW.8, Lenteng Agung, Jagakarsa, DKI Jakarta', NULL, '085217956006', NULL, 'Universitas Indonesia', '', 'Bisnis Kreatif', 'Evaluasi Desain E-Catalog Menggunakan Pendekatan Usability pada Minat Pembelian Konsumen di UMKM Derryl’s Crochet', '2026-06-07'),
(17, 'PIPIT', NULL, '', NULL, '087874906734', NULL, '', '', '', 'Pengaruh ekosistem ekonomi halal, foreign direct investment dan goverment effectieness terhadap PDB negara-negara oki tahun 2015-2023 dengan moderasi IPM', '2026-06-07'),
(18, 'ARIFIN', NULL, 'Jalan Martasik nomor 21 RT 03 RW 09 Kelurahan   Cipageran, Kecamatn Cimahi Utara, Kota Cimahi', NULL, '081325710190', NULL, 'Universitas Pasundan', '', 'Magister administrasi', 'Implementasi kebijakan sertipikat elektronik di kantor pertahanan kabupaten Bekasi', '2026-06-09'),
(19, 'NITA HARTINI', NULL, 'Jl. Matrapersada No. 56 Gunungbatu Cimahi', NULL, '081294050711', NULL, '', '', '', '', '2026-06-09'),
(20, 'MURSALIM', NULL, 'Desa Kiabu, Kecamatan Siantan Selatan, Kabupaten,     Kepulauan Anambas, Propinsi Kepulauan Riau.', NULL, '085320394920', NULL, 'UNIVERSITAS TAZKIA', 'EKONOMI DAN BISNIS SYARIAH', 'MAGISTER EKONOMI SYARIAH', 'Penyesuaian nilai pariwisata ramah muslim pada operasional hotel syariah dan keberlanjutan sosial ekonomi masyarakat pesisir', '2026-06-10'),
(21, 'RISKA BAINI NURSANTI', NULL, 'Citraland cibubur blok e5 no 36 cluster monteverde      livistona cileungsi, kab. Bogor', NULL, '082219274446', NULL, 'Universitas Widyatama', '', 'MAGISTER AKUNTANSI', 'PENGARUH MANAJEMEN LABA AKRUAL, MANAJEMEN LABA RIIL, DAN GOVERNANSI KORPORAT TERHADAP NILAI PERUSAHAAN DENGAN KINERJA KEUANGAN SEBAGAI VARIABEL MEDIASI\r\nStudi di Perusahaan Manufaktur Tahun 2021-2025', '2026-06-10'),
(22, 'NICO PERMADI', NULL, '', NULL, '0888019381', NULL, '', '', '', '', '2026-06-10'),
(23, 'LUIS LIE', NULL, '', NULL, '087734141188', NULL, '', '', '', '', '2026-06-11'),
(24, 'CLARESTA SUKMA RAMADHANI', NULL, '', NULL, '081282571561', NULL, 'UIN', 'ILMU TARBIYAH DAN KEGURUAN UIN', 'PENDIDIKAN MATEMATIKA', 'Pengaruh model pembelajaran discovery learning berbantuan LKPD terhadap kemampuan pemecahan masalah matematis siswa SMP', '2026-06-11'),
(25, 'INDRA RAHARJA', NULL, 'BATAM', NULL, '08126106633', NULL, '', '', '', '', '2026-06-12'),
(26, 'MUH AZMI NUGRAHA', NULL, 'Jl Cihanjuang Rt 3/7 Cihanjuang Parongpong Bandung     Barat', NULL, '081380554190', NULL, 'UNIVERSITAS PENDIDIKAN INDONESIA', '', 'S1 TEKNIK KOMPUTER', 'RANCANG BANGUN SISTEM PENYORTIRAN PAKET DENGAN CONVEYOR MENGGUNAKAN BARCODE BERBASIS INTERNET OF THINGS', '2026-06-12');

-- --------------------------------------------------------

--
-- Table structure for table `milestone_chat`
--

CREATE TABLE `milestone_chat` (
  `id_chat` int(11) NOT NULL,
  `id_milestone` int(11) NOT NULL,
  `id_pengirim` int(11) NOT NULL,
  `role_pengirim` enum('Staff','Klien') NOT NULL,
  `pesan` text NOT NULL,
  `file_lampiran` varchar(255) DEFAULT NULL,
  `waktu_kirim` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `milestone_chat`
--

INSERT INTO `milestone_chat` (`id_chat`, `id_milestone`, `id_pengirim`, `role_pengirim`, `pesan`, `file_lampiran`, `waktu_kirim`) VALUES
(1, 1, 2, 'Staff', 'ini contohnya mas bab 1', NULL, '2026-07-11 20:12:25'),
(2, 1, 24, 'Klien', 'baik kak, itu bab 1 tolong ada bagian headline yang typo. diperbaiki!', NULL, '2026-07-11 20:36:21'),
(3, 1, 2, 'Staff', 'ini mas', 'lampiran_9f3dd6e69c23aa76.png', '2026-07-11 20:49:06'),
(4, 1, 24, 'Klien', 'Maksud saya seperti ini, tapi gpp. Itu udah sesuai dosen pembimbing saya', 'lampiran_9abb1700934197e4.jpeg', '2026-07-12 05:44:58'),
(5, 2, 24, 'Klien', 'saya ingin melihat progress bab 2 nya kak. Sudah sejauh mana?', NULL, '2026-07-12 05:45:55'),
(6, 3, 24, 'Klien', 'sama ini bab akhir, apakah sudah ada gambarannya?', NULL, '2026-07-12 05:46:12'),
(7, 1, 24, 'Klien', '⚠️ *Pemberitahuan Sistem:* Klien mengajukan pembukaan ulang ruang diskusi untuk revisi tambahan.', NULL, '2026-07-12 06:32:30'),
(8, 1, 24, 'Klien', 'halo kak', NULL, '2026-07-12 06:45:30'),
(9, 1, 24, 'Klien', '⚠️ *Pemberitahuan Sistem:* Klien mengajukan pembukaan ulang ruang diskusi. Menunggu persetujuan Staff.', NULL, '2026-07-12 06:47:05'),
(10, 2, 2, 'Staff', 'baik kak , ini masih dibuat di microsoft word.', NULL, '2026-07-12 06:49:45'),
(11, 2, 2, 'Staff', 'siap, tolong untuk progress bab 2 harus selesai nanti akhir minggu ini', NULL, '2026-07-12 07:06:11'),
(12, 2, 24, 'Klien', 'ga perlu akhir minggu, yang penting sesuai dengan pedoman dosen saya yg kmren filenya dikirim ya kak :)', NULL, '2026-07-12 07:09:45'),
(13, 1, 2, 'Staff', 'heloooooooooooo kakakkkkkkkkkkkkk', NULL, '2026-07-12 07:15:55'),
(14, 1, 24, 'Klien', 'helooo', NULL, '2026-07-12 07:19:15'),
(15, 1, 24, 'Klien', 'sudah selesai?', NULL, '2026-07-12 07:19:37'),
(16, 1, 2, 'Staff', 'sedang dikerjakan kak', NULL, '2026-07-12 07:20:29'),
(17, 3, 24, 'Klien', '⚠️ *Pemberitahuan Sistem:* Klien mengajukan pembukaan ulang ruang diskusi. Menunggu persetujuan Staff.', NULL, '2026-07-12 07:21:59'),
(18, 3, 24, 'Klien', '⚠️ *Pemberitahuan Sistem:* Klien mengajukan pembukaan ulang ruang diskusi. Menunggu persetujuan Staff.', NULL, '2026-07-12 07:24:46'),
(19, 2, 2, 'Staff', 'saya kirim hasil revisi, mudah\"an sesuai', 'lampiran_85918cb083ea7bf3.webp', '2026-07-12 07:27:04'),
(20, 2, 24, 'Klien', 'baik kak saya cek dulu....', NULL, '2026-07-12 07:27:36'),
(21, 2, 24, 'Klien', 'Oke kak mantap terimakasih :)', NULL, '2026-07-12 07:27:39'),
(22, 2, 24, 'Klien', 'Sudah di acc dosen ya kak, big thankk buat tim LSI! hatur nuhun', NULL, '2026-07-12 07:28:08'),
(23, 2, 2, 'Staff', 'Ya sama\", jangan sungkan untuk membuka ruang diskusi terkait pengerjakannya ya kak. Izin saya update pengerjaan untuk Bab 2 ini ya kak :)', NULL, '2026-07-12 07:28:45');

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran_termin`
--

CREATE TABLE `pembayaran_termin` (
  `id_pembayaran` int(11) NOT NULL,
  `id_pesanan` int(11) DEFAULT NULL,
  `termin_1` bigint(20) DEFAULT 0,
  `tgl_termin_1` date DEFAULT NULL,
  `termin_2` bigint(20) DEFAULT 0,
  `tgl_termin_2` date DEFAULT NULL,
  `termin_3` bigint(20) DEFAULT 0,
  `tgl_termin_3` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pembayaran_termin`
--

INSERT INTO `pembayaran_termin` (`id_pembayaran`, `id_pesanan`, `termin_1`, `tgl_termin_1`, `termin_2`, `tgl_termin_2`, `termin_3`, `tgl_termin_3`) VALUES
(1, 1, 2800000, '2026-06-02', 1200000, '2026-06-30', 0, NULL),
(2, 2, 2500000, '2026-06-02', 0, '2026-07-09', 0, '2026-07-10'),
(4, 4, 1400000, '2026-06-03', 0, NULL, 0, NULL),
(5, 5, 2400000, '2026-06-01', 0, NULL, 0, NULL),
(6, 6, 6238050, '2026-06-03', 0, NULL, 0, NULL),
(7, 7, 2500000, '2026-06-03', 0, NULL, 0, NULL),
(8, 8, 3500000, '2026-06-04', 0, NULL, 0, NULL),
(9, 9, 4000000, '2026-06-04', 0, NULL, 0, NULL),
(10, 10, 1650000, '2026-06-04', 0, NULL, 0, NULL),
(11, 11, 4500000, '2026-06-04', 0, NULL, 0, NULL),
(12, 12, 1650000, '2026-06-05', 0, NULL, 0, NULL),
(13, 13, 0, NULL, 0, NULL, 0, NULL),
(14, 14, 2400000, '2026-06-05', 0, NULL, 0, NULL),
(15, 15, 2700000, '2026-06-07', 0, NULL, 0, NULL),
(16, 16, 2400000, '2026-06-07', 0, NULL, 0, NULL),
(17, 17, 1750000, '2026-06-07', 0, NULL, 0, NULL),
(18, 18, 5400000, '2026-06-09', 3600000, '2026-07-01', 0, NULL),
(19, 19, 4200000, '2026-06-09', 0, NULL, 0, NULL),
(20, 20, 2800000, '2026-06-09', 0, NULL, 0, NULL),
(21, 21, 2100000, '2026-06-10', 900000, '2026-06-27', 0, NULL),
(22, 22, 2400000, '2026-06-10', 0, NULL, 0, NULL),
(23, 23, 2700000, '2026-06-11', 0, NULL, 0, NULL),
(24, 24, 2700000, '2026-06-11', 0, NULL, 0, NULL),
(25, 25, 720000, '2026-06-12', 0, NULL, 0, NULL),
(26, 26, 2800000, '2026-06-12', 0, NULL, 0, NULL),
(29, 29, 150000, '2026-07-12', 0, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran_perusahaan`
--

CREATE TABLE `pengeluaran_perusahaan` (
  `id_pengeluaran` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jenis_pengeluaran` varchar(255) NOT NULL,
  `biaya` int(11) NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pengeluaran_perusahaan`
--

INSERT INTO `pengeluaran_perusahaan` (`id_pengeluaran`, `tanggal`, `jenis_pengeluaran`, `biaya`, `keterangan`) VALUES
(1, '2026-07-10', 'Beli Kertas', 150000, 'Kertas HVS 1 Rim');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan_layanan`
--

CREATE TABLE `pesanan_layanan` (
  `id_pesanan` int(11) NOT NULL,
  `id_klien` int(11) DEFAULT NULL,
  `jenis_layanan` varchar(150) NOT NULL,
  `nilai_dealing` bigint(20) NOT NULL,
  `deadline` date NOT NULL,
  `status_pelunasan` enum('Belum','Tertunda','Selesai') DEFAULT 'Belum',
  `file_mou` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pesanan_layanan`
--

INSERT INTO `pesanan_layanan` (`id_pesanan`, `id_klien`, `jenis_layanan`, `nilai_dealing`, `deadline`, `status_pelunasan`, `file_mou`) VALUES
(1, 1, 'Tesis', 4000000, '2026-07-16', 'Selesai', 'MoU_1_1783656104.pdf'),
(2, 2, 'Publikasi Jurnal', 2500000, '2026-07-31', 'Selesai', 'MoU_2_1783655624.pdf'),
(4, 4, 'Skripsi', 2000000, '2026-07-31', 'Tertunda', 'MoU_4_1783657111.pdf'),
(5, 5, 'Publikasi Jurnal', 4800000, '2026-07-31', 'Tertunda', 'MoU_5_1783660848.pdf'),
(6, 6, 'Publikasi Jurnal', 12476100, '2026-07-31', 'Tertunda', 'MoU_6_1783659272.pdf'),
(7, 7, 'Publikasi Jurnal', 2500000, '2026-07-31', 'Selesai', 'MoU_7_1783660875.pdf'),
(8, 8, 'Publikasi Jurnal', 8500000, '2026-07-31', 'Tertunda', 'MoU_8_1783662390.pdf'),
(9, 9, 'Disertasi', 8000000, '2026-07-31', 'Tertunda', 'MoU_9_1783662428.pdf'),
(10, 10, 'Tesis', 5500000, '2026-07-31', 'Tertunda', NULL),
(11, 11, 'Tesis', 7500000, '2026-07-31', 'Tertunda', 'MoU_11_1783665567.pdf'),
(12, 12, 'Tesis', 5500000, '2026-07-31', 'Tertunda', NULL),
(13, 13, 'Tesis', 5500000, '2026-07-31', 'Tertunda', NULL),
(14, 14, 'Skripsi', 4000000, '2026-07-31', 'Tertunda', 'MoU_14_1783668182.pdf'),
(15, 15, 'Skripsi', 4500000, '2026-07-31', 'Tertunda', 'MoU_15_1783669958.pdf'),
(16, 16, 'Skripsi', 4000000, '2026-07-31', 'Tertunda', 'MoU_16_1783671279.pdf'),
(17, 17, 'Skripsi', 1750000, '2026-07-31', 'Selesai', NULL),
(18, 18, 'Tesis', 9000000, '2026-07-31', 'Selesai', 'MoU_18_1783675336.pdf'),
(19, 19, 'Disertasi', 7000000, '2026-07-31', 'Tertunda', 'MoU_19_1783678484.pdf'),
(20, 20, 'Tesis', 4000000, '2026-07-31', 'Tertunda', 'MoU_20_1783678932.pdf'),
(21, 21, 'Tesis', 3000000, '2026-07-31', 'Selesai', 'MoU_21_1783690086.pdf'),
(22, 22, 'Skripsi', 4000000, '2026-06-10', 'Tertunda', 'MoU_22_1783736391.pdf'),
(23, 23, 'Skripsi', 4500000, '2026-07-31', 'Tertunda', NULL),
(24, 24, 'Skripsi', 4500000, '2026-07-31', 'Tertunda', NULL),
(25, 25, 'Disertasi', 14500000, '2026-07-31', 'Tertunda', 'MoU_25_1783695754.pdf'),
(26, 26, 'Skripsi', 4000000, '2026-07-31', 'Tertunda', 'MoU_26_1783737055.pdf'),
(29, 24, 'Artikel (ilham)', 450000, '2026-07-15', 'Tertunda', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pesanan_milestone`
--

CREATE TABLE `pesanan_milestone` (
  `id_milestone` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `judul_tahapan` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `status_pengerjaan` enum('Menunggu','Dikerjakan','Revisi','Selesai','Menunggu Konfirmasi') DEFAULT 'Menunggu',
  `tgl_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesanan_milestone`
--

INSERT INTO `pesanan_milestone` (`id_milestone`, `id_pesanan`, `judul_tahapan`, `deskripsi`, `status_pengerjaan`, `tgl_update`) VALUES
(1, 29, 'Artikel Bab 1', 'Menentukan judul dan pendahuluan', 'Revisi', '2026-07-12 07:16:59'),
(2, 29, 'Bab 2', 'Kerangka Berfikir', 'Selesai', '2026-07-12 07:28:53'),
(3, 29, 'Bab Akhir', 'Ini adalah tahapan akhir penyelesaian', 'Menunggu Konfirmasi', '2026-07-12 07:24:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `privileges` enum('Admin','Staff') NOT NULL DEFAULT 'Staff',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `privileges`, `created_at`) VALUES
(2, 'ilham', '$2y$10$D8XZFh5vUaKt3ygnCLgVuesU1u0VSOBKMpZrWfJcY7w2ZT4vFcik.', 'Ilham Nurhamzah', 'Admin', '2026-07-09 08:42:02'),
(3, 'ratih', '$2y$10$Ko6u.yXLZVyX6ENoxnKuRuZuhjcMkBJTBmN4Z2Vr06YdwcXY2X80q', 'Ibu Ratih', 'Staff', '2026-07-09 08:54:24'),
(4, 'rendi', '$2y$10$n8mp9WTUl283I3OZ62gItOrTAU/i0l5zbF1FnwqKd8vLqnCe3Y0ii', 'Rendi Ganteng', 'Admin', '2026-07-09 09:32:22'),
(5, 'irfan', '$2y$10$tRJLBqHhgTF/Tu2awxQRNOwaqJD.Gh62g.zrS5.kOBAlfAv3EiiEa', 'Irfan Masum', 'Admin', '2026-07-10 02:54:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `klien`
--
ALTER TABLE `klien`
  ADD PRIMARY KEY (`id_klien`);

--
-- Indexes for table `milestone_chat`
--
ALTER TABLE `milestone_chat`
  ADD PRIMARY KEY (`id_chat`),
  ADD KEY `id_milestone` (`id_milestone`);

--
-- Indexes for table `pembayaran_termin`
--
ALTER TABLE `pembayaran_termin`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indexes for table `pengeluaran_perusahaan`
--
ALTER TABLE `pengeluaran_perusahaan`
  ADD PRIMARY KEY (`id_pengeluaran`);

--
-- Indexes for table `pesanan_layanan`
--
ALTER TABLE `pesanan_layanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_klien` (`id_klien`);

--
-- Indexes for table `pesanan_milestone`
--
ALTER TABLE `pesanan_milestone`
  ADD PRIMARY KEY (`id_milestone`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `klien`
--
ALTER TABLE `klien`
  MODIFY `id_klien` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `milestone_chat`
--
ALTER TABLE `milestone_chat`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `pembayaran_termin`
--
ALTER TABLE `pembayaran_termin`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `pengeluaran_perusahaan`
--
ALTER TABLE `pengeluaran_perusahaan`
  MODIFY `id_pengeluaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pesanan_layanan`
--
ALTER TABLE `pesanan_layanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `pesanan_milestone`
--
ALTER TABLE `pesanan_milestone`
  MODIFY `id_milestone` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `milestone_chat`
--
ALTER TABLE `milestone_chat`
  ADD CONSTRAINT `milestone_chat_ibfk_1` FOREIGN KEY (`id_milestone`) REFERENCES `pesanan_milestone` (`id_milestone`) ON DELETE CASCADE;

--
-- Constraints for table `pembayaran_termin`
--
ALTER TABLE `pembayaran_termin`
  ADD CONSTRAINT `pembayaran_termin_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan_layanan` (`id_pesanan`) ON DELETE CASCADE;

--
-- Constraints for table `pesanan_layanan`
--
ALTER TABLE `pesanan_layanan`
  ADD CONSTRAINT `pesanan_layanan_ibfk_1` FOREIGN KEY (`id_klien`) REFERENCES `klien` (`id_klien`) ON DELETE CASCADE;

--
-- Constraints for table `pesanan_milestone`
--
ALTER TABLE `pesanan_milestone`
  ADD CONSTRAINT `pesanan_milestone_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan_layanan` (`id_pesanan`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
