-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 01:48 AM
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
-- Database: `sistem_usahawan_pahang`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '12345'),
(2, 'Arif', 'Arif3121');

-- --------------------------------------------------------

--
-- Table structure for table `berita`
--

CREATE TABLE `berita` (
  `id` int(11) NOT NULL,
  `tajuk` varchar(255) NOT NULL,
  `tarikh` date NOT NULL,
  `kandungan` text NOT NULL,
  `imej` varchar(255) NOT NULL,
  `pautan` varchar(255) DEFAULT '#'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `berita`
--

INSERT INTO `berita` (`id`, `tajuk`, `tarikh`, `kandungan`, `imej`, `pautan`) VALUES
(6, 'Semarak Felda', '2025-10-24', 'Kuala Lumpur: FELDA berisiko kehilangan hak ke atas tanah strategik di Jalan Semarak di sini, yang dianggarkan bernilai lebih RM200 juta apabila ia dipercayai bertukar hak milik menerusi transaksi diragui sejak 2015.', 'uploads/68f9ed6abbc4c_klvc3.transformed_0.jpg', 'https://www.bharian.com.my/berita/nasional/2017/12/366015/pindah-milik-tanah-felda-jalan-semarak-dicurigai'),
(7, '𝗣𝗘𝗥𝗔𝗦𝗠𝗜𝗔𝗡 𝗣𝗥𝗢𝗚𝗥𝗔𝗠 𝗝𝗘𝗟𝗔𝗝𝗔𝗛 𝗪𝗜𝗥𝗔 𝗠𝗔𝗗𝗔𝗡𝗜 & 𝗣𝗥𝗢𝗚𝗥𝗔𝗠 𝗦𝗔𝗧𝗘𝗟𝗜𝗧 𝗠𝗔𝗗𝗔𝗡𝗜 𝗥𝗔𝗞𝗬𝗔𝗧 𝟮𝟬𝟮𝟱 𝗙𝗘𝗟𝗗𝗔 𝗦𝗨𝗡𝗚𝗔𝗜 𝗞𝗢𝗬𝗔𝗡, 𝗟𝗜𝗣𝗜𝗦', '2025-10-26', '𝗣𝗿𝗼𝗴𝗿𝗮𝗺 𝗶𝗻𝗶 𝗯𝘂𝗸𝗮𝗻 𝘀𝗲𝗸𝗮𝗱𝗮𝗿 𝘀𝗶𝗺𝗯𝗼𝗹 𝗸𝗲𝗯𝗲𝗿𝘀𝗮𝗺𝗮𝗮𝗻, 𝘁𝗲𝘁𝗮𝗽𝗶 𝗺𝗲𝗱𝗮𝗻 𝗺𝗲𝗻𝘆𝗮𝘁𝘂𝗸𝗮𝗻 𝗸𝗲𝗸𝘂𝗮𝘁𝗮𝗻 𝗮𝗻𝘁𝗮𝗿𝗮 𝗸𝗲𝗿𝗮𝗷𝗮𝗮𝗻 𝗻𝗲𝗴𝗲𝗿𝗶, 𝗮𝗴𝗲𝗻𝘀𝗶 𝗽𝗲𝗿𝘀𝗲𝗸𝘂𝘁𝘂𝗮𝗻 𝘀𝗲𝗿𝘁𝗮 𝘄𝗮𝗿𝗴𝗮 𝗽𝗲𝗻𝗲𝗿𝗼𝗸𝗮 𝗙𝗘𝗟𝗗𝗔 𝗱𝗲𝗺𝗶 𝗸𝗲𝗺𝗮𝗸𝗺𝘂𝗿𝗮𝗻 𝗯𝗲𝗿𝘀𝗮𝗺𝗮.', 'uploads/68fedb9c2267b_568937461_1358862509407253_5427199725644559230_n.jpg', 'https://www.facebook.com/share/p/1FoYRQb2ze/'),
(8, '𝗣𝗘𝗥𝗞𝗛𝗜𝗗𝗠𝗔𝗧𝗔𝗡 𝗟𝗘𝗕𝗜𝗛 𝗣𝗢𝗪𝗘𝗥 𝗦𝗘𝗟𝗘𝗣𝗔𝗦 𝗜𝗡𝗜, 𝗕𝗔𝗡𝗚𝗨𝗡𝗔𝗡 𝗕𝗔𝗛𝗔𝗥𝗨 𝗝𝗣𝗝 𝗕𝗘𝗡𝗧𝗢𝗡𝗚 𝗗𝗜𝗕𝗨𝗞𝗔 𝗥𝗔𝗦𝗠𝗜!', '2025-10-24', 'Hari ini saya berkesempatan menghadiri Majlis Perasmian Bangunan Baharu Jabatan Pengangkutan Jalan (JPJ) Cawangan Bentong) sebagai wakil kepada YB Ir. Razali Kassim yang turut disempurnakan oleh YB Loke Siew Fook, Menteri Pengangkutan Malaysia.', 'uploads/68fedca43d3fe_567653977_1356811242945713_4285209782613967_n.jpg', 'https://www.facebook.com/share/p/1GqyxoT4dU/'),
(9, '𝗥𝗠𝟰𝟯𝟴,𝟬𝟭𝟴 𝗞𝗘𝗣𝗔𝗗𝗔 𝟭𝟮 𝗢𝗥𝗔𝗡𝗚 𝗪𝗔𝗞𝗜𝗟 𝗣𝗘𝗡𝗘𝗥𝗜𝗠𝗔 𝗗𝗜 𝗕𝗘𝗡𝗧𝗢𝗡𝗚. ', '2025-10-23', 'Terima kasih kepada YAB Dato’ Sri Diraja Haji Wan Rosdy bin Wan Ismail, Menteri Besar Pahang atas 𝗽𝗲𝗻𝘆𝗲𝗿𝗮𝗵𝗮𝗻 𝗰𝗲𝗸 𝘀𝘂𝗺𝗯𝗮𝗻𝗴𝗮𝗻 𝗞𝗲𝗿𝗮𝗷𝗮𝗮𝗻 𝗡𝗲𝗴𝗲𝗿𝗶 𝘆𝗮𝗻𝗴 𝗯𝗲𝗿𝗹𝗮𝗻𝗴𝘀𝘂𝗻𝗴 𝗱𝗶 𝗠𝗮𝗷𝗹𝗶𝘀 𝗣𝗲𝗿𝗯𝗮𝗻𝗱𝗮𝗿𝗮𝗻 𝗕𝗲𝗻𝘁𝗼𝗻𝗴 hari ini. ', 'uploads/68fedce1c81c4_558922825_1355990019694502_923695239600226118_n.jpg', 'https://www.facebook.com/share/p/16JraLjqwZ/');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `usahawan_id` int(11) NOT NULL,
  `produk_id` varchar(50) NOT NULL,
  `nama_produk` varchar(255) DEFAULT NULL,
  `harga` decimal(10,2) DEFAULT NULL,
  `gambar_url` varchar(255) DEFAULT NULL,
  `kuantiti` int(11) DEFAULT 1,
  `tarikh_tambah` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `usahawan_id`, `produk_id`, `nama_produk`, `harga`, `gambar_url`, `kuantiti`, `tarikh_tambah`) VALUES
