CREATE TABLE `voices` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `text` text NOT NULL,
  `voice` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int NOT NULL
);
