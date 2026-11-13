-- ====================================================================
-- RTIH Technical Assignment - Task Tracker Database Export
-- Organization: Ratan Tata Innovation Hub Amaravati Foundation
-- Target Role: Intern - IT
-- Database Name: rtih_tasks
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `rtih_tasks` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rtih_tasks`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `tasks`
-- Minimum required fields: id, title, description, status, created_at
-- Enhanced fields (Level 2/3 & Bonus): user_id, priority, due_date, updated_at
-- --------------------------------------------------------
CREATE TABLE `tasks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('Pending', 'In Progress', 'Completed') NOT NULL DEFAULT 'Pending',
  `priority` ENUM('Low', 'Medium', 'High', 'Urgent') NOT NULL DEFAULT 'Medium',
  `due_date` DATE NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_user_id` (`user_id`),
  KEY `idx_tasks_status` (`status`),
  KEY `idx_tasks_priority` (`priority`),
  KEY `idx_tasks_created_at` (`created_at`),
  CONSTRAINT `fk_tasks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data for table `users`
-- Default test credentials:
-- 1. Standard User: user@rtih.local  / User@123
-- 2. Administrator: admin@rtih.local / Admin@123
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Hemanth Kumar', 'user@rtih.local', '$2y$10$UzTITD2QdBTZslXBNZH82.tMmTtSqe3v4qYgYM2XcNanI4ZGZlz/m', 'user', NOW() - INTERVAL 5 DAY),
(2, 'RTIH Administrator', 'admin@rtih.local', '$2y$10$/B3d0d85yH09NuiML67ceu/cIrXDndPbemN2GqYxCpQ7oKfhdcaeK', 'admin', NOW() - INTERVAL 10 DAY);

-- --------------------------------------------------------
-- Seed Data for table `tasks`
-- Pre-populated tasks demonstrating all statuses, priorities, and dates
-- --------------------------------------------------------
INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `status`, `priority`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Implement PDO Database Connection', 'Set up secure PDO connectivity with utf8mb4 encoding, exception mode, and disabled emulate prepares.', 'Completed', 'High', CURDATE() + INTERVAL 1 DAY, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 2 DAY),
(2, 1, 'Build User Authentication Flow', 'Implement user registration, secure login with password_verify(), and session regeneration to prevent fixation.', 'Completed', 'High', CURDATE() + INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY),
(3, 1, 'Design Responsive Task Dashboard', 'Develop a modern, distraction-free interface with quick status toggle, view switch (grid/table), and KPI metrics.', 'In Progress', 'Urgent', CURDATE() + INTERVAL 3 DAY, NOW() - INTERVAL 1 DAY, NOW()),
(4, 1, 'Integrate CSRF and XSS Protections', 'Embed cryptographically secure CSRF tokens in state-changing operations and escape output with htmlspecialchars().', 'In Progress', 'High', CURDATE() + INTERVAL 4 DAY, NOW() - INTERVAL 12 HOUR, NOW() - INTERVAL 2 HOUR),
(5, 1, 'Implement Task Search and Filter Toolbar', 'Enable instant keyword search across task titles and filter views by Pending, In Progress, or Completed status.', 'Pending', 'Medium', CURDATE() + INTERVAL 5 DAY, NOW() - INTERVAL 6 HOUR, NOW() - INTERVAL 6 HOUR),
(6, 1, 'Prepare SQL Export & Documentation', 'Document database schema, backup and restore commands, and write up the testing checklist.', 'Pending', 'Low', CURDATE() + INTERVAL 7 DAY, NOW() - INTERVAL 1 HOUR, NOW() - INTERVAL 1 HOUR),
(7, 2, 'Review IT Intern Technical Submissions', 'Assess applicant code cleanliness, security practices (IDOR defense, prepared statements), and UI craftsmanship.', 'In Progress', 'Urgent', CURDATE() + INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 5 HOUR),
(8, 2, 'Conduct Candidate Technical Interviews', 'Schedule 15-minute code walkthroughs to discuss role-based access control and database optimization.', 'Pending', 'High', CURDATE() + INTERVAL 4 DAY, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY);

ALTER TABLE `tasks` AUTO_INCREMENT = 9;
ALTER TABLE `users` AUTO_INCREMENT = 3;
