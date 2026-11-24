-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2026 at 04:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `task_tracker_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `task_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`task_id`, `user_id`, `title`, `description`, `deadline`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Database Project', 'Complete the Database Project.', '2026-11-25', 'Pending', '2026-11-20 16:09:40', '2026-11-20 16:09:40'),
(3, 2, 'Database Management System', 'Complete the database management system project and prepare it for submission.', '2026-11-25', 'Pending', '2026-11-20 16:14:47', '2026-11-20 16:14:47'),
(4, 2, 'Database Project', 'Finish', '2026-11-25', 'In Progress', '2026-11-20 16:16:18', '2026-11-20 16:16:18'),
(6, 2, 'Test', 'test', '2026-11-27', 'Completed', '2026-11-20 16:34:05', '2026-11-20 16:34:27'),
(7, 3, 'I remind akong bab', 'love kaayu nako akong bab', '2026-11-30', 'Completed', '2026-11-24 15:03:23', '2026-11-24 15:03:33'),
(8, 4, 'Basketball Game', 'BBL', '2026-11-25', 'In Progress', '2026-11-24 15:22:37', '2026-11-24 15:23:11'),
(9, 4, 'Upper Body Workout', 'day 1 workout', '2026-11-25', 'Pending', '2026-11-24 15:22:54', '2026-11-24 15:22:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Juan Dela Cruz', 'juan123@gmail.com', '$2y$10$F.gHw3XXES0cG4iUPhMoFuq9xatADgkm8Wg9KU/behGXvG6ADFilC', '2026-11-20 15:53:59'),
(2, 'Juan Dela Cruz', 'juan123@example.com', '$2y$10$ed7648RDExK62nW0DXG01.EJME5/m5LsDcy6FIAFwwLSWle0HDDQ.', '2026-11-20 15:59:09'),
(3, 'Ashley Louise C. Labuan', 'ashleylouiselabuan@gmail.com', '$2y$10$0bW3XCrS/3kYcIOwuM5C8uq2kGLiqgQ19qT9urisHJzOsjtsTS94m', '2026-11-20 16:17:09'),
(4, 'Jave Jayson Mondejar', 'javejaysonmondejarvillarte@gmail.com', '$2y$10$PPOVqOttCha5N9gifCugLeDXpMdzMp89xkn9amL0ln8kYXXfIb1FS', '2026-11-24 15:22:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `fk_tasks_users` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `task_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
