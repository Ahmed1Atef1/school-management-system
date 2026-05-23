--
-- ------------------------------------------------------


--
-- Table structure for table `achievements`
--

DROP TABLE IF EXISTS `achievements`;
CREATE TABLE `achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(60) DEFAULT 'bi-trophy-fill',
  `color` enum('primary','success','warning','danger','info') DEFAULT 'warning',
  `xp_reward` int(11) NOT NULL DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `achievements`
--

LOCK TABLES `achievements` WRITE;
INSERT INTO `achievements` VALUES (1,'Top Performer','Scored 90%+ on any major quiz or homework assignment.','bi-trophy-fill','warning',100,'2026-05-23 14:51:24'),(2,'Perfect Week','Maintained 100% attendance streak for 7 consecutive days.','bi-calendar-check-fill','success',75,'2026-05-23 14:51:24'),(3,'Lightning Learner','Completed 5 modules or self-study chapters.','bi-lightning-fill','primary',50,'2026-05-23 14:51:24'),(4,'LMS Settler','Completed your very first assignment submission on LearnSphere.','bi-check2-circle','success',25,'2026-05-23 14:51:24'),(5,'Academic Legend','Reached Level 8 in the portal system.','bi-mortarboard-fill','primary',200,'2026-05-23 14:51:24'),(6,'Star Pupil','Achieved a perfect score (100%) on any test.','bi-star-fill','warning',150,'2026-05-23 14:51:24'),(7,'Steadfast Scholar','Maintained a solid 5-day streak of perfect attendance.','bi-activity','info',50,'2026-05-23 14:51:24');
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
CREATE TABLE `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `max_score` tinyint(4) NOT NULL DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_assign_course` (`course_id`),
  CONSTRAINT `fk_assign_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
INSERT INTO `assignments` VALUES (1,1,'Calculus Exercises','Work through integration and differentiation problems in Chapter 5.','2026-05-25',100,'2026-05-23 14:51:24'),(2,1,'Algebra Quiz','Solve system of linear equations and quadratic formulations.','2026-05-11',100,'2026-05-23 14:51:24'),(3,1,'Trigonometry Homework','Calculate angles and height/distance projections using sine/cosine rules.','2026-05-18',100,'2026-05-23 14:51:24'),(4,2,'Chemical Titration Report','Document observation of pH shifts and neutralization curves.','2026-05-27',100,'2026-05-23 14:51:24'),(5,2,'Science Quiz 3','Online assessment over cell structure and metabolic pathways.','2026-05-21',100,'2026-05-23 14:51:24'),(6,2,'Physics Force Project','Detailed slide deck covering Newton laws of universal gravitation.','2026-06-01',100,'2026-05-23 14:51:24'),(7,3,'Short Story Narrative','Write a 1000-word fictional story utilizing high tension.','2026-05-24',100,'2026-05-23 14:51:24'),(8,3,'Persuasive Speech Draft','Produce a 3-minute oral script debating clean energy strategies.','2026-05-15',100,'2026-05-23 14:51:24'),(9,3,'Vocabulary Test 3','Weekly quiz covering Latin prefixes and advanced prose syntax.','2026-05-05',50,'2026-05-23 14:51:24'),(10,4,'WWII Cause Essay','Write a short critical essay on the socio-economic causes of WWII.','2026-05-29',100,'2026-05-23 14:51:24'),(11,4,'Ancient Rome Quiz','Answer multiple choice questions over Roman empire transitions.','2026-05-19',100,'2026-05-23 14:51:24');
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance` (`user_id`,`course_id`,`date`),
  KEY `fk_att_user` (`user_id`),
  KEY `fk_att_course` (`course_id`),
  CONSTRAINT `fk_att_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
