-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2025 at 12:19 PM
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
-- Database: `kilimoconnect`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `delivery_address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `buyer_id`, `farmer_id`, `total_amount`, `status`, `delivery_address`, `created_at`, `updated_at`) VALUES
(1, 8, 6, 240000.00, 'cancelled', 'Moshi, Kilimanjaro - Majengo', '2025-04-10 20:21:33', '2025-04-10 20:32:00'),
(2, 8, 6, 2000.00, 'cancelled', 'moshi', '2025-04-10 20:23:15', '2025-04-10 20:24:44'),
(3, 8, 6, 45000.00, 'pending', 'Rau sokoni', '2025-04-10 21:19:33', '2025-04-10 21:19:33'),
(4, 10, 6, 200000.00, 'pending', 'moshi rau', '2025-04-11 18:12:34', '2025-04-11 18:12:34'),
(5, 10, 9, 10299000.00, 'pending', 'moshi', '2025-04-11 18:15:03', '2025-04-11 18:15:03');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 4, 120, 2000.00),
(2, 2, 4, 1, 2000.00),
(3, 3, 8, 9, 5000.00),
(4, 4, 4, 100, 2000.00),
(5, 5, 9, 3000, 3433.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('mpesa','airtel_money') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `status` enum('available','out_of_stock','hidden') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `farmer_id`, `name`, `description`, `category`, `price`, `stock_quantity`, `unit`, `status`, `created_at`, `updated_at`) VALUES
(4, 6, 'Fenesi', 'Mafenesi Matamu kutoka moshi', 'fruits', 2000.00, 200, 'piece', 'available', '2025-04-10 19:31:31', '2025-04-11 18:12:34'),
(5, 6, 'Mahindi', 'Bei powa kabisa', 'fruits', 2000.00, 4099, 'kg', 'out_of_stock', '2025-04-10 19:32:37', '2025-04-10 19:32:37'),
(6, 6, 'fenesi', 'tamu sana', 'other', 4000.00, 554, 'kg', 'available', '2025-04-10 19:44:20', '2025-04-10 20:00:50'),
(7, 6, 'alvin', 'eqf', 'vegetables', 343.00, 324, 'dozen', 'out_of_stock', '2025-04-10 20:01:29', '2025-04-10 20:01:29'),
(8, 6, 'trial', 'trial', 'meat', 5000.00, 25, 'kg', 'available', '2025-04-10 20:48:48', '2025-04-10 21:19:33'),
(9, 9, 'trial', 'trial', 'dairy', 3433.00, 29333, 'l', 'available', '2025-04-11 18:14:32', '2025-04-11 18:15:03');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `created_at`) VALUES
(6, 4, '67f81c9378f96.jpeg', 1, '2025-04-10 19:31:31'),
(7, 5, '67f81cd52f438.jpg', 1, '2025-04-10 19:32:37'),
(11, 6, '67f81fc81b43c.jpg', 1, '2025-04-10 19:45:12'),
(12, 6, '67f823721f6e0.jpg', 0, '2025-04-10 20:00:50'),
(13, 7, '67f823999cedd.jpeg', 1, '2025-04-10 20:01:29'),
(14, 8, '67f82eb0a5059.jpg', 1, '2025-04-10 20:48:48'),
(15, 9, '67f95c08a6f4e.jpg', 1, '2025-04-11 18:14:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','farmer','buyer') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `first_name`, `last_name`, `phone`, `address`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@kilimoconnect.com', '$2y$12$YourHashedPasswordHere', 'admin', 'System', 'Administrator', '254700000000', NULL, NULL, 'active', '2025-04-09 22:12:32', '2025-04-09 22:12:32'),
(2, 'alvin', 'alvinchipmunk196@gmail.com', '$2y$12$m.i2pwReVyP9qZxMERfZ9eHX86bJXvBxfTJAv1KMFfSiVuatXR5ei', 'farmer', 'Alvin', 'Calvin', '255628030877', 'Moshi', NULL, 'active', '2025-04-09 22:15:38', '2025-04-09 22:15:38'),
(3, 'mama', 'mama@gmail.com', '$2y$12$MSGpSu13rcNzWgSPRY/VH.WXZ49pHnhIVEWJgyGXigf2O/fp.Sl6W', 'buyer', 'mama', 'kaka', '0628030877', 'Moshi', NULL, 'active', '2025-04-09 22:16:28', '2025-04-09 22:16:28'),
(4, 'nana', 'nana@gmail.com', '$2y$12$u5Fe0LyiLC9rfR5P8hCHoOiFF7E4Z5WPUJsDriOdLD3NUvITBAveC', 'buyer', 'nana', 'nana', '0628030877', 'Kilimanjaro, Tanzania', NULL, 'active', '2025-04-09 22:26:48', '2025-04-09 22:26:48'),
(5, 'alvin121', 'alvin@gmail.com', '$2y$10$z58bc55TJyNqxftPWT9Np.UYh3Tn16Z0Ux5/0oYYiATGFG33sMdm.', 'farmer', '', '', '', NULL, NULL, 'active', '2025-04-09 22:34:51', '2025-04-09 22:34:51'),
(6, 'luiza', 'luiza@gmail.com', '$2y$10$6r/lhAdAnIIIV0V1YFYEvO31zjYAR4OQOphomiSnk/HWIB4vhbjYS', 'farmer', 'LUIZA', 'MARTINI', '0656566655', 'Mapinga', NULL, 'active', '2025-04-10 13:22:30', '2025-04-10 13:22:30'),
(7, 'trial', 'trial@gmail.ocm', '$2y$10$QCa05aDRzwtytN1HX9.NdOXUFlSmj4UZNRFKdmVEo845ldizLfsSW', 'buyer', 'trial', 'trial', '848484848484', 'trial', NULL, 'active', '2025-04-10 13:26:30', '2025-04-10 13:26:30'),
(8, 'lala', 'lala@gmail.com', '$2y$10$Dzfs3.nk0SwaQdonLx.0Te438MJRiDJeL22xJDBpsYzyQUZ3tW5ly', 'buyer', 'lala', 'lala', '0628030877', '12 moshi', NULL, 'active', '2025-04-10 19:21:23', '2025-04-10 19:21:23'),
(9, 'trial2', 'trial2@gmail.com', '$2y$10$gs8.aDxEobpqtcUa0dPrJui4bjKrf04Q1J7mAH9ZnEKWvD7lhI3py', 'farmer', 'trial2', 'trial2', '7464646664', 'Kunduchimtongani\r\n1232', NULL, 'active', '2025-04-11 18:09:44', '2025-04-11 18:09:44'),
(10, 'alvin4', 'as@gmail.com', '$2y$10$QwJ2XvYaGOwU2.mSFPflyePDBgwXO9NjtNmDsiPsvp73hq1uWgVuK', 'buyer', 'Alvin', 'Calvin', '2323123', 'Moshi', NULL, 'active', '2025-04-11 18:11:51', '2025-04-11 18:11:51');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `buyer_id`, `product_id`, `created_at`) VALUES
(2, 8, 4, '2025-04-10 21:18:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `idx_sender_receiver` (`sender_id`,`receiver_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transaction_id` (`transaction_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`buyer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
