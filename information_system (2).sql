-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2026 at 07:34 PM
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
-- Database: `information_system`
--

CREATE DATABASE IF NOT EXISTS `information_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `information_system`;

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AddDepartment` (IN `id` INT, IN `name` VARCHAR(50), IN `build` VARCHAR(50), IN `room` VARCHAR(10))   BEGIN
    INSERT INTO Department (DeptID, DeptName, Building, RoomNumber)
    VALUES (id, name, build, room);
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `ConvertToPercentage` (`GradeNum` DECIMAL(3,2)) RETURNS DECIMAL(5,2) DETERMINISTIC BEGIN
    RETURN (GradeNum / 4.0) * 100;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `CourseID` int(11) NOT NULL,
  `Title` varchar(50) DEFAULT NULL,
  `Credits` int(11) DEFAULT NULL,
  `ProfID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`CourseID`, `Title`, `Credits`, `ProfID`) VALUES
(401, 'Intro to SQL', 3, 201),
(402, 'Calculus I', 4, 208),
(403, 'Quantum Physics', 4, 203),
(404, 'Organic Chem', 4, 204),
(405, 'Macroeconomics', 3, 206),
(406, 'Logic 101', 3, 207),
(407, 'World History', 3, 210),
(408, 'AI Ethics', 3, 202),
(409, 'Poetry', 2, 209),
(410, 'Genetics', 4, 205);

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `DeptID` int(11) NOT NULL,
  `DeptName` varchar(50) DEFAULT NULL,
  `Building` varchar(50) DEFAULT NULL,
  `RoomNumber` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`DeptID`, `DeptName`, `Building`, `RoomNumber`) VALUES
(1, 'Computer Science', 'Tech Hall', 'A-101'),
(2, 'Mathematics', 'Euler Bldg', '202'),
(3, 'Physics', 'Newton Lab', '101'),
(4, 'Biology', 'Darwin Wing', '305'),
(5, 'History', 'Heritage Hall', '110'),
(6, 'Chemistry', 'Boyle Lab', 'B-12'),
(7, 'Literature', 'Poet Corner', '404'),
(8, 'Philosophy', 'Socrates Room', '001'),
(9, 'Economics', 'Smith Plaza', '500'),
(10, 'Art', 'Studio', '101');

-- --------------------------------------------------------

--
-- Stand-in structure for view `deptlocations`
-- (See below for the actual view)
--
CREATE TABLE `deptlocations` (
`DeptName` varchar(50)
,`Building` varchar(50)
,`RoomNumber` varchar(10)
);

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `EnrollID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `CourseID` int(11) DEFAULT NULL,
  `GradeNum` decimal(3,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`EnrollID`, `StudentID`, `CourseID`, `GradeNum`) VALUES
(1, 301, 401, 4.00),
(2, 301, 402, 3.50),
(3, 302, 402, 4.00),
(4, 303, 401, 2.80),
(5, 304, 403, 3.90),
(6, 305, 407, 3.20),
(7, 306, 410, 3.70),
(8, 307, 405, 4.00),
(9, 308, 404, 3.00),
(10, 310, 401, 3.80);

--
-- Triggers `enrollment`
--
DELIMITER $$
CREATE TRIGGER `Before_Enrollment_Insert` BEFORE INSERT ON `enrollment` FOR EACH ROW BEGIN
    -- new student enrollment is added this trigger check the GradeNum
    -- Check if the incoming GradeNum is negative
    IF NEW.GradeNum < 0 THEN
        -- If it is, automatically fix it by setting it to 0.00
        SET NEW.GradeNum = 0.00;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `professor`
--