INSERT INTO `attendance` VALUES (1,1,1,'2026-05-17','present','2026-05-23 14:51:24'),(2,1,1,'2026-05-18','present','2026-05-23 14:51:24'),(3,1,1,'2026-05-19','present','2026-05-23 14:51:24'),(4,1,1,'2026-05-20','present','2026-05-23 14:51:24'),(5,1,1,'2026-05-21','present','2026-05-23 14:51:24'),(6,1,1,'2026-05-22','present','2026-05-23 14:51:24'),(7,1,1,'2026-05-23','present','2026-05-23 14:51:24'),(8,1,2,'2026-05-19','present','2026-05-23 14:51:24'),(9,1,2,'2026-05-21','present','2026-05-23 14:51:24'),(10,3,1,'2026-05-18','absent','2026-05-23 14:51:24'),(11,3,1,'2026-05-19','present','2026-05-23 14:51:24'),(12,3,1,'2026-05-20','present','2026-05-23 14:51:24'),(13,3,1,'2026-05-21','present','2026-05-23 14:51:24'),(14,3,1,'2026-05-22','present','2026-05-23 14:51:24'),(15,3,1,'2026-05-23','present','2026-05-23 14:51:24'),(16,4,3,'2026-05-19','present','2026-05-23 14:51:24'),(17,4,3,'2026-05-20','late','2026-05-23 14:51:24'),(18,4,3,'2026-05-21','present','2026-05-23 14:51:24'),(19,4,3,'2026-05-22','excused','2026-05-23 14:51:24'),(20,4,3,'2026-05-23','present','2026-05-23 14:51:24'),(21,7,1,'2026-05-20','present','2026-05-23 14:51:24'),(22,7,1,'2026-05-21','absent','2026-05-23 14:51:24'),(23,7,1,'2026-05-22','late','2026-05-23 14:51:24'),(24,7,1,'2026-05-23','absent','2026-05-23 14:51:24'),(25,3,3,'2026-05-23','late','2026-05-23 16:26:43'),(26,1,3,'2026-05-23','present','2026-05-23 16:26:43');
UNLOCK TABLES;

--
-- Table structure for table `class_rooms`
--

DROP TABLE IF EXISTS `class_rooms`;
CREATE TABLE `class_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `class_rooms`
--

LOCK TABLES `class_rooms` WRITE;
INSERT INTO `class_rooms` VALUES (1,'Finance Room 2','Building 2 2nd Floor',27,'2025-09-08 21:02:16'),(2,'A012','Business',30,'2026-05-23 01:39:56');
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(60) DEFAULT 'bi-book-fill',
  `color` enum('primary','success','warning','danger','info') DEFAULT 'primary',
  `teacher_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_course_teacher` (`teacher_id`),
  KEY `fk_course_room` (`room_id`),
  CONSTRAINT `fk_course_room` FOREIGN KEY (`room_id`) REFERENCES `class_rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_course_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
INSERT INTO `courses` VALUES (1,'Mathematics','Advanced algebraic principles, geometry, calculus, and logical problem-solving.','bi-calculator','primary',1,1,'2026-05-23 14:51:24'),(2,'Science','Practical physics concepts, chemistry reactions, lab mechanics, and organic science.','bi-lightning-charge-fill','success',1,1,'2026-05-23 14:51:24'),(3,'English','Analysis of classical and modern literature, essay logic, writing prose, and debate.','bi-book-half','warning',1,1,'2026-05-23 14:51:24'),(4,'History','Deep exploration of world history, historical conflicts, global civilizations, and treaties.','bi-globe2','danger',1,1,'2026-05-23 14:51:24');
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `progress` tinyint(4) NOT NULL DEFAULT 0 CHECK (`progress` between 0 and 100),
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment` (`user_id`,`course_id`),
  KEY `fk_enroll_user` (`user_id`),
  KEY `fk_enroll_course` (`course_id`),
  CONSTRAINT `fk_enroll_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enroll_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
