-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 25, 2025 at 04:07 PM
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
-- Database: `dbms_project`
--

-- --------------------------------------------------------

--
-- Table structure for table `game`
--

CREATE TABLE `game` (
  `game_id` int(11) NOT NULL,
  `game_name` varchar(100) NOT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `max_team_size` int(11) DEFAULT 5,
  `min_age` int(11) DEFAULT 0,
  `min_teams_per_match` int(11) DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `game`
--

INSERT INTO `game` (`game_id`, `game_name`, `platform`, `max_team_size`, `min_age`, `min_teams_per_match`) VALUES
(1, 'Valorant', 'PC', 5, 16, 2),
(2, 'BGMI', 'Mobile', 4, 12, 4),
(3, 'Counter-Strike 2', 'PC', 5, 18, 2);

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `match_id` int(11) NOT NULL,
  `tournament_id` int(11) DEFAULT NULL,
  `match_date` date DEFAULT NULL,
  `match_time` time DEFAULT NULL,
  `status` enum('Scheduled','Live','Completed') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`match_id`, `tournament_id`, `match_date`, `match_time`, `status`) VALUES
(1, 1, '2023-11-05', '14:00:00', 'Completed'),
(2, 1, '2023-11-06', '16:00:00', 'Completed'),
(3, 5, '2024-12-22', '15:00:00', 'Completed'),
(4, 1, '2025-12-12', '01:00:00', 'Scheduled'),
(5, 1, '2025-12-16', '01:00:00', 'Scheduled'),
(6, 2, '2026-12-20', '10:00:00', 'Completed'),
(7, 5, '2028-12-16', '01:00:00', 'Completed'),
(8, 5, '2025-12-24', '01:09:00', 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `match_plays`
--

CREATE TABLE `match_plays` (
  `match_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `match_score` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `match_plays`
--

INSERT INTO `match_plays` (`match_id`, `team_id`, `match_score`) VALUES
(1, 1, 15),
(1, 3, 10),
(2, 1, 15),
(2, 3, 20),
(4, 1, 15),
(4, 3, 30),
(5, 1, 0),
(5, 3, 0),
(6, 2, 100),
(6, 23, 10),
(7, 23, 50),
(7, 24, 100),
(7, 25, 10),
(8, 23, 20),
(8, 24, 30),
(8, 25, 40),
(8, 26, 90);

-- --------------------------------------------------------

--
-- Table structure for table `organizer`
--

CREATE TABLE `organizer` (
  `organizer_id` int(11) NOT NULL,
  `organizer_name` varchar(100) DEFAULT NULL,
  `organization_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT 'admin123'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `organizer`
--

INSERT INTO `organizer` (`organizer_id`, `organizer_name`, `organization_name`, `contact_email`, `contact_number`, `password`) VALUES
(1, 'Nodwin Gaming', 'Nodwin', 'organizer@nodwin.com', NULL, 'admin123'),
(2, 'Riot Games', 'Riot', 'esports@riotgames.com', NULL, 'admin123');

-- --------------------------------------------------------

--
-- Table structure for table `participates`
--

CREATE TABLE `participates` (
  `tournament_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `registration_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `score` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `participates`
--

INSERT INTO `participates` (`tournament_id`, `team_id`, `registration_status`, `score`) VALUES
(1, 1, 'Approved', 45),
(1, 3, 'Approved', 60),
(2, 2, 'Approved', 100),
(2, 23, 'Approved', 10),
(5, 23, 'Approved', 70),
(5, 24, 'Approved', 130),
(5, 25, 'Approved', 50),
(5, 26, 'Approved', 90),
(6, 28, 'Approved', 0),
(9, 29, 'Approved', 0);

-- --------------------------------------------------------

--
-- Table structure for table `player`
--

CREATE TABLE `player` (
  `player_id` int(11) NOT NULL,
  `gamer_tag` varchar(50) NOT NULL,
  `player_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(20) NOT NULL,
  `country` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `player`
--

INSERT INTO `player` (`player_id`, `gamer_tag`, `player_name`, `email`, `password_hash`, `country`, `age`, `contact_number`) VALUES
(1, 'GK_Pro', 'Ganesh Karthik', 'gk@iiitj.ac.in', 'gk@123', 'India', 20, NULL),
(2, 'Mortal', 'Naman Mathur', 'mortal@soul.com', 'nm@123', 'India', 24, NULL),
(3, 'ScoutOP', 'Tanmay Singh', 'scout@tx.com', 'ts@123', 'India', 25, NULL),
(4, 'TenZ', 'Tyson Ngo', 'tenz@sentinels.com', 'tn@123', 'Canada', 22, NULL),
(5, 'S1mple', 'Oleksandr Kostyliev', 's1mple@navi.com', 'ok@123', 'Ukraine', 26, NULL),
(26, 'daddy123', 'Naman Reddy', 'naman2007@gmail.com', 'nm@123', 'India', 18, '9550534020'),
(27, 'rohith_11', 'rohith', 'rohith@gmail.com', 'rk@123', 'India', 19, ''),
(28, 'begam', 'maroof', 'maroof@gmail.com', '12345', 'India', 20, '9550534021'),
(30, 'trojan', 'pankaj', 'pkj@kdjkdjj', 'wer', '', 0, ''),
(31, 'BGC', 'B. GURU CHARAN', 'GC@AS', '1234556', 'India', 18, '9704203739'),
(32, 'jiraya', 'jiraya', 'gk@gmail.com', '$2y$10$v4jkBiDcuz8WA', 'India', 20, '');

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

CREATE TABLE `team` (
  `team_id` int(11) NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `team_captain_id` int(11) DEFAULT NULL,
  `creation_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `team`
--

INSERT INTO `team` (`team_id`, `team_name`, `team_captain_id`, `creation_date`) VALUES
(1, 'Team Soul', 2, '2025-11-30'),
(2, 'Sentinels', 4, '2025-11-30'),
(3, 'NaVi', 5, '2025-11-30'),
(23, 'rumbling', 1, '2025-12-01'),
(24, 'hello world', 1, '2025-12-01'),
(25, 'lets go', 27, '2025-12-01'),
(26, 'clash', 28, '2025-12-01'),
(27, 'mingers', 1, '2025-12-01'),
(28, 'pkj', 28, '2025-12-01'),
(29, 'gg', 1, '2025-12-25');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `team_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `join_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`team_id`, `player_id`, `join_date`) VALUES
(1, 1, '2025-11-30'),
(1, 2, '2025-11-30'),
(1, 3, '2025-11-30'),
(2, 4, '2025-11-30'),
(3, 5, '2025-11-30'),
(23, 1, '2025-12-01'),
(23, 2, '2025-12-01'),
(23, 4, '2025-12-01'),
(24, 1, '2025-12-01'),
(24, 2, '2025-12-01'),
(24, 26, '2025-12-01'),
(25, 2, '2025-12-01'),
(25, 26, '2025-12-01'),
(25, 27, '2025-12-01'),
(26, 2, '2025-12-01'),
(26, 26, '2025-12-01'),
(26, 28, '2025-12-01'),
(28, 28, '2025-12-01'),
(29, 1, '2025-12-25'),
(29, 2, '2025-12-25'),
(29, 26, '2025-12-25');

-- --------------------------------------------------------

--
-- Table structure for table `tournament`
--

CREATE TABLE `tournament` (
  `tournament_id` int(11) NOT NULL,
  `tournament_name` varchar(150) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `organizer_id` int(11) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `prize_pool` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tournament`
--

INSERT INTO `tournament` (`tournament_id`, `tournament_name`, `game_id`, `organizer_id`, `start_date`, `end_date`, `prize_pool`) VALUES
(1, 'BGMI Pro Series', 2, 1, '2023-11-01 10:00:00', '2023-12-30 10:00:00', 50000.00),
(2, 'Valorant Champions', 1, 2, '2024-05-01 10:00:00', '2024-05-20 10:00:00', 100000.00),
(5, 'CS', 3, 2, '2025-11-29 02:33:00', '2025-12-25 02:33:00', 50000.00),
(6, 'PUBG all star', 2, 2, '2025-12-04 11:05:00', '2025-12-31 11:05:00', 100000.00),
(8, 'BGC', 2, 2, '2025-12-08 20:48:00', '2025-12-20 20:48:00', 1000000.00),
(9, 'bgmi', 2, 2, '2025-12-30 19:48:00', '2026-01-10 19:48:00', 100000.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `game`
--
ALTER TABLE `game`
  ADD PRIMARY KEY (`game_id`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `tournament_id` (`tournament_id`);

--
-- Indexes for table `match_plays`
--
ALTER TABLE `match_plays`
  ADD PRIMARY KEY (`match_id`,`team_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `organizer`
--
ALTER TABLE `organizer`
  ADD PRIMARY KEY (`organizer_id`),
  ADD UNIQUE KEY `contact_email` (`contact_email`);

--
-- Indexes for table `participates`
--
ALTER TABLE `participates`
  ADD PRIMARY KEY (`tournament_id`,`team_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `player`
--
ALTER TABLE `player`
  ADD PRIMARY KEY (`player_id`),
  ADD UNIQUE KEY `gamer_tag` (`gamer_tag`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`team_id`),
  ADD KEY `team_captain_id` (`team_captain_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`team_id`,`player_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indexes for table `tournament`
--
ALTER TABLE `tournament`
  ADD PRIMARY KEY (`tournament_id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `organizer_id` (`organizer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `game`
--
ALTER TABLE `game`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `match_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `organizer`
--
ALTER TABLE `organizer`
  MODIFY `organizer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `player`
--
ALTER TABLE `player`
  MODIFY `player_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `team`
--
ALTER TABLE `team`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `tournament`
--
ALTER TABLE `tournament`
  MODIFY `tournament_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`tournament_id`);

--
-- Constraints for table `match_plays`
--
ALTER TABLE `match_plays`
  ADD CONSTRAINT `match_plays_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`match_id`),
  ADD CONSTRAINT `match_plays_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_id`);

--
-- Constraints for table `participates`
--
ALTER TABLE `participates`
  ADD CONSTRAINT `participates_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`tournament_id`),
  ADD CONSTRAINT `participates_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_id`);

--
-- Constraints for table `team`
--
ALTER TABLE `team`
  ADD CONSTRAINT `team_ibfk_1` FOREIGN KEY (`team_captain_id`) REFERENCES `player` (`player_id`);

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_id`),
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `player` (`player_id`);

--
-- Constraints for table `tournament`
--
ALTER TABLE `tournament`
  ADD CONSTRAINT `tournament_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`game_id`),
  ADD CONSTRAINT `tournament_ibfk_2` FOREIGN KEY (`organizer_id`) REFERENCES `organizer` (`organizer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