CREATE TABLE `professor` (
  `ProfID` int(11) NOT NULL,
  `FirstName` varchar(25) DEFAULT NULL,
  `LastName` varchar(25) DEFAULT NULL,
  `Email` varchar(50) DEFAULT NULL,
  `DeptID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `professor`
--

INSERT INTO `professor` (`ProfID`, `FirstName`, `LastName`, `Email`, `DeptID`) VALUES
(201, 'Alan', 'Turing', 'turing@uni.edu', 1),
(202, 'Ada', 'Lovelace', 'ada@uni.edu', 1),
(203, 'Isaac', 'Newton', 'gravity@uni.edu', 3),
(204, 'Marie', 'Curie', 'radium@uni.edu', 6),
(205, 'Charles', 'Darwin', 'finch@uni.edu', 4),
(206, 'Adam', 'Smith', 'wealth@uni.edu', 9),
(207, 'Plato', 'Aristotle', 'cave@uni.edu', 8),
(208, 'Hypatia', 'Alexandria', 'astronomy@uni.edu', 2),
(209, 'Ernest', 'Hemingway', 'sea@uni.edu', 7),
(210, 'Leonardo', 'Vinci', 'mona@uni.edu', 10);

--
-- Triggers `professor`
--
DELIMITER $$
CREATE TRIGGER `BeforeProfUpdateEmail` BEFORE UPDATE ON `professor` FOR EACH ROW BEGIN
    -- This trigger activates BEFORE an update is made to a professor's record. If the administrator types the new email with messy capitalization like a mix of uppercase and lowercase letters the trigger uses the LOWER() function to automatically convert the entire Email string to lowercase before it gets saved.
    SET NEW.Email = LOWER(NEW.Email);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `BeforeProfessorDelete` BEFORE DELETE ON `professor` FOR EACH ROW BEGIN
    -- Remove the deleted professor from any courses they were assigned to
    UPDATE course 
    SET ProfID = NULL 
    WHERE ProfID = OLD.ProfID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentID` int(11) NOT NULL,
  `FirstName` varchar(25) DEFAULT NULL,
  `LastName` varchar(25) DEFAULT NULL,
  `Major_DeptID` int(11) DEFAULT NULL,
  `EnrollYear` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudentID`, `FirstName`, `LastName`, `Major_DeptID`, `EnrollYear`) VALUES
(301, 'Alice', 'Smith', 1, 2024),
(302, 'Bob', 'Jones', 2, 2023),
(303, 'Charlie', 'Brown', 1, 2025),
(304, 'Diana', 'Prince', 3, 2024),
(305, 'Ethan', 'Hunt', 5, 2022),
(306, 'Fiona', 'Shrek', 4, 2023),
(307, 'George', 'Miller', 9, 2024),
(308, 'Hannah', 'Abbott', 6, 2025),
(309, 'Ian', 'Wright', 7, 2023),
(310, 'Jane', 'Doe', 1, 2024);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'Staff',
  `status` varchar(20) NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `status`) VALUES
(1, 'Admin', 'User', 'admin@admin.com', 'admin123', 'Admin', 'Inactive'),
(2, 'Jane', 'Smith', 'jane@company.com', 'POSIS123', 'Staff', 'Inactive'),
(3, 'joseph', 'posis', 'josephposis@gmail.com', 'JosephPosis', 'Admin', 'Active');

-- --------------------------------------------------------

--
-- Stand-in structure for view `viewstudent`
-- (See below for the actual view)
--
CREATE TABLE `viewstudent` (
`StudentID` int(11)
,`FullName` varchar(51)
,`EnrollYear` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_student_enrollments`
-- (See below for the actual view)
--
CREATE TABLE `view_student_enrollments` (
`StudentID` int(11)
,`CourseID` int(11)
,`GradeNum` decimal(3,2)
);

-- --------------------------------------------------------

--
-- Structure for view `deptlocations`
--
DROP TABLE IF EXISTS `deptlocations`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `deptlocations`  AS SELECT `department`.`DeptName` AS `DeptName`, `department`.`Building` AS `Building`, `department`.`RoomNumber` AS `RoomNumber` FROM `department` ;

-- --------------------------------------------------------

--
-- Structure for view `viewstudent`
--
DROP TABLE IF EXISTS `viewstudent`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `viewstudent`  AS SELECT `student`.`StudentID` AS `StudentID`, concat(`student`.`FirstName`,' ',`student`.`LastName`) AS `FullName`, `student`.`EnrollYear` AS `EnrollYear` FROM `student` ;

-- --------------------------------------------------------

--
-- Structure for view `view_student_enrollments`
--
DROP TABLE IF EXISTS `view_student_enrollments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_student_enrollments`  AS SELECT `enrollment`.`StudentID` AS `StudentID`, `enrollment`.`CourseID` AS `CourseID`, `enrollment`.`GradeNum` AS `GradeNum` FROM `enrollment` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`CourseID`),
  ADD KEY `ProfID` (`ProfID`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`DeptID`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`EnrollID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `professor`
--
ALTER TABLE `professor`
  ADD PRIMARY KEY (`ProfID`),
  ADD KEY `DeptID` (`DeptID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentID`),
  ADD KEY `Major_DeptID` (`Major_DeptID`);

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`ProfID`) REFERENCES `professor` (`ProfID`);

--
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `enrollment_ibfk_2` FOREIGN KEY (`CourseID`) REFERENCES `course` (`CourseID`);

--
-- Constraints for table `professor`
--
ALTER TABLE `professor`
  ADD CONSTRAINT `professor_ibfk_1` FOREIGN KEY (`DeptID`) REFERENCES `department` (`DeptID`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`Major_DeptID`) REFERENCES `department` (`DeptID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