INSERT INTO `enrollments` VALUES (1,1,1,85,'2026-05-23 14:51:24'),(2,1,2,92,'2026-05-23 14:51:24'),(3,1,3,78,'2026-05-23 14:51:24'),(4,1,4,60,'2026-05-23 14:51:24'),(5,3,1,98,'2026-05-23 14:51:24'),(6,3,2,88,'2026-05-23 14:51:24'),(7,3,3,90,'2026-05-23 14:51:24'),(8,4,1,55,'2026-05-23 14:51:24'),(9,4,2,65,'2026-05-23 14:51:24'),(10,4,3,95,'2026-05-23 14:51:24'),(11,4,4,82,'2026-05-23 14:51:24'),(12,7,1,40,'2026-05-23 14:51:24'),(13,7,2,50,'2026-05-23 14:51:24'),(14,7,4,45,'2026-05-23 14:51:24');
UNLOCK TABLES;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `score` tinyint(4) NOT NULL DEFAULT 0,
  `graded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grade` (`user_id`,`assignment_id`),
  KEY `fk_grade_user` (`user_id`),
  KEY `fk_grade_assign` (`assignment_id`),
  CONSTRAINT `fk_grade_assign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grade_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

LOCK TABLES `grades` WRITE;
INSERT INTO `grades` VALUES (1,1,2,94,'2026-05-23 14:51:24'),(2,1,3,46,'2026-05-23 14:51:24'),(3,1,5,92,'2026-05-23 14:51:24'),(4,1,8,88,'2026-05-23 14:51:24'),(5,1,9,45,'2026-05-23 14:51:24'),(6,3,2,100,'2026-05-23 14:51:24'),(7,3,3,50,'2026-05-23 14:51:24'),(8,3,5,96,'2026-05-23 14:51:24'),(9,3,8,98,'2026-05-23 14:51:24'),(10,3,9,48,'2026-05-23 14:51:24'),(11,4,2,70,'2026-05-23 14:51:24'),(12,4,3,35,'2026-05-23 14:51:24'),(13,4,8,96,'2026-05-23 14:51:24'),(14,4,9,47,'2026-05-23 14:51:24'),(15,4,11,92,'2026-05-23 14:51:24'),(16,7,2,62,'2026-05-23 14:51:24'),(17,7,3,28,'2026-05-23 14:51:24'),(18,7,11,70,'2026-05-23 14:51:24');
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_msg_sender` (`sender_id`),
  KEY `fk_msg_receiver` (`receiver_id`),
  CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