(3, 6, '14', 'Ikan Kembong', 20.00, '1760413476_upload.jpg', 1, '2025-11-24 02:33:36'),
(4, 4, '14', 'Ikan Kembong', 20.00, '1760413476_upload.jpg', 1, '2025-11-24 02:37:55'),
(6, 4, '13', 'Yamaha MT25', 25000.00, 'mt25.jpg', 1, '2025-11-24 06:33:19');

-- --------------------------------------------------------

--
-- Table structure for table `cart_backup`
--

CREATE TABLE `cart_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `produk_id` varchar(50) NOT NULL,
  `nama_produk` varchar(255) DEFAULT NULL,
  `harga` decimal(10,2) DEFAULT NULL,
  `gambar_url` varchar(255) DEFAULT NULL,
  `kuantiti` int(11) DEFAULT 1,
  `tarikh_tambah` timestamp NOT NULL DEFAULT current_timestamp(),
  `usahawan_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_backup`
--

INSERT INTO `cart_backup` (`id`, `user_id`, `produk_id`, `nama_produk`, `harga`, `gambar_url`, `kuantiti`, `tarikh_tambah`, `usahawan_id`) VALUES
(81, 3, '16', 'Ikan', 20.00, '1762931628_kembung.jpg', 1, '2025-11-19 03:34:17', 0),
(97, 1, '13', 'Yamaha MT25', 25000.00, 'mt25.jpg', 1, '2025-11-24 01:57:54', 0),
(101, 1, '14', 'Ikan Kembong', 20.00, '1760413476_upload.jpg', 1, '2025-11-24 02:15:50', 0),
(103, 1, '16', 'Ikan', 20.00, '1762931628_kembung.jpg', 1, '2025-11-24 02:16:27', 0);

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender_id`, `receiver_id`, `message`, `created_at`) VALUES
(1, 1, 2, 'Hi kamu', '2025-10-13 01:53:57'),
(2, 1, 2, 'hello', '2025-10-14 01:44:43'),
(3, 2, 1, 'ye saya', '2025-10-14 01:45:22'),
(4, 1, 2, 'Hello', '2025-10-15 05:05:25');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `usahawan_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `usahawan_id`, `comment`, `created_at`) VALUES
(1, 1, 1, 'Hi', '2025-09-24 01:18:52'),
(2, 1, 1, 'Hi', '2025-09-24 01:20:09'),
(3, 1, 1, 'Hi', '2025-09-24 01:22:27'),
(4, 1, 2, 'Hi', '2025-09-24 01:38:00'),
(5, 3, 2, 'Sedapnyer', '2025-09-25 04:02:45'),
(6, 3, 1, 'Beli kat mana ni', '2025-09-25 04:03:22'),
(7, 3, 1, 'Beli kat mana ni', '2025-09-25 04:05:16'),
(8, 3, 1, 'Beli kat mana ni', '2025-09-25 04:05:49'),
(9, 3, 1, 'Beli kat mana ni', '2025-09-25 04:06:01'),
(10, 4, 3, 'Kenapa kamu curi gambar saya, dasar paparazi', '2025-10-03 02:41:28'),
(11, 4, 4, 'Siapa kamu beban', '2025-10-03 02:43:06'),
(12, 4, 4, 'dada', '2025-10-09 01:30:49'),
(13, 4, 1, 'Saya suka', '2025-10-10 04:02:14'),
(14, 2, 1, 'Tahniah YB', '2025-10-30 03:34:07'),
(15, 2, 1, 'Tahniah YB', '2025-10-30 03:34:31'),
(16, 5, 4, 'Kenapa harga 2 tapak jadi 75 untuk satu tapak?', '2025-10-30 04:14:30'),
(17, 5, 2, 'Ianya kerana semakin banyak tapak yang diambil, semakin tinggi peratus untuk dapat untung lebih', '2025-10-30 04:15:40');

-- --------------------------------------------------------

--
-- Table structure for table `event_pasar`
--

CREATE TABLE `event_pasar` (
  `id` int(11) NOT NULL,
  `tajuk` varchar(200) NOT NULL,
  `lokasi` varchar(120) DEFAULT NULL,
  `tarikh` date NOT NULL,
  `pautan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama`) VALUES
(1, 'Makanan'),
(2, 'Pakaian'),
(3, 'aksesori motor');

-- --------------------------------------------------------

--
-- Table structure for table `komuniti`
--

CREATE TABLE `komuniti` (
  `id` int(11) NOT NULL,
  `usahawan_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permohonan_agro`
--

CREATE TABLE `permohonan_agro` (
  `id` int(11) NOT NULL,
  `nama` varchar(200) NOT NULL,
  `ic` varchar(20) NOT NULL,
  `telefon` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `tujuan` text NOT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  `tarikh_permohonan` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permohonan_ipush`
