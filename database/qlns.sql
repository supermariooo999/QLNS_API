-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th8 10, 2026 lúc 11:52 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `qlns`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cau_hinh`
--

CREATE TABLE `cau_hinh` (
  `id` bigint(20) NOT NULL,
  `nhom` varchar(100) DEFAULT NULL,
  `khoa_cau_hinh` varchar(100) DEFAULT NULL,
  `gia_tri` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chuc_vu`
--

CREATE TABLE `chuc_vu` (
  `id` bigint(20) NOT NULL,
  `ma_chuc_vu` varchar(50) DEFAULT NULL,
  `ten_chuc_vu` varchar(255) NOT NULL,
  `ten_tat` varchar(100) DEFAULT NULL,
  `thu_tu_cap` int(11) DEFAULT 1,
  `la_quan_ly` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chuc_vu`
--

INSERT INTO `chuc_vu` (`id`, `ma_chuc_vu`, `ten_chuc_vu`, `ten_tat`, `thu_tu_cap`, `la_quan_ly`, `created_at`) VALUES
(1, 'NV', 'Nhân viên', NULL, 6, 0, '2026-04-28 00:23:54'),
(2, 'CC', 'Công chức', NULL, 5, 0, '2026-04-28 00:23:54'),
(3, 'PTOT', 'Phó Tổ trưởng', NULL, 4, 1, '2026-04-28 00:23:54'),
(5, 'PTCS', 'Phó trưởng Thuế cơ sở', NULL, 2, 1, '2026-04-28 00:23:54'),
(6, 'TTCS', 'Trưởng Thuế cơ sở', NULL, 1, 1, '2026-04-28 00:23:54'),
(10, 'TOT', 'Tổ trưởng', 'TOT', 3, 1, '2026-04-29 09:55:28'),
(12, 'QTHT', 'Quản trị hệ thống', 'QTHT', 7, 0, '2026-04-30 10:30:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cong_tac`
--

CREATE TABLE `cong_tac` (
  `id` bigint(20) NOT NULL,
  `so_giay` bigint(20) NOT NULL,
  `id_nhan_vien` bigint(20) NOT NULL,
  `noi_dung` text DEFAULT NULL,
  `so_cong_lenh` varchar(100) DEFAULT NULL,
  `tu_ngay` date DEFAULT NULL,
  `den_ngay` date DEFAULT NULL,
  `loai_cong_tac` enum('cong_tac','tap_huan','hoc') DEFAULT 'cong_tac',
  `luong_ung_truoc` decimal(12,2) DEFAULT 0.00 COMMENT 'Lương ứng trước',
  `cong_tac_phi_ung_truoc` decimal(12,2) DEFAULT 0.00 COMMENT 'Công tác phí ứng trước',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cong_tac`
--

INSERT INTO `cong_tac` (`id`, `so_giay`, `id_nhan_vien`, `noi_dung`, `so_cong_lenh`, `tu_ngay`, `den_ngay`, `loai_cong_tac`, `luong_ung_truoc`, `cong_tac_phi_ung_truoc`, `created_at`, `updated_at`) VALUES
(33, 2026013, 57, 'Niêm yết công khai thủ tục hành chính', '2017/CMA-VP', '2026-07-31', '2026-08-03', 'cong_tac', 0.00, 0.00, '2026-08-10 08:16:39', '2026-08-10 08:16:39'),
(34, 2026014, 60, 'Niêm yết công khai thủ tục hành chính', '2017/CMA-VP', '2026-07-31', '2026-06-02', 'cong_tac', 0.00, 0.00, '2026-08-10 08:16:39', '2026-08-10 08:16:39');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lich_su_duyet_nghi`
--

CREATE TABLE `lich_su_duyet_nghi` (
  `id` bigint(20) NOT NULL,
  `id_nghi_phep` bigint(20) NOT NULL,
  `buoc_so` int(11) NOT NULL,
  `id_tai_khoan` bigint(20) NOT NULL,
  `hanh_dong` varchar(50) NOT NULL,
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_nghi`
--

CREATE TABLE `loai_nghi` (
  `id` bigint(20) NOT NULL,
  `ma_loai` varchar(50) DEFAULT NULL,
  `ten_loai` varchar(255) NOT NULL,
  `huong_luong` tinyint(4) DEFAULT 1,
  `co_tru_phep` tinyint(1) NOT NULL DEFAULT 0,
  `so_ngay_toi_da` decimal(5,2) DEFAULT 0.00,
  `thu_tu_cap` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `loai_nghi`
--

INSERT INTO `loai_nghi` (`id`, `ma_loai`, `ten_loai`, `huong_luong`, `co_tru_phep`, `so_ngay_toi_da`, `thu_tu_cap`) VALUES
(1, 'PN', '🏖️ Phép năm', 1, 1, NULL, 1),
(2, 'NO', '😷 Nghỉ ốm', 1, 0, 0.00, 2),
(3, 'VR', '💒 Việc riêng', 1, 0, 0.00, 3),
(4, 'KHL', '🍺 Không hưởng lương', 0, 0, 0.00, 5),
(5, 'TS', '🤰 Thai sản', 1, 0, 0.00, 4);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `luong_duyet`
--

CREATE TABLE `luong_duyet` (
  `id` bigint(20) NOT NULL,
  `module` varchar(100) NOT NULL,
  `id_chuc_vu_ap_dung` bigint(20) NOT NULL,
  `buoc_so` int(11) NOT NULL,
  `id_chuc_vu_duyet` bigint(20) NOT NULL,
  `bat_buoc` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `luong_duyet`
--

INSERT INTO `luong_duyet` (`id`, `module`, `id_chuc_vu_ap_dung`, `buoc_so`, `id_chuc_vu_duyet`, `bat_buoc`) VALUES
(14, 'leave', 1, 2, 6, 1),
(15, 'leave', 2, 1, 10, 1),
(16, 'leave', 2, 2, 6, 1),
(17, 'leave', 1, 1, 10, 1),
(18, 'leave', 3, 1, 10, 1),
(19, 'leave', 3, 2, 6, 1),
(20, 'leave', 10, 1, 6, 1),
(21, 'leave', 6, 1, 6, 0),
(22, 'leave', 5, 1, 6, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `menu_he_thong`
--

CREATE TABLE `menu_he_thong` (
  `id` bigint(20) NOT NULL,
  `id_cha` bigint(20) DEFAULT NULL,
  `ma_menu` varchar(100) DEFAULT NULL,
  `ten_menu` varchar(255) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `duong_dan` varchar(255) DEFAULT NULL,
  `component` varchar(255) DEFAULT NULL,
  `thu_tu` int(11) DEFAULT 0,
  `hien_thi` tinyint(4) DEFAULT 1,
  `ma_quyen` varchar(100) DEFAULT NULL,
  `id_trang_thai` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `menu_he_thong`
--

INSERT INTO `menu_he_thong` (`id`, `id_cha`, `ma_menu`, `ten_menu`, `icon`, `duong_dan`, `component`, `thu_tu`, `hien_thi`, `ma_quyen`, `id_trang_thai`, `created_at`) VALUES
(1, NULL, 'dashboard', 'Trang chủ', 'home', '/trang-chu', NULL, 1, 1, 'dashboard.view', 11, '2026-04-28 00:23:54'),
(2, NULL, 'leave', 'Quản lý nghỉ phép', 'calendar', '/nghi-phep', NULL, 2, 1, 'leave.create', 11, '2026-04-28 00:23:54'),
(3, NULL, 'trip', 'Quản lý công tác', 'briefcase', '/cong-tac', NULL, 3, 1, 'trip.view', 11, '2026-04-28 00:23:54'),
(4, NULL, 'employee', 'Quản lý nhân viên', 'users', '/nhan-vien', NULL, 4, 1, 'employee.manage', 11, '2026-04-28 00:23:54'),
(5, NULL, 'report', 'Báo cáo tổng hợp', 'chart-bar', '/bao-cao', NULL, 5, 1, 'report.view', 11, '2026-04-28 00:23:54'),
(6, NULL, 'system', 'Cấu hình hệ thống', 'settings', NULL, NULL, 6, 1, NULL, 11, '2026-04-28 00:23:54'),
(7, 6, 'role', 'Phân quyền', 'shield-check', '/phan-quyen', NULL, 3, 1, 'role.manage', 11, '2026-04-28 00:23:54'),
(8, 6, 'account', 'Tài khoản', 'user-cog', '/tai-khoan', NULL, 2, 1, 'system.config', 11, '2026-04-28 00:23:54'),
(9, 6, 'catalog', 'Danh mục', 'list', '/danh-muc', NULL, 1, 1, 'system.config', 11, '2026-04-28 00:23:54'),
(10, 2, 'leave.apply', 'Đăng ký nghỉ phép', 'circle-fading-plus', '/dang-ky-nghi-phep', NULL, 1, 1, 'leave.apply', 11, '2026-04-30 16:48:28'),
(11, 2, 'leave.balance', 'Số dư phép năm', 'database', '/so-du-phep', NULL, 3, 1, 'leave.balance', 11, '2026-05-01 17:05:47'),
(12, 2, 'leave.approve', 'Quản lý đơn nghỉ', 'land-plot', '/duyet-don', NULL, 2, 1, 'leave.approve', 11, '2026-05-03 02:48:43'),
(13, 6, 'flow', 'Cấu hình luồng duyệt', 'cable', '/luong-duyet', NULL, 4, 1, 'flow', 11, '2026-05-10 13:28:09');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `menu_vai_tro`
--

CREATE TABLE `menu_vai_tro` (
  `id` bigint(20) NOT NULL,
  `id_menu` bigint(20) NOT NULL,
  `id_vai_tro` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `menu_vai_tro`
--

INSERT INTO `menu_vai_tro` (`id`, `id_menu`, `id_vai_tro`) VALUES
(16, 1, 14),
(58, 1, 15),
(52, 1, 16),
(59, 1, 17),
(55, 1, 18),
(51, 1, 19),
(17, 2, 14),
(36, 2, 15),
(53, 2, 16),
(39, 2, 17),
(56, 2, 18),
(31, 2, 19),
(18, 3, 14),
(19, 4, 14),
(20, 5, 14),
(61, 5, 15),
(60, 5, 17),
(62, 5, 18),
(21, 6, 14),
(22, 7, 14),
(23, 8, 14),
(24, 9, 14),
(29, 10, 14),
(37, 10, 15),
(54, 10, 16),
(40, 10, 17),
(57, 10, 18),
(32, 10, 19),
(30, 11, 14),
(38, 11, 15),
(48, 11, 16),
(41, 11, 17),
(49, 11, 18),
(50, 11, 19),
(44, 12, 14),
(43, 12, 15),
(45, 12, 16),
(42, 12, 17),
(46, 12, 18),
(47, 12, 19),
(63, 13, 14);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2019_12_14_000001_create_personal_access_tokens_table', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nghi_phep`
--

CREATE TABLE `nghi_phep` (
  `id` bigint(20) NOT NULL,
  `so_don_nghi` varchar(20) DEFAULT NULL,
  `id_nhan_vien` bigint(20) NOT NULL,
  `id_loai_nghi` bigint(20) NOT NULL,
  `buoi_tu_ngay` varchar(5) NOT NULL,
  `buoi_den_ngay` varchar(5) NOT NULL,
  `tu_ngay` date NOT NULL,
  `den_ngay` date NOT NULL,
  `so_ngay` decimal(5,2) NOT NULL,
  `ly_do` text DEFAULT NULL,
  `id_trang_thai` bigint(20) DEFAULT NULL,
  `buoc_hien_tai` int(11) DEFAULT 1,
  `id_nguoi_duyet_hien_tai` bigint(20) DEFAULT NULL,
  `nop_luc` datetime DEFAULT NULL,
  `duyet_luc` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nghi_phep`
--

INSERT INTO `nghi_phep` (`id`, `so_don_nghi`, `id_nhan_vien`, `id_loai_nghi`, `buoi_tu_ngay`, `buoi_den_ngay`, `tu_ngay`, `den_ngay`, `so_ngay`, `ly_do`, `id_trang_thai`, `buoc_hien_tai`, `id_nguoi_duyet_hien_tai`, `nop_luc`, `duyet_luc`, `created_at`, `updated_at`) VALUES
(100, '02-2026', 58, 1, '07', '17', '2026-08-03', '2026-08-05', 3.00, 'Nghỉ phép năm giải quyết việc riêng', 5, 2, NULL, '2026-07-28 08:00:00', '2026-07-29 09:30:00', '2026-07-28 01:00:00', '2026-07-29 02:30:00'),
(101, '03-2026', 59, 2, '07', '17', '2026-08-04', '2026-08-04', 1.00, 'Khám sức khỏe định kỳ', 5, 2, NULL, '2026-08-03 16:00:00', '2026-08-04 07:30:00', '2026-08-03 09:00:00', '2026-08-04 00:30:00'),
(102, '04-2026', 61, 3, '07', '12', '2026-08-05', '2026-08-05', 0.50, 'Giải quyết công việc cá nhân buổi sáng', 4, 1, 23, '2026-08-04 09:15:00', NULL, '2026-08-04 02:15:00', '2026-08-04 02:15:00'),
(103, '05-2026', 62, 1, '07', '17', '2026-08-06', '2026-08-07', 2.00, 'Nghỉ phép năm giải quyết việc riêng', 4, 1, 23, '2026-08-04 10:00:00', NULL, '2026-08-04 03:00:00', '2026-08-04 03:00:00'),
(104, '06-2026', 63, 2, '07', '17', '2026-08-07', '2026-08-07', 1.00, 'Bị sốt siêu vi cần điều trị', 5, 2, NULL, '2026-08-06 17:00:00', '2026-08-07 07:30:00', '2026-08-06 10:00:00', '2026-08-07 00:30:00'),
(105, '07-2026', 58, 1, '07', '17', '2026-01-12', '2026-01-13', 2.00, 'Nghỉ giải quyết việc gia đình', 5, 2, NULL, '2026-01-10 09:00:00', '2026-01-11 10:00:00', '2026-01-10 02:00:00', '2026-01-11 03:00:00'),
(106, '08-2026', 60, 3, '07', '17', '2026-02-02', '2026-02-03', 2.00, 'Nghỉ chuẩn bị Tết nguyên đán', 5, 2, NULL, '2026-01-28 14:00:00', '2026-01-29 08:30:00', '2026-01-28 07:00:00', '2026-01-29 01:30:00'),
(107, '09-2026', 61, 2, '07', '17', '2026-02-16', '2026-02-17', 2.00, 'Bị cúm mùa cần điều trị tại nhà', 5, 2, NULL, '2026-02-16 07:15:00', '2026-02-16 09:00:00', '2026-02-16 00:15:00', '2026-02-16 02:00:00'),
(108, '10-2026', 65, 1, '07', '17', '2026-03-02', '2026-03-04', 3.00, 'Nghỉ phép năm đợt 1', 5, 2, NULL, '2026-02-25 08:30:00', '2026-02-26 11:00:00', '2026-02-25 01:30:00', '2026-02-26 04:00:00'),
(109, '11-2026', 66, 3, '07', '12', '2026-03-16', '2026-03-16', 0.50, 'Đi làm thủ tục giấy tờ cá nhân', 5, 2, NULL, '2026-03-15 16:00:00', '2026-03-16 07:30:00', '2026-03-15 09:00:00', '2026-03-16 00:30:00'),
(110, '12-2026', 67, 1, '07', '17', '2026-04-06', '2026-04-07', 2.00, 'Nghỉ giỗ tổ gia đình', 5, 2, NULL, '2026-04-02 10:00:00', '2026-04-03 14:00:00', '2026-04-02 03:00:00', '2026-04-03 07:00:00'),
(111, '13-2026', 72, 2, '07', '17', '2026-04-20', '2026-04-22', 3.00, 'Điều trị chấn thương chân', 5, 2, NULL, '2026-04-20 08:00:00', '2026-04-20 10:30:00', '2026-04-20 01:00:00', '2026-04-20 03:30:00'),
(112, '14-2026', 73, 1, '07', '17', '2026-05-11', '2026-05-13', 3.00, 'Nghỉ phép năm gia đình', 5, 2, NULL, '2026-05-05 09:00:00', '2026-05-06 15:00:00', '2026-05-05 02:00:00', '2026-05-06 08:00:00'),
(113, '15-2026', 74, 1, '07', '17', '2026-05-18', '2026-05-20', 3.00, 'Nghỉ phép cá nhân đi du lịch', 5, 2, NULL, '2026-05-12 11:00:00', '2026-05-13 09:00:00', '2026-05-12 04:00:00', '2026-05-13 02:00:00'),
(114, '16-2026', 75, 4, '07', '17', '2026-06-01', '2026-06-05', 5.00, 'Việc gia đình không hưởng lương', 5, 2, NULL, '2026-05-25 14:00:00', '2026-05-26 10:00:00', '2026-05-25 07:00:00', '2026-05-26 03:00:00'),
(115, '17-2026', 76, 2, '07', '17', '2026-06-15', '2026-06-16', 2.00, 'Sốt đau mắt đỏ', 5, 2, NULL, '2026-06-15 07:00:00', '2026-06-15 08:30:00', '2026-06-15 00:00:00', '2026-06-15 01:30:00'),
(116, '18-2026', 77, 1, '07', '17', '2026-07-06', '2026-07-08', 3.00, 'Nghỉ phép đi tham quan', 5, 2, NULL, '2026-07-01 09:30:00', '2026-07-02 11:00:00', '2026-07-01 02:30:00', '2026-07-02 04:00:00'),
(117, '19-2026', 78, 3, '13', '17', '2026-07-20', '2026-07-20', 0.50, 'Đi đám cưới bạn thân', 5, 2, NULL, '2026-07-19 15:00:00', '2026-07-20 07:30:00', '2026-07-19 08:00:00', '2026-07-20 00:30:00'),
(118, '20-2026', 79, 1, '07', '17', '2026-09-07', '2026-09-09', 3.00, 'Nghỉ phép sau đợt công tác dài', 4, 1, 43, '2026-09-01 08:00:00', NULL, '2026-09-01 01:00:00', '2026-09-01 01:00:00'),
(119, '21-2026', 80, 2, '07', '17', '2026-09-21', '2026-09-22', 2.00, 'Nghỉ ốm điều trị tại nhà', 4, 1, 43, '2026-09-21 07:00:00', NULL, '2026-09-21 00:00:00', '2026-09-21 00:00:00'),
(120, '22-2026', 81, 1, '07', '17', '2026-10-05', '2026-10-06', 2.00, 'Nghỉ phép năm cá nhân', 4, 1, 43, '2026-09-30 10:00:00', NULL, '2026-09-30 03:00:00', '2026-09-30 03:00:00'),
(121, '23-2026', 82, 3, '07', '17', '2026-10-19', '2026-10-19', 1.00, 'Sửa chữa nhà cửa', 4, 1, 43, '2026-10-18 14:00:00', NULL, '2026-10-18 07:00:00', '2026-10-18 07:00:00'),
(122, '24-2026', 83, 1, '07', '17', '2026-11-09', '2026-11-11', 3.00, 'Nghỉ phép giải quyết công việc', 4, 1, 43, '2026-11-04 09:00:00', NULL, '2026-11-04 02:00:00', '2026-11-04 02:00:00'),
(123, '25-2026', 84, 2, '07', '17', '2026-11-23', '2026-11-23', 1.00, 'Nghỉ do nhức răng đi khám điều trị', 4, 1, 43, '2026-11-23 07:30:00', NULL, '2026-11-23 00:30:00', '2026-11-23 00:30:00'),
(124, '26-2026', 85, 1, '07', '17', '2026-12-14', '2026-12-18', 5.00, 'Nghỉ hết số phép còn lại trong năm', 4, 1, 43, '2026-12-08 08:30:00', NULL, '2026-12-08 01:30:00', '2026-12-08 01:30:00'),
(125, '27-2026', 64, 1, '07', '17', '2026-12-21', '2026-12-23', 3.00, 'Nghỉ phép gia đình', 4, 1, 23, '2026-12-15 10:00:00', NULL, '2026-12-15 03:00:00', '2026-12-15 03:00:00'),
(126, '28-2026', 68, 2, '07', '17', '2026-09-14', '2026-09-15', 2.00, 'Khám sức khỏe tổng quát', 5, 2, NULL, '2026-09-13 14:00:00', '2026-09-14 07:30:00', '2026-09-13 07:00:00', '2026-09-14 00:30:00'),
(127, '29-2026', 69, 3, '07', '17', '2026-10-12', '2026-10-12', 1.00, 'Giải quyết việc gia đình', 5, 2, NULL, '2026-10-10 08:00:00', '2026-10-11 15:00:00', '2026-10-10 01:00:00', '2026-10-11 08:00:00'),
(128, '30-2026', 70, 1, '07', '17', '2026-11-16', '2026-11-17', 2.00, 'Nghỉ phép cá nhân', 4, 1, 31, '2026-11-10 09:00:00', NULL, '2026-11-10 02:00:00', '2026-11-10 02:00:00'),
(129, '31-2026', 71, 2, '07', '17', '2026-12-01', '2026-12-02', 2.00, 'Nghỉ ốm điều trị', 5, 2, NULL, '2026-12-01 07:00:00', '2026-12-01 08:30:00', '2026-12-01 00:00:00', '2026-12-01 01:30:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhan_vien`
--

CREATE TABLE `nhan_vien` (
  `id` bigint(20) NOT NULL,
  `ma_nhan_vien` varchar(50) DEFAULT NULL,
  `ho_ten` varchar(255) NOT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `gioi_tinh` enum('Nam','Nu','Khac') DEFAULT 'Nam',
  `email` varchar(255) DEFAULT NULL,
  `so_dien_thoai` varchar(20) DEFAULT NULL,
  `dia_chi` text DEFAULT NULL,
  `id_phong_ban` bigint(20) NOT NULL,
  `id_chuc_vu` bigint(20) NOT NULL,
  `id_cap_tren` bigint(20) DEFAULT NULL,
  `ngay_vao_lam` date DEFAULT NULL,
  `ngay_nghi_viec` date DEFAULT NULL,
  `id_trang_thai` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `anh_dai_dien` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nhan_vien`
--

INSERT INTO `nhan_vien` (`id`, `ma_nhan_vien`, `ho_ten`, `ngay_sinh`, `gioi_tinh`, `email`, `so_dien_thoai`, `dia_chi`, `id_phong_ban`, `id_chuc_vu`, `id_cap_tren`, `ngay_vao_lam`, `ngay_nghi_viec`, `id_trang_thai`, `created_at`, `updated_at`, `deleted_at`, `anh_dai_dien`) VALUES
(54, 'dvan.cma', 'Đào Văn An', '1972-01-01', 'Nam', 'dvan.cma@gdt.gov.vn', NULL, NULL, 1, 6, NULL, '1991-05-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(55, 'tttrang.cma', 'Trần Thùy Trang', '1976-08-23', 'Nu', 'tttrang.cma@gdt.gov.vn', NULL, NULL, 1, 5, NULL, '2012-12-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(56, 'tmthuong.cma', 'Trương Mỹ Thương', '1985-10-14', 'Nu', 'tmthuong.cma@gdt.gov.vn', NULL, NULL, 2, 10, NULL, '2009-09-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(57, 'clhung.cma', 'Chung Long Hưng', '1978-10-06', 'Nam', 'clhung.cma@gdt.gov.vn', NULL, NULL, 2, 3, NULL, '1996-12-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-10 09:34:45', NULL, NULL),
(58, 'mctien.cma', 'Mã Chí Tiến', '2000-07-20', 'Nam', 'mctien.cma@gdt.gov.vn', NULL, NULL, 2, 2, NULL, '2024-10-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(59, 'nqtuong.cma', 'Nguyễn Quốc Tưởng', '1992-03-01', 'Nam', 'nqtuong.cma@gdt.gov.vn', NULL, NULL, 2, 2, NULL, '2024-10-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(60, 'tmtri.cma', 'Trịnh Minh Trí', '1996-02-24', 'Nam', 'tmtri.cma@gdt.gov.vn', NULL, NULL, 2, 2, NULL, '2024-10-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-10 09:34:26', NULL, 'avatars/q4IPWaYIGeZ6FahnodCESwznma922llK4uJo4kxY.jpg'),
(61, 'mtaloc.cma', 'Mã Thị An Lộc', '1990-10-30', 'Nu', 'mtaloc.cma@gdt.gov.vn', NULL, NULL, 2, 2, NULL, '2021-07-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(62, 'pnngan.cma', 'Phạm Ngọc Ngân', '1990-11-29', 'Nu', 'pnngan.cma@gdt.gov.vn', NULL, NULL, 2, 2, NULL, '2021-07-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(63, 'tdttvi.cma', 'Trần Đoàn Thanh Vị', '1988-10-22', 'Nam', 'tdtvi.cma@gdt.gov.vn', NULL, NULL, 2, 2, NULL, '2013-07-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(64, 'ttvu.cma', 'Thái Trường Vũ', '1975-03-05', 'Nam', 'ttvu.cma@gdt.gov.vn', NULL, NULL, 2, 2, NULL, '1994-01-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(65, 'hqhung.cma', 'Huỳnh Quốc Hưng', '1974-12-20', 'Nam', 'hqhung.cma@gdt.gov.vn', NULL, NULL, 5, 10, NULL, '1994-10-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(66, 'dqdat.cma', 'Đinh Quốc Đạt', '1975-10-25', 'Nam', 'dqdat.cma@gdt.gov.vn', NULL, NULL, 5, 3, NULL, '1995-12-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(67, 'tqtuan.cma', 'Trịnh Quốc Tuấn', '1979-08-17', 'Nam', 'tqtuan.cma@gdt.gov.vn', NULL, NULL, 4, 3, NULL, '2001-05-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(68, 'thhue.cma', 'Thái Hồng Huệ', '1988-05-26', 'Nu', 'thhhue.cma@gdt.gov.vn', NULL, NULL, 5, 2, NULL, '2021-07-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(69, 'ntvanh.cma', 'Nguyễn Thị Vân Anh', '1984-10-10', 'Nu', 'ntvanh.cma@gdt.gov.vn', NULL, NULL, 4, 2, NULL, '2004-10-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-09 18:53:13', NULL, NULL),
(70, 'tccuong.cma', 'Trần Chí Cường', '1991-08-06', 'Nam', 'tccuong.cma@gdt.gov.vn', NULL, NULL, 4, 2, NULL, '2023-06-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(71, 'ntduy.cma', 'Nguyễn Thúy Duy', '2001-01-01', 'Nu', 'ntduy.cma@gdt.gov.vn', NULL, NULL, 5, 2, NULL, '2024-02-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-09 18:53:47', NULL, NULL),
(72, 'dvpha.cma', 'Dương Văn Pha', '1971-02-28', 'Nam', 'dvpha.cma@gdt.gov.vn', NULL, NULL, 4, 10, NULL, '2009-07-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(73, 'lthien.cma', 'Lâm Thanh Hiền', '1980-09-01', 'Nu', 'lthien.cma@gdt.gov.vn', NULL, NULL, 4, 3, NULL, '2004-10-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(74, 'lvtoan.cma', 'Lâm Văn Toàn', '1979-09-27', 'Nam', 'lvtoan.cma@gdt.gov.vn', NULL, NULL, 4, 2, NULL, '2008-03-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(75, 'pnlai.cma', 'Phạm Ngọc Lài', '1986-07-01', 'Nu', 'pnlai.cma@gdt.gov.vn', NULL, NULL, 5, 2, NULL, '2024-02-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(76, 'tgphung.cma', 'Trần Giang Phụng', '1996-07-20', 'Nu', 'tgphung.cma@gdt.gov.vn', NULL, NULL, 4, 2, NULL, '2022-06-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(77, 'nptung.cma', 'Nguyễn Phương Tùng', '1990-01-08', 'Nam', 'nptung.cma@gdt.gov.vn', NULL, NULL, 4, 2, NULL, '2018-04-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(78, 'lththao.cma', 'Lê Thị Hiếu Thảo', '1982-03-29', 'Nu', 'lththao.cma@gdt.gov.vn', NULL, NULL, 5, 2, NULL, '2006-11-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(79, 'dnvien.cma', 'Đoàn Ngọc Viễn', '1978-04-12', 'Nam', 'dnvien.cma@gdt.gov.vn', NULL, NULL, 3, 10, NULL, '1999-11-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-09 18:55:02', NULL, NULL),
(80, 'lvmun.cma', 'Lê Văn Mun', '1980-05-20', 'Nam', 'lvmun.cma@gdt.gov.vn', NULL, NULL, 3, 3, NULL, '2006-03-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(81, 'ttduy.cma', 'Thái Trường Duy', '1979-11-09', 'Nam', 'ttduy.cma@gdt.gov.vn', NULL, NULL, 3, 3, NULL, '2004-10-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(82, 'lhthi.cma', 'Lâm Hoàng Thi', '1984-09-17', 'Nam', 'lhthi.cma@gdt.gov.vn', NULL, NULL, 3, 2, NULL, '2008-02-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(83, 'ctkhue.cma', 'Chiêm Thị Kim Huê', '1990-08-31', 'Nu', 'ctkhue.cma@gdt.gov.vn', NULL, NULL, 3, 2, NULL, '2013-07-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(84, 'pbha.cma', 'Phan Bích Hà', '1988-07-01', 'Nu', 'pbha.cma@gdt.gov.vn', NULL, NULL, 3, 2, NULL, '2014-07-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(85, 'pttthuy.cma', 'Phạm Thị Thu Thủy', '1984-03-23', 'Nu', 'pttthuy.cma@gdt.gov.vn', NULL, NULL, 3, 2, NULL, '2005-08-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(86, 'tbthuy.cma', 'Trần Bé Thúy', '1990-08-10', 'Nu', 'tbthuy.cma@gdt.gov.vn', NULL, NULL, 3, 2, NULL, '2013-07-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(87, 'tttlam.cma', 'Trần Thị Thùy Lam', '1984-03-22', 'Nu', 'tttlam.cma@gdt.gov.vn', NULL, NULL, 3, 2, NULL, '2009-08-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(88, 'vtlinh.cma', 'Võ Thị Lịnh', '1985-04-12', 'Nu', NULL, NULL, NULL, 2, 1, NULL, '2008-12-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(89, 'ptmlinh.cma', 'Phạm Thị Mỹ Linh', '1991-02-28', 'Nu', NULL, NULL, NULL, 2, 1, NULL, '2011-08-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(90, 'ntmduyen.cma', 'Nguyễn Thị Mỹ Duyên', '1975-04-01', 'Nu', NULL, NULL, NULL, 2, 1, NULL, '2006-03-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(91, 'nthue.cma', 'Nguyễn Thị Huệ', '1983-01-01', 'Nu', NULL, NULL, NULL, 2, 1, NULL, '2005-10-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(92, 'nmtruong.cma', 'Nguyễn Minh Trường', '1983-06-08', 'Nam', NULL, NULL, NULL, 2, 1, NULL, '2008-07-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL),
(93, 'qpminh.cma', 'Quách Phương Minh', '1980-03-13', 'Nam', NULL, NULL, NULL, 2, 1, NULL, '2007-07-01', NULL, 1, '2026-05-07 17:26:55', '2026-05-07 17:26:55', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhat_ky_he_thong`
--

CREATE TABLE `nhat_ky_he_thong` (
  `id` bigint(20) NOT NULL,
  `id_tai_khoan` bigint(20) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `hanh_dong` varchar(100) DEFAULT NULL,
  `id_ban_ghi` bigint(20) DEFAULT NULL,
  `du_lieu_cu` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`du_lieu_cu`)),
  `du_lieu_moi` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`du_lieu_moi`)),
  `ip` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `noi_den_cong_tac`
--

CREATE TABLE `noi_den_cong_tac` (
  `id` bigint(20) NOT NULL,
  `id_cong_tac` bigint(20) NOT NULL COMMENT 'ID giấy đi đường',
  `noi_den` varchar(255) NOT NULL COMMENT 'Địa chỉ nơi đến',
  `dia_chi` varchar(500) NOT NULL,
  `vi_do` decimal(10,8) DEFAULT NULL COMMENT 'Vĩ độ',
  `kinh_do` decimal(11,8) DEFAULT NULL COMMENT 'Kinh độ',
  `khoang_cach_km` decimal(10,2) DEFAULT NULL COMMENT 'Khoảng cách từ điểm xuất phát (km)',
  `thoi_gian_phut` int(11) DEFAULT NULL COMMENT 'Thời gian di chuyển (phút)',
  `thu_tu` int(11) DEFAULT 0 COMMENT 'Thứ tự các nơi đến trong chuyến công tác',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu nhiều nơi đến cho giấy đi đường';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(161, 'App\\Models\\TaiKhoan', 16, 'auth_token', '51dc7034df4bf942d1758e900b74390f24ee8f8e5c64c1efd556fe13fce9ca8e', '[\"*\"]', '2026-05-11 01:36:03', NULL, '2026-05-11 00:02:50', '2026-05-11 01:36:03'),
(162, 'App\\Models\\TaiKhoan', 24, 'auth_token', 'ebf69ff44d2131ce042e351df2ab2f1b250dd5d95d34f5233fcca8434323b7f8', '[\"*\"]', '2026-05-11 00:18:33', NULL, '2026-05-11 00:15:44', '2026-05-11 00:18:33'),
(163, 'App\\Models\\TaiKhoan', 16, 'auth_token', '92fb64fd6e8c1e2644de92738bdd8c7680991c0bc596c809262029f8007c389c', '[\"*\"]', '2026-05-11 00:43:04', NULL, '2026-05-11 00:41:05', '2026-05-11 00:43:04'),
(164, 'App\\Models\\TaiKhoan', 16, 'auth_token', 'a159aa9a9418b0dc8c5ed2de8fcbf3e598b5664feb99cf0b29237cc59c71bea4', '[\"*\"]', '2026-05-11 00:46:09', NULL, '2026-05-11 00:43:12', '2026-05-11 00:46:09'),
(165, 'App\\Models\\TaiKhoan', 16, 'auth_token', '5bdcf12b37abb613b712bb2682cd52cfe60a3817d43b6cabd7f69872476355fd', '[\"*\"]', '2026-08-04 21:32:32', NULL, '2026-08-04 20:45:31', '2026-08-04 21:32:32'),
(166, 'App\\Models\\TaiKhoan', 16, 'auth_token', 'a819be83c6806e90f1c53f0b752b6e90670f86b61f168a341c3c4acfbabdaa78', '[\"*\"]', '2026-08-10 02:52:40', NULL, '2026-08-09 23:58:49', '2026-08-10 02:52:40'),
(167, 'App\\Models\\TaiKhoan', 16, 'auth_token', '63477a642be0f54f4d764a0233b9f8ceeb1995179a85b7006973be01158dabc3', '[\"*\"]', '2026-08-10 01:50:17', NULL, '2026-08-10 00:59:04', '2026-08-10 01:50:17');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phan_quyen_vai_tro`
--

CREATE TABLE `phan_quyen_vai_tro` (
  `id` bigint(20) NOT NULL,
  `id_vai_tro` bigint(20) NOT NULL,
  `id_quyen` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phan_quyen_vai_tro`
--

INSERT INTO `phan_quyen_vai_tro` (`id`, `id_vai_tro`, `id_quyen`) VALUES
(74, 14, 44),
(86, 14, 45),
(82, 14, 46),
(85, 14, 47),
(83, 14, 48),
(81, 14, 49),
(99, 14, 51),
(95, 14, 52),
(98, 14, 53),
(96, 14, 54),
(97, 14, 55),
(80, 14, 56),
(75, 14, 57),
(79, 14, 58),
(76, 14, 59),
(78, 14, 60),
(77, 14, 61),
(73, 14, 62),
(70, 14, 63),
(72, 14, 64),
(71, 14, 65),
(88, 14, 66),
(87, 14, 67),
(94, 14, 68),
(93, 14, 69),
(90, 14, 70),
(92, 14, 71),
(91, 14, 72),
(89, 14, 73),
(69, 14, 74),
(66, 14, 75),
(68, 14, 76),
(67, 14, 77),
(108, 14, 78),
(103, 14, 79),
(104, 14, 80),
(102, 14, 81);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phien_dang_nhap`
--

CREATE TABLE `phien_dang_nhap` (
  `id` bigint(20) NOT NULL,
  `id_tai_khoan` bigint(20) NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `thiet_bi` varchar(255) DEFAULT NULL,
  `het_han_luc` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phong_ban`
--

CREATE TABLE `phong_ban` (
  `id` bigint(20) NOT NULL,
  `ma_phong` varchar(50) DEFAULT NULL,
  `ten_phong` varchar(255) NOT NULL,
  `ten_tat` varchar(100) NOT NULL,
  `id_phong_cha` bigint(20) DEFAULT NULL,
  `thu_tu_cap` int(11) DEFAULT 1,
  `id_trang_thai` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phong_ban`
--

INSERT INTO `phong_ban` (`id`, `ma_phong`, `ten_phong`, `ten_tat`, `id_phong_cha`, `thu_tu_cap`, `id_trang_thai`, `created_at`, `updated_at`) VALUES
(1, 'BLĐ', 'Ban Lãnh đạo', 'BLĐ', NULL, 1, NULL, '2026-04-29 07:43:31', '2026-04-29 07:43:31'),
(2, 'NVDPC', 'Tổ Nghiệp vụ, dự toán, pháp chế', 'Tổ NVDPC', NULL, 2, NULL, '2026-04-29 07:47:53', '2026-04-29 07:47:53'),
(3, 'QLHTDN', 'Tổ Quản lý, hỗ trợ doanh nghiệp', 'Tổ QLHTDN', NULL, 3, NULL, '2026-04-29 07:48:40', '2026-04-29 07:48:40'),
(4, 'QLHTCNHKD', 'Tổ Quản lý, hỗ trợ cá nhân, hộ kinh doanh', 'Tổ QLHTCNHKD', NULL, 4, NULL, '2026-04-29 07:49:22', '2026-04-29 07:50:45'),
(5, 'QLCKTK', 'Tổ Quản lý các khoản thu khác', 'Tổ QLCKTK', NULL, 5, NULL, '2026-04-29 07:50:18', '2026-04-29 07:50:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quyen`
--

CREATE TABLE `quyen` (
  `id` bigint(20) NOT NULL,
  `module` varchar(100) DEFAULT NULL,
  `ma_quyen` varchar(100) DEFAULT NULL,
  `ten_quyen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `quyen`
--

INSERT INTO `quyen` (`id`, `module`, `ma_quyen`, `ten_quyen`) VALUES
(44, 'dashboard', 'dashboard.view', 'Xem trang chủ'),
(45, 'leave.approve', 'leave.approve.view', 'Xem nghỉ phép'),
(46, 'leave.apply', 'leave.apply.create', 'Tạo đơn nghỉ phép'),
(47, 'leave.approve', 'leave.approve.update', 'Sửa đơn nghỉ phép'),
(48, 'leave.approve', 'leave.approve.delete', 'Xóa đơn nghỉ phép'),
(49, 'leave.approve', 'leave.approve.approve', 'Duyệt nghỉ phép'),
(51, 'trip', 'trip.view', 'Xem công tác'),
(52, 'trip', 'trip.create', 'Tạo công tác'),
(53, 'trip', 'trip.update', 'Sửa công tác'),
(54, 'trip', 'trip.delete', 'Xóa công tác'),
(55, 'trip', 'trip.export', 'Xuất Excel công tác'),
(56, 'employee', 'employee.view', 'Xem nhân viên'),
(57, 'employee', 'employee.create', 'Thêm nhân viên'),
(58, 'employee', 'employee.update', 'Sửa nhân viên'),
(59, 'employee', 'employee.delete', 'Xóa nhân viên'),
(60, 'employee', 'employee.reset_password', 'Đặt lại mật khẩu'),
(61, 'employee', 'employee.export', 'Xuất Excel nhân viên'),
(62, 'catalog', 'catalog.view', 'Xem danh mục'),
(63, 'catalog', 'catalog.create', 'Thêm danh mục'),
(64, 'catalog', 'catalog.update', 'Sửa danh mục'),
(65, 'catalog', 'catalog.delete', 'Xóa danh mục'),
(66, 'report', 'report.view', 'Xem báo cáo'),
(67, 'report', 'report.export', 'Xuất báo cáo'),
(68, 'system', 'system.config', 'Cấu hình hệ thống'),
(69, 'system', 'role.view', 'Xem phân quyền'),
(70, 'system', 'role.create', 'Tạo vai trò'),
(71, 'system', 'role.update', 'Sửa vai trò'),
(72, 'system', 'role.delete', 'Xóa vai trò'),
(73, 'system', 'role.assign_permission', 'Gán quyền vai trò'),
(74, 'system', 'account.view', 'Xem tài khoản'),
(75, 'system', 'account.create', 'Tạo tài khoản'),
(76, 'system', 'account.update', 'Sửa tài khoản'),
(77, 'system', 'account.delete', 'Xóa tài khoản'),
(78, 'leave', 'leave.balance', 'Số dư phép năm'),
(79, 'leave.balance', 'leave.balance.create', 'Tạo phép năm'),
(80, 'leave.balance', 'leave.balance.transfer', 'Kết chuyển phép tồn'),
(81, 'leave.balance', 'leave.balance.edit', 'Cập nhật số dư phép');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `so_du_phep`
--

CREATE TABLE `so_du_phep` (
  `id` bigint(20) NOT NULL,
  `id_nhan_vien` bigint(20) NOT NULL,
  `nam` int(11) NOT NULL,
  `tong_ngay` decimal(5,2) DEFAULT 12.00,
  `da_dung` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `so_du_phep`
--

INSERT INTO `so_du_phep` (`id`, `id_nhan_vien`, `nam`, `tong_ngay`, `da_dung`) VALUES
(9, 54, 2026, 19.00, 0.00),
(10, 55, 2026, 14.00, 0.00),
(11, 56, 2026, 15.00, 0.00),
(12, 57, 2026, 18.00, 0.00),
(13, 58, 2026, 12.00, 0.00),
(14, 59, 2026, 12.00, 0.00),
(15, 60, 2026, 12.00, 3.00),
(16, 61, 2026, 13.00, 0.00),
(17, 62, 2026, 13.00, 0.00),
(18, 63, 2026, 14.00, 0.00),
(19, 64, 2026, 18.00, 0.00),
(20, 65, 2026, 18.00, 0.00),
(21, 66, 2026, 18.00, 0.00),
(22, 67, 2026, 17.00, 0.00),
(23, 68, 2026, 13.00, 0.00),
(24, 69, 2026, 16.00, 0.00),
(25, 70, 2026, 12.00, 0.00),
(26, 71, 2026, 12.00, 0.00),
(27, 72, 2026, 15.00, 0.00),
(28, 73, 2026, 16.00, 0.00),
(29, 74, 2026, 15.00, 0.00),
(30, 75, 2026, 12.00, 0.00),
(31, 76, 2026, 12.00, 0.00),
(32, 77, 2026, 13.00, 0.00),
(33, 78, 2026, 16.00, 0.00),
(34, 79, 2026, 17.00, 0.00),
(35, 80, 2026, 16.00, 0.00),
(36, 81, 2026, 16.00, 0.00),
(37, 82, 2026, 15.00, 0.00),
(38, 83, 2026, 14.00, 0.00),
(39, 84, 2026, 14.00, 0.00),
(40, 85, 2026, 16.00, 0.00),
(41, 86, 2026, 14.00, 0.00),
(42, 87, 2026, 15.00, 0.00),
(43, 88, 2026, 15.00, 0.00),
(44, 89, 2026, 15.00, 0.00),
(45, 90, 2026, 16.00, 0.00),
(46, 91, 2026, 16.00, 0.00),
(47, 92, 2026, 15.00, 0.00),
(48, 93, 2026, 15.00, 0.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tai_khoan`
--

CREATE TABLE `tai_khoan` (
  `id` bigint(20) NOT NULL,
  `id_nhan_vien` bigint(20) NOT NULL,
  `ten_dang_nhap` varchar(100) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `dang_hoat_dong` tinyint(4) DEFAULT 1,
  `lan_dang_nhap_cuoi` datetime DEFAULT NULL,
  `ip_cuoi` varchar(50) DEFAULT NULL,
  `doi_mat_khau_luc` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tai_khoan`
--

INSERT INTO `tai_khoan` (`id`, `id_nhan_vien`, `ten_dang_nhap`, `mat_khau`, `dang_hoat_dong`, `lan_dang_nhap_cuoi`, `ip_cuoi`, `doi_mat_khau_luc`, `created_at`, `updated_at`) VALUES
(16, 60, 'tmtri.cma', '$2y$12$PzlJXlC99hGGpqXr2sho5ur9Jity1KsPZPr/i7XWNat5vZv4T0ij6', 1, NULL, NULL, NULL, '2026-05-07 17:28:24', '2026-05-10 10:37:58'),
(19, 59, 'nqtuong.cma', '$2y$12$i8DvmGNJ1T5Gqi/kZXD5Zuhc6iXqBfYrqcUvo5INK4nLfClMiA9D2', 1, NULL, NULL, NULL, '2026-05-09 11:23:31', '2026-05-09 11:23:31'),
(20, 54, 'dvan.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(21, 55, 'tttrang.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(22, 56, 'tmthuong.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(23, 57, 'clhung.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(24, 58, 'mctien.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(25, 61, 'mtaloc.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(26, 62, 'pnngan.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(27, 63, 'tdtvi.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(28, 64, 'ttvu.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(29, 65, 'hqhung.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(30, 66, 'dqdat.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(31, 67, 'tqtuan.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(32, 68, 'thhue.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(33, 69, 'ntvanh.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(34, 70, 'tccuong.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(35, 71, 'ntduy.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(36, 72, 'dvpha.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(37, 73, 'lthien.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(38, 74, 'lvtoan.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(39, 75, 'pnlai.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(40, 76, 'tgphung.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(41, 77, 'nptung.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(42, 78, 'lththao.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(43, 79, 'dnvien.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(44, 80, 'lvmun.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(45, 81, 'ttduy.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(46, 82, 'lhthi.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(47, 83, 'ctkhue.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(48, 84, 'pbha.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(49, 85, 'pttthuy.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(50, 86, 'tbthuy.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(51, 87, 'tttlam.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(52, 88, 'vtlinh.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(53, 89, 'ptmlinh.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(54, 90, 'ntmduyen.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(55, 91, 'nthue.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(56, 92, 'nmtruong.cma', '$2y$10$2b7Wq80MTjD.Zh8M.pd6Z.68QxrfCVcpd.IyqixyMxR.Bz4eHfuR.', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-09 18:59:13'),
(57, 93, 'qpminh.cma', '$2y$12$CphL/NZUEbAGZOET9NexFe9.ATMnEukQWwNO6z0vJaG4G0yMx6I0C', 1, NULL, NULL, NULL, '2026-05-09 18:59:13', '2026-05-10 04:51:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tai_khoan_vai_tro`
--

CREATE TABLE `tai_khoan_vai_tro` (
  `id` bigint(20) NOT NULL,
  `id_tai_khoan` bigint(20) NOT NULL,
  `id_vai_tro` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tai_khoan_vai_tro`
--

INSERT INTO `tai_khoan_vai_tro` (`id`, `id_tai_khoan`, `id_vai_tro`) VALUES
(8, 16, 14),
(9, 19, 19),
(10, 20, 15),
(12, 21, 16),
(26, 22, 17),
(27, 23, 18),
(28, 24, 14),
(22, 25, 19),
(21, 26, 19),
(20, 27, 19),
(19, 28, 19),
(52, 29, 17),
(53, 30, 18),
(49, 31, 18),
(57, 32, 19),
(47, 33, 19),
(46, 34, 19),
(56, 35, 19),
(51, 36, 17),
(50, 37, 18),
(43, 38, 19),
(55, 39, 19),
(42, 40, 19),
(41, 41, 19),
(54, 42, 19),
(38, 43, 17),
(39, 44, 18),
(40, 45, 18),
(34, 46, 19),
(33, 47, 19),
(32, 48, 19),
(31, 49, 19),
(30, 50, 19),
(29, 51, 19),
(18, 52, 19),
(17, 53, 19),
(16, 54, 19),
(15, 55, 19),
(14, 56, 19),
(13, 57, 19);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `trang_thai_danh_muc`
--

CREATE TABLE `trang_thai_danh_muc` (
  `id` bigint(20) NOT NULL,
  `module` varchar(100) NOT NULL,
  `ma_trang_thai` varchar(100) NOT NULL,
  `ten_trang_thai` varchar(255) NOT NULL,
  `mau_sac` varchar(30) DEFAULT NULL,
  `thu_tu` int(11) DEFAULT 0,
  `mac_dinh` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `trang_thai_danh_muc`
--

INSERT INTO `trang_thai_danh_muc` (`id`, `module`, `ma_trang_thai`, `ten_trang_thai`, `mau_sac`, `thu_tu`, `mac_dinh`) VALUES
(1, 'employee', 'dang_lam', 'Đang làm', 'green', 1, 1),
(2, 'employee', 'tam_nghi', 'Tạm nghỉ', 'orange', 2, 0),
(3, 'employee', 'nghi_viec', 'Nghỉ việc', 'red', 3, 0),
(4, 'leave', 'cho_duyet', 'Chờ duyệt', 'orange', 1, 1),
(5, 'leave', 'da_duyet', 'Đã duyệt', 'green', 2, 0),
(6, 'leave', 'tu_choi', 'Từ chối', 'red', 3, 0),
(7, 'leave', 'da_huy', 'Đã huỷ', 'gray', 4, 0),
(8, 'trip', 'da_tao', 'Đã tạo', 'blue', 1, 1),
(9, 'trip', 'dang_di', 'Đang đi', 'orange', 2, 0),
(10, 'trip', 'hoan_thanh', 'Hoàn thành', 'green', 3, 0),
(11, 'menu', 'hoat_dong', 'Hoạt động', 'green', 1, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vai_tro`
--

CREATE TABLE `vai_tro` (
  `id` bigint(20) NOT NULL,
  `ma_vai_tro` varchar(50) DEFAULT NULL,
  `ten_vai_tro` varchar(255) NOT NULL,
  `mo_ta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `vai_tro`
--

INSERT INTO `vai_tro` (`id`, `ma_vai_tro`, `ten_vai_tro`, `mo_ta`) VALUES
(14, 'quan_tri', 'Quản trị hệ thống', 'Toàn quyền hệ thống'),
(15, 'thu_truong', 'Thủ trưởng', 'Người đứng đầu đơn vị'),
(16, 'pho_thu_truong', 'Phó thủ trưởng', 'Cấp phó đơn vị'),
(17, 'truong_phong', 'Trưởng phòng', 'Quản lý phòng ban'),
(18, 'pho_truong_phong', 'Phó trưởng phòng', 'Cấp phó phòng ban'),
(19, 'nhan_vien', 'Nhân viên', 'Người dùng thông thường');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cau_hinh`
--
ALTER TABLE `cau_hinh`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `khoa_cau_hinh` (`khoa_cau_hinh`);

--
-- Chỉ mục cho bảng `chuc_vu`
--
ALTER TABLE `chuc_vu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_chuc_vu` (`ma_chuc_vu`);

--
-- Chỉ mục cho bảng `cong_tac`
--
ALTER TABLE `cong_tac`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_nhan_vien` (`id_nhan_vien`);

--
-- Chỉ mục cho bảng `lich_su_duyet_nghi`
--
ALTER TABLE `lich_su_duyet_nghi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lsdn_np` (`id_nghi_phep`),
  ADD KEY `fk_lsdn_tk` (`id_tai_khoan`);

--
-- Chỉ mục cho bảng `loai_nghi`
--
ALTER TABLE `loai_nghi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_loai` (`ma_loai`);

--
-- Chỉ mục cho bảng `luong_duyet`
--
ALTER TABLE `luong_duyet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ld_cv1` (`id_chuc_vu_ap_dung`),
  ADD KEY `fk_ld_cv2` (`id_chuc_vu_duyet`);

--
-- Chỉ mục cho bảng `menu_he_thong`
--
ALTER TABLE `menu_he_thong`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_menu` (`ma_menu`),
  ADD KEY `fk_menu_cha` (`id_cha`),
  ADD KEY `fk_menu_tt` (`id_trang_thai`);

--
-- Chỉ mục cho bảng `menu_vai_tro`
--
ALTER TABLE `menu_vai_tro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_menu_vt` (`id_menu`,`id_vai_tro`),
  ADD KEY `fk_mvt_vt` (`id_vai_tro`);

--
-- Chỉ mục cho bảng `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `nghi_phep`
--
ALTER TABLE `nghi_phep`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_np_nv` (`id_nhan_vien`),
  ADD KEY `fk_np_ln` (`id_loai_nghi`),
  ADD KEY `fk_np_tt` (`id_trang_thai`),
  ADD KEY `fk_np_duyet` (`id_nguoi_duyet_hien_tai`),
  ADD KEY `idx_so_don_nghi` (`so_don_nghi`);

--
-- Chỉ mục cho bảng `nhan_vien`
--
ALTER TABLE `nhan_vien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_nhan_vien` (`ma_nhan_vien`),
  ADD KEY `fk_nv_pb` (`id_phong_ban`),
  ADD KEY `fk_nv_cv` (`id_chuc_vu`),
  ADD KEY `fk_nv_captren` (`id_cap_tren`),
  ADD KEY `fk_nv_tt` (`id_trang_thai`);

--
-- Chỉ mục cho bảng `nhat_ky_he_thong`
--
ALTER TABLE `nhat_ky_he_thong`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_nkht_tk` (`id_tai_khoan`);

--
-- Chỉ mục cho bảng `noi_den_cong_tac`
--
ALTER TABLE `noi_den_cong_tac`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cong_tac` (`id_cong_tac`),
  ADD KEY `idx_thu_tu` (`thu_tu`);

--
-- Chỉ mục cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Chỉ mục cho bảng `phan_quyen_vai_tro`
--
ALTER TABLE `phan_quyen_vai_tro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_vt_q` (`id_vai_tro`,`id_quyen`),
  ADD KEY `fk_pq_q` (`id_quyen`);

--
-- Chỉ mục cho bảng `phien_dang_nhap`
--
ALTER TABLE `phien_dang_nhap`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pdn_tk` (`id_tai_khoan`);

--
-- Chỉ mục cho bảng `phong_ban`
--
ALTER TABLE `phong_ban`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_phong` (`ma_phong`),
  ADD KEY `fk_pb_cha` (`id_phong_cha`),
  ADD KEY `fk_pb_tt` (`id_trang_thai`);

--
-- Chỉ mục cho bảng `quyen`
--
ALTER TABLE `quyen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_quyen` (`ma_quyen`);

--
-- Chỉ mục cho bảng `so_du_phep`
--
ALTER TABLE `so_du_phep`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sdp` (`id_nhan_vien`,`nam`);

--
-- Chỉ mục cho bảng `tai_khoan`
--
ALTER TABLE `tai_khoan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`);

--
-- Chỉ mục cho bảng `tai_khoan_vai_tro`
--
ALTER TABLE `tai_khoan_vai_tro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tk_vt` (`id_tai_khoan`,`id_vai_tro`),
  ADD KEY `fk_tkvt_vt` (`id_vai_tro`);

--
-- Chỉ mục cho bảng `trang_thai_danh_muc`
--
ALTER TABLE `trang_thai_danh_muc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tt` (`module`,`ma_trang_thai`);

--
-- Chỉ mục cho bảng `vai_tro`
--
ALTER TABLE `vai_tro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_vai_tro` (`ma_vai_tro`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cau_hinh`
--
ALTER TABLE `cau_hinh`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chuc_vu`
--
ALTER TABLE `chuc_vu`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `cong_tac`
--
ALTER TABLE `cong_tac`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT cho bảng `lich_su_duyet_nghi`
--
ALTER TABLE `lich_su_duyet_nghi`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `loai_nghi`
--
ALTER TABLE `loai_nghi`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `luong_duyet`
--
ALTER TABLE `luong_duyet`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `menu_he_thong`
--
ALTER TABLE `menu_he_thong`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `menu_vai_tro`
--
ALTER TABLE `menu_vai_tro`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT cho bảng `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `nghi_phep`
--
ALTER TABLE `nghi_phep`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT cho bảng `nhan_vien`
--
ALTER TABLE `nhan_vien`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT cho bảng `nhat_ky_he_thong`
--
ALTER TABLE `nhat_ky_he_thong`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `noi_den_cong_tac`
--
ALTER TABLE `noi_den_cong_tac`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT cho bảng `phan_quyen_vai_tro`
--
ALTER TABLE `phan_quyen_vai_tro`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT cho bảng `phien_dang_nhap`
--
ALTER TABLE `phien_dang_nhap`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `phong_ban`
--
ALTER TABLE `phong_ban`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `quyen`
--
ALTER TABLE `quyen`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT cho bảng `so_du_phep`
--
ALTER TABLE `so_du_phep`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT cho bảng `tai_khoan`
--
ALTER TABLE `tai_khoan`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT cho bảng `tai_khoan_vai_tro`
--
ALTER TABLE `tai_khoan_vai_tro`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT cho bảng `trang_thai_danh_muc`
--
ALTER TABLE `trang_thai_danh_muc`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `vai_tro`
--
ALTER TABLE `vai_tro`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `lich_su_duyet_nghi`
--
ALTER TABLE `lich_su_duyet_nghi`
  ADD CONSTRAINT `fk_lsdn_np` FOREIGN KEY (`id_nghi_phep`) REFERENCES `nghi_phep` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lsdn_tk` FOREIGN KEY (`id_tai_khoan`) REFERENCES `tai_khoan` (`id`);

--
-- Các ràng buộc cho bảng `luong_duyet`
--
ALTER TABLE `luong_duyet`
  ADD CONSTRAINT `fk_ld_cv1` FOREIGN KEY (`id_chuc_vu_ap_dung`) REFERENCES `chuc_vu` (`id`),
  ADD CONSTRAINT `fk_ld_cv2` FOREIGN KEY (`id_chuc_vu_duyet`) REFERENCES `chuc_vu` (`id`);

--
-- Các ràng buộc cho bảng `menu_he_thong`
--
ALTER TABLE `menu_he_thong`
  ADD CONSTRAINT `fk_menu_cha` FOREIGN KEY (`id_cha`) REFERENCES `menu_he_thong` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_menu_tt` FOREIGN KEY (`id_trang_thai`) REFERENCES `trang_thai_danh_muc` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `menu_vai_tro`
--
ALTER TABLE `menu_vai_tro`
  ADD CONSTRAINT `fk_mvt_menu` FOREIGN KEY (`id_menu`) REFERENCES `menu_he_thong` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mvt_vt` FOREIGN KEY (`id_vai_tro`) REFERENCES `vai_tro` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `nghi_phep`
--
ALTER TABLE `nghi_phep`
  ADD CONSTRAINT `fk_np_duyet` FOREIGN KEY (`id_nguoi_duyet_hien_tai`) REFERENCES `tai_khoan` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_np_ln` FOREIGN KEY (`id_loai_nghi`) REFERENCES `loai_nghi` (`id`),
  ADD CONSTRAINT `fk_np_nv` FOREIGN KEY (`id_nhan_vien`) REFERENCES `nhan_vien` (`id`),
  ADD CONSTRAINT `fk_np_tt` FOREIGN KEY (`id_trang_thai`) REFERENCES `trang_thai_danh_muc` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `nhan_vien`
--
ALTER TABLE `nhan_vien`
  ADD CONSTRAINT `fk_nv_captren` FOREIGN KEY (`id_cap_tren`) REFERENCES `nhan_vien` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_nv_cv` FOREIGN KEY (`id_chuc_vu`) REFERENCES `chuc_vu` (`id`),
  ADD CONSTRAINT `fk_nv_pb` FOREIGN KEY (`id_phong_ban`) REFERENCES `phong_ban` (`id`),
  ADD CONSTRAINT `fk_nv_tt` FOREIGN KEY (`id_trang_thai`) REFERENCES `trang_thai_danh_muc` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `nhat_ky_he_thong`
--
ALTER TABLE `nhat_ky_he_thong`
  ADD CONSTRAINT `fk_nkht_tk` FOREIGN KEY (`id_tai_khoan`) REFERENCES `tai_khoan` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `noi_den_cong_tac`
--
ALTER TABLE `noi_den_cong_tac`
  ADD CONSTRAINT `fk_noi_den_cong_tac` FOREIGN KEY (`id_cong_tac`) REFERENCES `cong_tac` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `phan_quyen_vai_tro`
--
ALTER TABLE `phan_quyen_vai_tro`
  ADD CONSTRAINT `fk_pq_q` FOREIGN KEY (`id_quyen`) REFERENCES `quyen` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pq_vt` FOREIGN KEY (`id_vai_tro`) REFERENCES `vai_tro` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `phien_dang_nhap`
--
ALTER TABLE `phien_dang_nhap`
  ADD CONSTRAINT `fk_pdn_tk` FOREIGN KEY (`id_tai_khoan`) REFERENCES `tai_khoan` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `phong_ban`
--
ALTER TABLE `phong_ban`
  ADD CONSTRAINT `fk_pb_cha` FOREIGN KEY (`id_phong_cha`) REFERENCES `phong_ban` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pb_tt` FOREIGN KEY (`id_trang_thai`) REFERENCES `trang_thai_danh_muc` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `so_du_phep`
--
ALTER TABLE `so_du_phep`
  ADD CONSTRAINT `fk_sdp_nv` FOREIGN KEY (`id_nhan_vien`) REFERENCES `nhan_vien` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `tai_khoan_vai_tro`
--
ALTER TABLE `tai_khoan_vai_tro`
  ADD CONSTRAINT `fk_tkvt_tk` FOREIGN KEY (`id_tai_khoan`) REFERENCES `tai_khoan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tkvt_vt` FOREIGN KEY (`id_vai_tro`) REFERENCES `vai_tro` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
