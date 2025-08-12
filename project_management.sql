-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 12, 2025 at 03:17 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` enum('project','task','user','comment') NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `created_at`) VALUES
(1, 1, 'logged_in', 'user', 1, NULL, '2025-08-12 12:06:39'),
(2, 1, 'logged_out', 'user', 1, NULL, '2025-08-12 12:06:43'),
(3, 1, 'logged_in', 'user', 1, NULL, '2025-08-12 12:06:53'),
(4, 1, 'logged_out', 'user', 1, NULL, '2025-08-12 13:15:16');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','active','completed','on_hold') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `description`, `status`, `priority`, `start_date`, `end_date`, `progress`, `manager_id`, `created_at`, `updated_at`) VALUES
(1, 'Website Redesign', 'Complete redesign of company website with modern UI/UX', 'active', 'high', '2024-01-01', '2024-06-30', 65, 2, '2025-08-12 10:29:00', '2025-08-12 10:29:00'),
(2, 'Mobile App Development', 'Develop iOS and Android apps for customer service', 'pending', 'medium', '2024-03-01', '2024-12-31', 0, 2, '2025-08-12 10:29:00', '2025-08-12 10:29:00'),
(3, 'Database Migration', 'Migrate legacy database to new cloud infrastructure', 'active', 'low', '2024-02-15', '2024-05-15', 40, 2, '2025-08-12 10:29:00', '2025-08-12 10:29:00'),
(4, 'Test Project 2025-08-12 15:09:02', 'Test project created after migration', 'pending', 'medium', '2025-08-12', NULL, 0, 2, '2025-08-12 13:09:02', '2025-08-12 13:09:02'),
(5, 'Test Project', 'Test Description', 'pending', 'medium', '2024-08-12', NULL, 0, 2, '2025-08-12 13:09:22', '2025-08-12 13:09:22');

-- --------------------------------------------------------

--
-- Table structure for table `project_members`
--

CREATE TABLE `project_members` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('manager','developer','designer','tester','viewer') DEFAULT 'developer',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_members`
--

INSERT INTO `project_members` (`id`, `project_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 2, 'manager', '2025-08-12 10:29:00'),
(2, 1, 3, 'developer', '2025-08-12 10:29:00'),
(3, 1, 4, 'designer', '2025-08-12 10:29:00'),
(4, 2, 2, 'manager', '2025-08-12 10:29:00'),
(5, 2, 3, 'developer', '2025-08-12 10:29:00'),
(6, 3, 2, 'manager', '2025-08-12 10:29:00'),
(7, 3, 4, 'developer', '2025-08-12 10:29:00'),
(8, 4, 2, 'manager', '2025-08-12 13:09:02'),
(9, 5, 2, 'manager', '2025-08-12 13:09:22');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','on_hold') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `project_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `estimated_hours` decimal(5,2) DEFAULT NULL,
  `actual_hours` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `status`, `priority`, `project_id`, `assigned_to`, `assigned_by`, `due_date`, `estimated_hours`, `actual_hours`, `created_at`, `updated_at`) VALUES
(1, 'Design Homepage', 'Create wireframes and mockups for homepage', 'in_progress', 'high', 1, 3, 2, '2024-04-15', 8.00, 6.00, '2025-08-12 10:29:00', '2025-08-12 10:29:00'),
(2, '', '', 'pending', 'medium', NULL, NULL, 2, NULL, NULL, NULL, '2025-08-12 10:29:00', '2025-08-12 13:14:28'),
(3, 'API Development', 'Develop REST API endpoints', 'pending', 'medium', 2, 4, 2, '2024-05-01', 20.00, 0.00, '2025-08-12 10:29:00', '2025-08-12 10:29:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','manager','member') DEFAULT 'member',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `avatar`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@pmtool.com', '$2y$10$2Gq/0PvPcSSLhadIsr2ooeUQ8WPPWpfZZTkAn.rz7n.eUDsv5Wy1u', 'Admin User', 'admin', NULL, '2025-08-12 10:29:00', '2025-08-12 10:29:00'),
(2, 'john_doe', 'john@pmtool.com', '$2y$10$K44kCL7p4ydKDkx0M71Jlecae8wtde6hvYFIq9u70Nk0ClBZdcT26', 'John Doe', 'manager', NULL, '2025-08-12 10:29:00', '2025-08-12 10:29:00'),
(3, 'jane_smith', 'jane@pmtool.com', '$2y$10$in/Cz2sotPv4Vk4vTKPi3.Gd88PbLTx2qEvmKm3MMeWyKLVpTTs3y', 'Jane Smith', 'member', NULL, '2025-08-12 10:29:00', '2025-08-12 10:29:00'),
(4, 'mike_wilson', 'mike@pmtool.com', '$2y$10$MI0n5phEK82EHCq69D518Osc5vtdFJSFj2Ij2Dnmmz1.yuxShYvNO', 'Mike Wilson', 'member', NULL, '2025-08-12 10:29:00', '2025-08-12 10:29:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_user` (`project_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `project_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