--

CREATE TABLE `permohonan_ipush` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `ic` varchar(20) NOT NULL,
  `telefon` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `kategori` varchar(500) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `tujuan` text NOT NULL,
  `dokumen` varchar(255) NOT NULL,
  `tarikh_permohonan` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permohonan_ipush`
--

INSERT INTO `permohonan_ipush` (`id`, `nama`, `ic`, `telefon`, `alamat`, `kategori`, `jumlah`, `tujuan`, `dokumen`, `tarikh_permohonan`, `status`) VALUES
(9, 'Lisa', '671090025712', '0102523121', 'Kuala Lumpur', 'Usahawan', 2000.00, 'Pembukaan Kedai', 'uploads/1762936852_CHECKLIST ITEM KELUAR MASUK.pdf', '2025-11-12 08:40:52', 'Selesai');

-- --------------------------------------------------------

--
-- Table structure for table `permohonan_itekad`
--

CREATE TABLE `permohonan_itekad` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `ic` varchar(20) NOT NULL,
  `telefon` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `kategori` enum('B40','Asnaf') NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `tujuan` text NOT NULL,
  `dokumen` varchar(255) NOT NULL,
  `tarikh_permohonan` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `usahawan_id` int(11) NOT NULL,
  `no_pesanan` varchar(50) NOT NULL,
  `nama_pelanggan` varchar(255) NOT NULL,
  `no_telefon` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `nota` text DEFAULT NULL,
  `cara_hantar` varchar(20) NOT NULL,
  `cara_bayar` varchar(20) NOT NULL,
  `jumlah_bayaran` decimal(10,2) NOT NULL,
  `status_pesanan` varchar(20) DEFAULT 'pending',
  `status_bayaran` varchar(20) DEFAULT 'pending',
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `tarikh_pesanan` datetime DEFAULT current_timestamp(),
  `tarikh_diproses` datetime DEFAULT NULL,
  `tarikh_dihantar` datetime DEFAULT NULL,
  `tarikh_selesai` datetime DEFAULT NULL,
  `tarikh_dibatalkan` datetime DEFAULT NULL,
  `sebab_batal` text DEFAULT NULL,
  `nota_pesanan` text DEFAULT NULL,
  `tarikh_kemaskini` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id`, `usahawan_id`, `no_pesanan`, `nama_pelanggan`, `no_telefon`, `alamat`, `nota`, `cara_hantar`, `cara_bayar`, `jumlah_bayaran`, `status_pesanan`, `status_bayaran`, `stripe_session_id`, `tarikh_pesanan`, `tarikh_diproses`, `tarikh_dihantar`, `tarikh_selesai`, `tarikh_dibatalkan`, `sebab_batal`, `nota_pesanan`, `tarikh_kemaskini`) VALUES
