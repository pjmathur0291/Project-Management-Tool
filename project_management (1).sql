-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 13, 2025 at 02:52 PM
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
(4, 1, 'logged_out', 'user', 1, NULL, '2025-08-12 13:15:16'),
(5, 1, 'logged_in', 'user', 1, NULL, '2025-08-13 04:46:13'),
(6, 1, 'logged_in', 'user', 1, NULL, '2025-08-13 04:49:25'),
(7, 1, 'logged_out', 'user', 1, NULL, '2025-08-13 04:59:03'),
(8, 1, 'logged_in', 'user', 1, NULL, '2025-08-13 04:59:07'),
(9, 1, 'logged_out', 'user', 1, NULL, '2025-08-13 05:58:32'),
(10, 1, 'logged_in', 'user', 1, NULL, '2025-08-13 05:58:36'),
(11, 1, 'logged_out', 'user', 1, NULL, '2025-08-13 07:28:47'),
(12, 1, 'logged_in', 'user', 1, NULL, '2025-08-13 07:28:56'),
(13, 1, 'logged_out', 'user', 1, NULL, '2025-08-13 09:05:57'),
(14, 1, 'logged_in', 'user', 1, NULL, '2025-08-13 09:06:03'),
(15, 1, 'logged_out', 'user', 1, NULL, '2025-08-13 09:24:51'),
(16, 6, 'logged_in', 'user', 6, NULL, '2025-08-13 09:25:01'),
(17, 6, 'logged_out', 'user', 6, NULL, '2025-08-13 09:30:35'),
(18, 7, 'logged_in', 'user', 7, NULL, '2025-08-13 09:30:41'),
(19, 7, 'logged_out', 'user', 7, NULL, '2025-08-13 09:31:25'),
(20, 1, 'logged_in', 'user', 1, NULL, '2025-08-13 09:31:30'),
(21, 1, 'logged_out', 'user', 1, NULL, '2025-08-13 09:31:35'),
(22, 6, 'logged_in', 'user', 6, NULL, '2025-08-13 09:31:45'),
(23, 6, 'logged_out', 'user', 6, NULL, '2025-08-13 09:33:48'),
(24, 7, 'logged_in', 'user', 7, NULL, '2025-08-13 09:33:57'),
(25, 7, 'logged_out', 'user', 7, NULL, '2025-08-13 10:58:20'),
(26, 1, 'logged_in', 'user', 1, NULL, '2025-08-13 10:58:28'),
(27, 1, 'logged_out', 'user', 1, NULL, '2025-08-13 10:58:32'),
(28, 6, 'logged_in', 'user', 6, NULL, '2025-08-13 10:58:36'),
(29, 6, 'logged_out', 'user', 6, NULL, '2025-08-13 10:58:51'),
(30, 1, 'logged_in', 'user', 1, NULL, '2025-08-13 10:58:56'),
(31, 1, 'logged_out', 'user', 1, NULL, '2025-08-13 10:59:28'),
(32, 6, 'logged_in', 'user', 6, NULL, '2025-08-13 10:59:33');

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
-- Table structure for table `completed_tasks`
--

CREATE TABLE `completed_tasks` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(8, 'IZee Institute', '', 'active', 'high', '2025-08-05', NULL, 0, 6, '2025-08-13 10:59:17', '2025-08-13 10:59:17');

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
(12, 8, 6, 'manager', '2025-08-13 10:59:17');

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
(49, 'IZee Website', '', 'pending', 'high', 8, 7, 6, '2025-08-20', 2.00, NULL, '2025-08-13 11:03:47', '2025-08-13 11:03:47'),
(50, 'IZee Website', '', 'pending', 'low', 8, 7, 6, '2025-08-20', 2.00, NULL, '2025-08-13 11:04:31', '2025-08-13 11:04:31');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `job_title` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `avatar`, `created_at`, `updated_at`, `job_title`) VALUES
(1, 'admin', 'admin@pmtool.com', '$2y$10$2Gq/0PvPcSSLhadIsr2ooeUQ8WPPWpfZZTkAn.rz7n.eUDsv5Wy1u', 'Admin User', 'admin', NULL, '2025-08-12 10:29:00', '2025-08-12 10:29:00', NULL),
(6, 'pjmathur157', 'pjmathur157@gmail.com', '$2y$10$WQKqg./6njdemS6F8Q5pgul3duTSETk4yHluATCjgSlr/uG3oPsYa', 'Pranjal Mathur', 'manager', NULL, '2025-08-13 09:19:18', '2025-08-13 09:19:18', 'Developer (Manager)'),
(7, 'Shubham', 'shubham@gmail.com', '$2y$10$x6TBOgPoTsik/z2TwKzlZ.bmjc69tCvyHplFJ1sfE3jqsfsTVHccm', 'Shuabham', 'member', NULL, '2025-08-13 09:30:27', '2025-08-13 09:30:27', 'Graphic Designer');

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
-- Indexes for table `completed_tasks`
--
ALTER TABLE `completed_tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_id` (`task_id`),
  ADD KEY `completed_by` (`completed_by`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `completed_tasks`
--
ALTER TABLE `completed_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Constraints for table `completed_tasks`
--
ALTER TABLE `completed_tasks`
  ADD CONSTRAINT `completed_tasks_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `completed_tasks_ibfk_2` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