INSERT INTO `messages` VALUES (1,2,7,NULL,'mid term exam','Study well',1,'2026-05-23 16:22:06');
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `icon` varchar(60) DEFAULT 'bi-bell',
  `color` enum('primary','success','warning','danger','info','secondary') DEFAULT 'primary',
  `action_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
INSERT INTO `notifications` VALUES (1,7,'system','New Message: mid term exam','You received a direct message from AhmedAdmin','bi-envelope-fill','info','/school-management/modules/student/messages.php',1,'2026-05-23 16:22:06'),(2,3,'attendance','Attendance Marked','You were marked as Late for English on May 23, 2026.','bi-calendar-check-fill','warning','/school-management/modules/student/attendance.php',0,'2026-05-23 16:26:43'),(3,1,'attendance','Attendance Marked','You were marked as Present for English on May 23, 2026.','bi-calendar-check-fill','success','/school-management/modules/student/attendance.php',0,'2026-05-23 16:26:43'),(4,4,'attendance','Attendance Marked','You were marked as Present for English on May 23, 2026.','bi-calendar-check-fill','success','/school-management/modules/student/attendance.php',0,'2026-05-23 16:26:43');
UNLOCK TABLES;

--
-- Table structure for table `student_achievements`
--

DROP TABLE IF EXISTS `student_achievements`;
CREATE TABLE `student_achievements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `achievement_id` int(11) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_achievement` (`user_id`,`achievement_id`),
  KEY `fk_sa_user` (`user_id`),
  KEY `fk_sa_achievement` (`achievement_id`),
  CONSTRAINT `fk_sa_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_achievements`
--

LOCK TABLES `student_achievements` WRITE;
INSERT INTO `student_achievements` VALUES (1,1,1,'2026-05-19 14:51:24'),(2,1,2,'2026-05-22 14:51:24'),(3,1,3,'2026-05-16 14:51:24'),(4,1,4,'2026-05-11 14:51:24'),(5,3,1,'2026-05-08 14:51:24'),(6,3,2,'2026-05-13 14:51:24'),(7,3,5,'2026-05-15 14:51:24'),(8,3,6,'2026-05-21 14:51:24'),(9,4,4,'2026-05-14 14:51:24'),(10,4,7,'2026-05-20 14:51:24'),(11,7,4,'2026-05-13 14:51:24');
UNLOCK TABLES;

--
-- Table structure for table `student_xp`
--

DROP TABLE IF EXISTS `student_xp`;
CREATE TABLE `student_xp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `xp` int(11) NOT NULL DEFAULT 0,
  `level` tinyint(4) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_xp_user` (`user_id`),
  KEY `fk_xp_user` (`user_id`),
  CONSTRAINT `fk_xp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_xp`
--

LOCK TABLES `student_xp` WRITE;
INSERT INTO `student_xp` VALUES (1,1,2250,5,'2026-05-23 14:51:24'),(2,3,3850,8,'2026-05-23 14:51:24'),(3,4,1750,4,'2026-05-23 14:51:24'),(4,7,750,2,'2026-05-23 14:51:24');
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
INSERT INTO `students` VALUES (1,'AhmedAtef123','ahmed.ahmed@gmail.com','123123123','2025-09-08 21:00:49'),(2,'AhmedAtef','ahmed@gmail.com','01241241243','2026-05-22 09:18:42'),(3,'ahmed hesham','ahmedheshamfaroukk@gmail.com','','2026-05-22 09:18:42'),(4,'rody','rodaina_a06796@cic-cairo.com','','2026-05-22 09:18:42'),(6,'elbob','ofal@gmail.com','','2026-05-23 01:37:37');
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
INSERT INTO `teachers` VALUES (1,'youssef elnajjarww3','salah2@gmail.com','2223333','math','2025-09-08 21:01:16'),(2,'elboba','Elboba@gmail.com','01221345612','French','2026-05-23 01:40:48'),(3,'moha','moha@gmail.com',NULL,'','2026-05-23 02:12:18'),(4,'youssef elnajjarww3','ahmed@ahmed.com','12','English','2026-05-23 14:29:46');
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
INSERT INTO `users` VALUES (1,'AhmedAtef','ahmed@gmail.com','$2y$10$dOHuYwwzzips8avKhVJEh.uLlQTLU5mNrAI34yU.mX4W4Pso5Dqci','student','2025-09-07 05:54:30'),(2,'AhmedAdmin','ahmedadmin@gmail.com','$2y$10$Vq7ek.pAZrZK0fYpas.eg.Z5uBzMNrmZq71RTJZE6Z4Ve4xAHQrdq','admin','2025-09-07 05:58:59'),(3,'ahmed hesham','ahmedheshamfaroukk@gmail.com','$2y$10$W99qHr1iaxUk33cWh4B5nelwCJkW8ReqjNl0L9kr7wNLabCRlGGuS','student','2025-09-07 11:10:30'),(4,'rody','rodaina_a06796@cic-cairo.com','$2y$10$IIEXLgm9Vi7Iw26xcWdIUOrCw6F81d5kUgK58rlaEfQ4qRKF3lz1S','student','2025-09-08 09:27:40'),(6,'Taha elnajjar','Tahaelnajjar@gmail.com','$2y$10$OCg9Jtt51.bShuB2eo2YcuRX.I5zJZuygU.5rH7u5uPqvYcCs9OUC','admin','2025-09-08 18:59:12'),(7,'elbob','ofal@gmail.com','$2y$10$s1WYmIVI./RpC9LN8MLffuluXsXBYFq8BpKvvH939H.bL.4quMP/C','student','2026-05-22 09:22:39'),(8,'moha','moha@gmail.com','$2y$10$a7ACPajERYSdytpNB2jlfesjHR2gEnExg2TEjfdc/RTYOhPQGCcNa','teacher','2026-05-23 02:12:05');
UNLOCK TABLES;


-- Dump completed on 2026-05-23 20:35:47