(13, 1, 'ORD202511110E15F9', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 50000.00, 'pending', 'pending', NULL, '2025-11-11 16:18:56', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-11 16:18:56'),
(14, 2, 'ORD20251112B920D9', 'Eir Aoi', '0102523121', 'dadada', '', 'delivery', 'cod', 12.99, 'delivered', 'pending', NULL, '2025-11-12 11:21:31', '2025-11-12 11:22:01', '2025-11-12 11:22:10', '2025-11-12 11:22:16', NULL, NULL, NULL, '2025-11-12 11:22:16'),
(15, 1, 'ORD202511122D1513', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'cod', 20.00, 'delivered', 'pending', NULL, '2025-11-12 15:05:22', '2025-11-12 16:44:18', '2025-11-12 16:44:23', '2025-11-12 16:44:30', NULL, NULL, NULL, '2025-11-12 16:44:30'),
(16, 1, 'ORD2025111229C596', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 62.98, 'delivered', 'pending', NULL, '2025-11-12 15:05:54', '2025-11-12 15:18:57', '2025-11-12 15:19:05', '2025-11-12 15:19:12', NULL, NULL, NULL, '2025-11-12 15:19:12'),
(17, 1, 'ORD202511123A8D8D', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 25000.00, 'pending', 'pending', NULL, '2025-11-12 15:17:55', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-12 15:17:55'),
(18, 1, 'ORD2025111266B6DA', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 20.00, 'pending', 'pending', NULL, '2025-11-12 16:42:30', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-12 16:42:30'),
(19, 1, 'ORD202511171932', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 25.00, 'pending', 'paid', 'cs_test_a18s7W9tMjKxZwjgOgcPCbWxAvzgTqeX2Is189jJILKJ1GEvO9F392Uc5C', '2025-11-17 09:50:02', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-17 09:50:02'),
(20, 1, 'ORD202511175097', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 25000.00, 'pending', 'paid', 'cs_test_a1ZWP5COeqX70kqADjEDwC5y4zRsGi54B52wLVqeQ7NPLIq1niumDY9s9w', '2025-11-17 09:52:21', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-17 09:52:21'),
(21, 1, 'ORD202511179010', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 49.99, 'pending', 'paid', 'cs_test_a1taKLouQ4AdqBwfNTj23L6OHzOCEAd9LvvPXZtBK6KCo26VGrdx9XREor', '2025-11-17 10:03:26', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-17 10:03:26'),
(22, 1, 'ORD202511177904', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 4.99, 'pending', 'paid', 'cs_test_a10MF5yKcIuKENloV5me033pPkSN8mHL17HaGcENoxcHY6P5fHAWyPqNkM', '2025-11-17 10:06:12', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-17 10:06:12'),
(23, 1, 'ORD202511171051', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 25.00, 'pending', 'paid', 'cs_test_a1JYe74t6Cl8U7ycFmMz5KMCS3RODEz4B2S0FV2qQHdHuudN0HyF73zxy3', '2025-11-17 10:08:16', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-17 10:08:16'),
(24, 1, 'ORD202511171132', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 20.00, 'pending', 'paid', 'cs_test_a1LIQZhRQpcXOW6bfMC0sbkWaQNBwHuANP6NBxXRMUU1lTAOZ2ZsyfmQGd', '2025-11-17 10:30:16', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-17 10:30:16'),
(25, 1, 'ORD202511189720', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 37.99, 'pending', 'paid', 'cs_test_a138Qq0ig8NXGwH553PHZx2yBYjNG70la40oZ6x3VsZYmzebEOOMxfznta', '2025-11-18 17:15:04', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-18 17:15:04'),
(26, 1, 'ORD202511185563', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'online', 12.99, 'pending', 'paid', 'cs_test_a1qVKHUhOa7WKzHHmFbH5fYdfHLHu5tuNsz9HoUoAy7kehuFqVybUJCsU0', '2025-11-18 17:21:17', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-18 17:21:17'),
(27, 3, 'ORD202511183384', 'Wani Hasrita', '0102523121', 'Bandar Kinrara, Kuala Lumpur', '', 'delivery', 'online', 25000.00, 'pending', 'paid', 'cs_test_a1mlbMIIxw2wX3l4uRP34xgmwyRFLqHuWLAjfFRGYrOqwgk5R3bDdFKxtV', '2025-11-18 17:25:12', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-18 17:25:12'),
(28, 3, 'ORD20251118337778', 'Wani Hasrita', '0102523121', 'Bandar Kinrara, Kuala Lumpur', '', 'dropspot', 'cod', 20.00, 'pending', 'pending', NULL, '2025-11-18 17:40:51', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-18 17:40:51'),
(29, 3, 'ORD202511192233', 'Wani Hasrita', '0102523121', 'Bandar Kinrara, Kuala Lumpur', '', 'delivery', 'online', 25000.00, 'pending', 'paid', 'cs_test_a1evsveK709rvCxAMpmi4juE4xv4ZgkmiQpwnw1W7U4FSQGxxuX7pQGH0r', '2025-11-19 11:19:12', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-19 11:19:12'),
(31, 1, 'ORD20251124A2B887', 'Lisa', '0102523121', 'dadada', '', 'delivery', 'cod', 25.00, 'pending', 'pending', NULL, '2025-11-24 10:28:58', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-24 10:28:58'),
(33, 4, 'ORD202511248332', 'Majiko', '0102523121', 'Shah Alam, Kuala Lumpur', '', 'delivery', 'online', 25000.00, 'pending', 'paid', 'cs_test_a1JCKlsYfgQ4EV0A6aPwM2CH3vtsyd8wjlc3veZ0UR2AkooiYFdRIJZT7I', '2025-11-24 10:31:28', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-24 10:31:28'),
(34, 4, 'ORD202511241039', 'Majiko', '0102523121', 'Shah Alam, Kuala Lumpur', '', 'delivery', 'online', 20.00, 'pending', 'paid', 'cs_test_a1y9y21Qqvjqki6DaDD1dBqXd1nRS8SfEwDl33swmnl4BnTn7j121LjIHi', '2025-11-24 14:35:09', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-24 14:35:09');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan_item`
--

CREATE TABLE `pesanan_item` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `nama_produk` varchar(255) NOT NULL,
  `gambar_url` varchar(255) DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `kuantiti` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesanan_item`
--

INSERT INTO `pesanan_item` (`id`, `pesanan_id`, `produk_id`, `nama_produk`, `gambar_url`, `harga`, `kuantiti`, `subtotal`) VALUES
(3, 4, 11, 'Jersey Bola', '1759458764_jersey_kids_premier_league_epl_1708190734_7cfba261_progressive.jpg', 49.99, 1, 49.99),
(4, 5, 13, 'Yamaha MT25', 'mt25.jpg', 25000.00, 1, 25000.00),
(5, 6, 13, 'Yamaha MT25', 'mt25.jpg', 25000.00, 1, 25000.00),
(6, 7, 8, 'Telur Gred B', '1758682772_119.png', 4.99, 1, 4.99),
(7, 8, 12, 'Yamaha Y15ZR', 'y15zr.jpg', 8900.00, 3, 26700.00),
(8, 9, 13, 'Yamaha MT25', 'mt25.jpg', 25000.00, 1, 25000.00),
(9, 10, 13, 'Yamaha MT25', 'mt25.jpg', 25000.00, 1, 25000.00),
(10, 11, 8, 'Telur Gred B', '1758682772_119.png', 4.99, 1, 4.99),
(11, 12, 11, 'Jersey Bola', '1759458764_jersey_kids_premier_league_epl_1708190734_7cfba261_progressive.jpg', 49.99, 1, 49.99),
(12, 13, 13, 'Yamaha MT25', 'mt25.jpg', 25000.00, 2, 50000.00),
(13, 14, 7, 'Telur Gred A', '1758682674_PGjl1edpzXbCVYXUAbcsh3sIiObODPxkYcdXxVDF.jpg', 12.99, 1, 12.99),
(14, 15, 14, 'Ikan Kembong', '1760413476_upload.jpg', 20.00, 1, 20.00),
(15, 16, 7, 'Telur Gred A', '1758682674_PGjl1edpzXbCVYXUAbcsh3sIiObODPxkYcdXxVDF.jpg', 12.99, 1, 12.99),
(16, 16, 11, 'Jersey Bola', '1759458764_jersey_kids_premier_league_epl_1708190734_7cfba261_progressive.jpg', 49.99, 1, 49.99),
(17, 17, 13, 'Yamaha MT25', 'mt25.jpg', 25000.00, 1, 25000.00),
(18, 18, 16, 'Ikan', '1762931628_kembung.jpg', 20.00, 1, 20.00),
(19, 19, 21, 'Kuaci', '1762936776_kuaci.jpeg', 25.00, 1, 25.00),
(20, 20, 13, 'Yamaha MT25', 'mt25.jpg', 25000.00, 1, 25000.00),
(21, 21, 10, 'Jersey Red Devils', '1759458669_my-11134207-7rasd-m0e40ahrst97f5.jpeg', 49.99, 1, 49.99),
(22, 22, 8, 'Telur Gred B', '1758682772_119.png', 4.99, 1, 4.99),
(23, 23, 21, 'Kuaci', '1762936776_kuaci.jpeg', 25.00, 1, 25.00),
(24, 24, 16, 'Ikan', '1762931628_kembung.jpg', 20.00, 1, 20.00),
(25, 25, 9, 'Beras Basmathi Faiza', '1758682920_4065387274.png', 37.99, 1, 37.99),
(26, 26, 7, 'Telur Gred A', '1758682674_PGjl1edpzXbCVYXUAbcsh3sIiObODPxkYcdXxVDF.jpg', 12.99, 1, 12.99),
(27, 27, 13, 'Yamaha MT25', 'mt25.jpg', 25000.00, 1, 25000.00),
(28, 28, 14, 'Ikan Kembong', '1760413476_upload.jpg', 20.00, 1, 20.00),
(29, 29, 13, 'Yamaha MT25', 'mt25.jpg', 25000.00, 1, 25000.00),
(31, 31, 21, 'Kuaci', '1762936776_kuaci.jpeg', 25.00, 1, 25.00),
(33, 33, 13, 'Yamaha MT25', 'mt25.jpg', 25000.00, 1, 25000.00),
(34, 34, 14, 'Ikan Kembong', '1760413476_upload.jpg', 20.00, 1, 20.00);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `usahawan_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `usahawan_id`, `image`, `caption`, `created_at`) VALUES
(1, 1, '1758676711_logozakat.png', 'Zakat Selangor', '2025-09-24 01:18:31'),
(2, 2, '1758682273_529805006_1291507872809384_5213216722967461030_n.jpg', '', '2025-09-24 02:51:13'),
(3, 2, '1758764487_ChatGPT Image Sep 8, 2025, 11_25_57 AM.png', 'Makan Nasi Harini', '2025-09-25 01:41:27'),
(4, 4, '1759459252_download.jpeg', 'Hesmes kan saya ni', '2025-10-03 02:40:52'),
(5, 2, '1761796819_Pasar-Malam.jpg', 'Tapak di Pasar Malam Kuantan untuk disewa\r\n\r\n1 Tapak - RM 50\r\n2 Tapak - RM 150', '2025-10-30 04:00:19');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama` varchar(160) NOT NULL,
  `harga` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deskripsi` text DEFAULT NULL,
  `gambar_url` varchar(255) DEFAULT NULL,
  `lokasi` varchar(80) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `kategori_id` int(11) DEFAULT NULL,
  `usahawan_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `nama`, `harga`, `deskripsi`, `gambar_url`, `lokasi`, `stok`, `kategori_id`, `usahawan_id`) VALUES
(7, 'Telur Gred A', 12.99, 'Telur Ayam Gred A', '1758682674_PGjl1edpzXbCVYXUAbcsh3sIiObODPxkYcdXxVDF.jpg', 'Kuantan', 200, 1, 2),
(8, 'Telur Gred B', 4.99, 'Telur Gred B (10 Biji)', '1758682772_119.png', 'Kuantan', 30, 1, 2),
(9, 'Beras Basmathi Faiza', 37.99, 'Beras Basmathi Jenama Faiza', '1758682920_4065387274.png', 'Pelangai', 100, 1, 2),
(10, 'Jersey Red Devils', 49.99, 'Baju penduduk epl tempat ke-16', '1759458669_my-11134207-7rasd-m0e40ahrst97f5.jpeg', 'Mempaga', 2, 2, 1),
(13, 'Yamaha MT25', 25000.00, 'Yamaha MT25 Baru untuk dilepaskan', 'mt25.jpg', 'Bera', 1, 3, 2),
(14, 'Ikan Kembong', 20.00, 'Harga mengikut per Kg', '1760413476_upload.jpg', 'Kuantan', 200, 1, 1),
(16, 'Ikan', 20.00, 'Contoh', '1762931628_kembung.jpg', 'Kuantan', 30, 1, 5),
(21, 'Kuaci', 25.00, 'Contoh', '1762936776_kuaci.jpeg', 'Jerantut', 100, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `promosi`
--

CREATE TABLE `promosi` (
  `id` int(11) NOT NULL,
  `tajuk` varchar(180) NOT NULL,
  `gambar_url` varchar(255) NOT NULL,
  `pautan` varchar(255) DEFAULT NULL,
  `mula` date DEFAULT NULL,
  `tamat` date DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ruang_fizikal`
--

CREATE TABLE `ruang_fizikal` (
  `id` int(11) NOT NULL,
  `nama_ruang` varchar(100) DEFAULT NULL,
  `lokasi` varchar(200) DEFAULT NULL,
  `kemudahan` text DEFAULT NULL,
  `kadar_sewa` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ruang_fizikal`
--

INSERT INTO `ruang_fizikal` (`id`, `nama_ruang`, `lokasi`, `kemudahan`, `kadar_sewa`, `status`, `gambar`) VALUES
(2, 'Bilik Mesyuarat Utama', 'Tingkat 3, Blok Pentadbiran', 'Meja mesyuarat, projektor, WiFi, pendingin hawa', 250.00, 'Tersedia', 'bilik_mesyuarat.jpg'),
(3, 'Ruang Pameran Usahawan', 'Aras G, Lobi Utama', 'Tapak pameran, soket elektrik, meja pameran', 150.00, 'Disewa', 'ruang_pameran.jpg'),
(4, 'Dewan Seminar Pahang', 'Menara Pahang, Tingkat 5', 'Kerusi seminar, podium, sistem audio, LCD', 400.00, 'Tersedia', 'dewan_seminar.jpg'),
(5, 'Tapak Pasar Malam 1', 'Kuantan', 'Tapak Berniaga', 50.00, 'Tersedia', 'Pasar-Malam.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `statistik_pelawat`
--

CREATE TABLE `statistik_pelawat` (
  `id` int(11) NOT NULL,
  `page` varchar(100) DEFAULT NULL,
  `jumlah` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `statistik_pelawat`
--

INSERT INTO `statistik_pelawat` (`id`, `page`, `jumlah`) VALUES
(1, 'index', 817);

-- --------------------------------------------------------

--
-- Table structure for table `tempahan_ruang`
--

CREATE TABLE `tempahan_ruang` (
  `id` int(11) NOT NULL,
  `nama_ruang` varchar(100) DEFAULT NULL,
  `nama_pemohon` varchar(100) DEFAULT NULL,
  `no_ic` varchar(20) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `tarikh_tempah` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Dalam Semakan',
  `maklum_balas` varchar(10000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tempahan_ruang`
--

INSERT INTO `tempahan_ruang` (`id`, `nama_ruang`, `nama_pemohon`, `no_ic`, `telefon`, `tarikh_tempah`, `status`, `maklum_balas`) VALUES
(1, 'Bilik Mesyuarat Utama', 'Arif Haikal', '011203030095', '0102523121', '2025-10-30', 'Dalam Semakan', ''),
(3, 'Bilik Mesyuarat Utama', 'Arif Haikal', '011203030095', '0102523121', '2025-10-31', 'Dalam Semakan', 'Baik nanti saya akan contact encik'),
(4, 'Tapak Pasar Malam 1', 'Arif Haikal', '011203030095', '0102523121', '2025-10-30', 'Dalam Semakan', ''),
(5, 'Bilik Mesyuarat Utama', 'Test 1', '011203030095', '0102523121', '2025-11-01', 'Dalam Semakan', ''),
(6, 'Dewan Indera Mahkota', 'Muhamad Arif Haikal', '011203030095', '0102523121', '2025-11-13', 'Dalam Semakan', ''),
(7, 'Dewan Indera Mahkota', 'Arif Haikal', '011203030095', '0102523121', '2025-11-12', 'Dalam Semakan', '');

-- --------------------------------------------------------

--
-- Table structure for table `usahawan`
--

CREATE TABLE `usahawan` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `ic` varchar(20) NOT NULL,
  `perniagaan` varchar(255) NOT NULL,
  `jenis` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telefon` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(250) NOT NULL,
  `tarikh_daftar` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL,
  `status` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usahawan`
--

INSERT INTO `usahawan` (`id`, `nama`, `ic`, `perniagaan`, `jenis`, `alamat`, `telefon`, `email`, `password`, `tarikh_daftar`, `avatar`, `status`) VALUES
(1, 'Lisa', '671090025712', 'He & She', 'Makanan', 'dadada', '0102523121', 'Arep3121@gmail.com', 'Rizki3121', '2025-08-13 07:07:01', 'uploads/1761797259_hqdefault.jpg', 'yes'),
(2, 'Eir Aoi', '671090025712', 'Jajan Lama', 'Perkhidmatan', 'dadada', '0102523121', 'amizarschedule@gmail.com', 'Haikal_3121', '2025-09-24 01:37:40', 'uploads/1761797164_eiraoi.jpeg', 'yes'),
(3, 'Wani Hasrita', '011203030095', 'Bandai Namco', 'Lain-lain', 'Bandar Kinrara, Kuala Lumpur', '0102523121', 'aaarrriiifff3121@gmail.com', 'Haikal3121', '2025-10-03 02:36:06', 'uploads/1761797355_images.jpeg', 'Yes'),
(4, 'Majiko', '760101036337', 'Bandai Namcol', 'Lain-lain', 'Shah Alam, Kuala Lumpur', '0102523121', 'ekalgenshin@gmail.com', 'Haikal3121', '2025-10-03 02:39:52', 'uploads/1761797516_majik.jpeg', 'Yes'),
(5, 'Muhamad Arif Haikal', '011203030095', 'Contoh', 'Lain-lain', 'Kuala Lumpur', '0102523121', 'haikal.arif3121@icloud.com', 'Haikal3121', '2025-11-12 07:12:13', 'uploads/1762931575_Logo KAF.jpg', 'Yes'),
(6, 'Adam Zaidan', '760101036337', 'Popia Simpul Kasih', 'Makanan', 'Kuantan, Pahang', '01120800173', 'adamzaidan@gmail.com', 'Haikal_3121', '2025-11-12 07:49:44', NULL, 'Yes');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `tarikh_daftar` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `telefon`, `tarikh_daftar`) VALUES
(1, 'Ali Bin Abu', 'ali@example.com', 'e10adc3949ba59abbe56e057f20f883e', '0123456789', '2025-10-09 06:10:32'),
(2, 'Siti Binti Ahmad', 'siti@example.com', 'e80b5017098950fc58aad83c8c14978e', '0191234567', '2025-10-09 06:10:32'),
(3, 'Arif', 'Arep@gmail.com', 'Arif3121', '0102523121', '2025-10-09 06:55:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `berita`
--
ALTER TABLE `berita`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usahawan` (`usahawan_id`),
  ADD KEY `idx_produk` (`produk_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `usahawan_id` (`usahawan_id`);

--
-- Indexes for table `event_pasar`
--
ALTER TABLE `event_pasar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tarikh` (`tarikh`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `komuniti`
--
ALTER TABLE `komuniti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usahawan_id` (`usahawan_id`);

--
-- Indexes for table `permohonan_agro`
--
ALTER TABLE `permohonan_agro`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permohonan_ipush`
--
ALTER TABLE `permohonan_ipush`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permohonan_itekad`
--
ALTER TABLE `permohonan_itekad`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_pesanan` (`no_pesanan`),
  ADD KEY `idx_usahawan_id` (`usahawan_id`),
  ADD KEY `idx_tarikh_pesanan` (`tarikh_pesanan`),
  ADD KEY `idx_status_pesanan` (`status_pesanan`);

--
-- Indexes for table `pesanan_item`
--
ALTER TABLE `pesanan_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pesanan_id` (`pesanan_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usahawan_id` (`usahawan_id`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`),
  ADD KEY `usahawan_id` (`usahawan_id`),
  ADD KEY `nama` (`nama`),
  ADD KEY `lokasi` (`lokasi`),
  ADD KEY `harga` (`harga`);

--
-- Indexes for table `promosi`
--
ALTER TABLE `promosi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ruang_fizikal`
--
ALTER TABLE `ruang_fizikal`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `statistik_pelawat`
--
ALTER TABLE `statistik_pelawat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tempahan_ruang`
--
ALTER TABLE `tempahan_ruang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usahawan`
--
ALTER TABLE `usahawan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `berita`
--
ALTER TABLE `berita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `event_pasar`
--
ALTER TABLE `event_pasar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `komuniti`
--
ALTER TABLE `komuniti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permohonan_agro`
--
ALTER TABLE `permohonan_agro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permohonan_ipush`
--
ALTER TABLE `permohonan_ipush`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `permohonan_itekad`
--
ALTER TABLE `permohonan_itekad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `pesanan_item`
--
ALTER TABLE `pesanan_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `promosi`
--
ALTER TABLE `promosi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ruang_fizikal`
--
ALTER TABLE `ruang_fizikal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `statistik_pelawat`
--
ALTER TABLE `statistik_pelawat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tempahan_ruang`
--
ALTER TABLE `tempahan_ruang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `usahawan`
--
ALTER TABLE `usahawan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `usahawan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `usahawan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`usahawan_id`) REFERENCES `usahawan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `komuniti`
--
ALTER TABLE `komuniti`
  ADD CONSTRAINT `komuniti_ibfk_1` FOREIGN KEY (`usahawan_id`) REFERENCES `usahawan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`usahawan_id`) REFERENCES `usahawan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `produk_ibfk_2` FOREIGN KEY (`usahawan_id`) REFERENCES `usahawan` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
