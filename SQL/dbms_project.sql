-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 25, 2025 at 07:02 PM
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
  `end_time` time DEFAULT NULL,
  `status` enum('Scheduled','Live','Completed') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`match_id`, `tournament_id`, `match_date`, `match_time`, `end_time`, `status`) VALUES
(101, 1, '2025-11-05', '14:00:00', '16:00:00', 'Completed'),
(102, 1, '2025-11-06', '16:30:00', '18:30:00', 'Completed'),
(103, 2, '2025-12-25', '22:00:00', '24:00:00', 'Scheduled'),
(104, 2, '2025-12-28', '19:00:00', '21:00:00', 'Scheduled');

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
(101, 1, 15),
(101, 2, 10),
(102, 1, 30),
(102, 30, 50),
(103, 2, 45),
(103, 30, 42);

--
-- Triggers `match_plays`
--
DELIMITER $$
CREATE TRIGGER `update_total_score_after_insert` AFTER INSERT ON `match_plays` FOR EACH ROW BEGIN
    -- Update the participates table by summing all match scores for this team in this tournament
    UPDATE participates P
    SET P.score = (
        SELECT SUM(MP.match_score)
        FROM match_plays MP
        JOIN matches M ON MP.match_id = M.match_id
        WHERE MP.team_id = NEW.team_id 
          AND M.tournament_id = P.tournament_id
    )
    WHERE P.team_id = NEW.team_id;
END
$$
DELIMITER ;

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
(2, 2, 'Approved', 45),
(2, 30, 'Approved', 42);

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
(1, 'GK_Pro', 'Ganesh Karthik', 'gk@iiitj.ac.in', 'gk@123', NULL, 20, NULL),
(2, 'Slayer_24', 'Rahul Sharma', 'rahul@gmail.com', 'sl@123', NULL, 19, NULL),
(3, 'Minge_Lord', 'Varun K', 'varun@gmail.com', 'ml@123', NULL, 15, NULL);

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
(1, 'Team Soul', 1, '2025-11-30'),
(2, 'Sentinels', 2, '2025-11-30'),
(30, 'minge', 3, '2025-12-25');

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
(1, 1, '2025-12-25'),
(2, 2, '2025-12-25'),
(30, 3, '2025-12-25');

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
(1, 'Winter Invitational', 1, 1, '2025-11-01 10:00:00', '2025-11-15 22:00:00', 50000.00),
(2, 'BGMI Pro Series', 2, 2, '2025-12-20 09:00:00', '2026-01-10 23:59:59', 100000.00),
(3, 'CS2 Masters', 3, 1, '2025-10-15 12:00:00', '2025-10-30 20:00:00', 75000.00);

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
  MODIFY `match_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

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
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
