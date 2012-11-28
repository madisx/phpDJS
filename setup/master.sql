# --------------------------------------------------------
# Host:                         127.0.0.1
# Server version:               5.5.28-0ubuntu0.12.10.1
# Server OS:                    debian-linux-gnu
# HeidiSQL version:             6.0.0.3603
# Date/time:                    2012-11-27 23:39:08
# --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

# Dumping database structure for phpDJS
DROP DATABASE IF EXISTS `phpDJS`;
CREATE DATABASE IF NOT EXISTS `phpDJS` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `phpDJS`;


# Dumping structure for table phpDJS.job
DROP TABLE IF EXISTS `job`;
CREATE TABLE IF NOT EXISTS `job` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `class` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class` (`class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Data exporting was unselected.


# Dumping structure for table phpDJS.job_parameters
DROP TABLE IF EXISTS `job_parameters`;
CREATE TABLE IF NOT EXISTS `job_parameters` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `job_id` int(10) NOT NULL DEFAULT '0',
  `max_parallel` int(10) DEFAULT NULL COMMENT 'How many instances can run at most in parallel',
  `server_cooldown` int(10) DEFAULT NULL COMMENT 'Cooldown for task on one server',
  `global_cooldown` int(10) DEFAULT NULL COMMENT 'Cooldown for task, minimum time in seconds between last end and new start',
  `server_rotation_required` tinyint(4) DEFAULT NULL COMMENT 'Task should not be ran twice in row on the same server if possible',
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `param_to_job` FOREIGN KEY (`job_id`) REFERENCES `job` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Data exporting was unselected.


# Dumping structure for table phpDJS.schedule
DROP TABLE IF EXISTS `schedule`;
CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `job_id` int(10) NOT NULL,
  `type` tinyint(4) DEFAULT NULL COMMENT '1 - relational frequency, 2 - cron',
  `frequency` tinyint(4) DEFAULT NULL,
  `minute` varchar(5) DEFAULT NULL,
  `hour` varchar(5) DEFAULT NULL,
  `dom` varchar(5) DEFAULT NULL,
  `month` varchar(5) DEFAULT NULL,
  `dow` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_id_uniq` (`job_id`),
  KEY `job_id` (`job_id`),
  CONSTRAINT `schedule_to_job` FOREIGN KEY (`job_id`) REFERENCES `job` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Data exporting was unselected.


# Dumping structure for table phpDJS.server
DROP TABLE IF EXISTS `server`;
CREATE TABLE IF NOT EXISTS `server` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `last_job` int(10) DEFAULT NULL,
  `run_count` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`),
  KEY `last_job` (`last_job`),
  CONSTRAINT `server_to_stats` FOREIGN KEY (`last_job`) REFERENCES `stats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Data exporting was unselected.


# Dumping structure for table phpDJS.settings
DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) DEFAULT NULL,
  `value` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Data exporting was unselected.


# Dumping structure for table phpDJS.stats
DROP TABLE IF EXISTS `stats`;
CREATE TABLE IF NOT EXISTS `stats` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `job_id` int(10) NOT NULL,
  `server_id` int(10) NOT NULL,
  `start_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` timestamp NULL DEFAULT NULL,
  `duration` float DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL COMMENT '1 - started, 2 - finished, 3 - error',
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `server_id` (`server_id`),
  CONSTRAINT `stats_to_job` FOREIGN KEY (`job_id`) REFERENCES `job` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Data exporting was unselected.


# Dumping structure for table phpDJS.user
DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) DEFAULT NULL,
  `password` varchar(32) DEFAULT NULL COMMENT 'md5 hash of the password',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Dumping data for table phpDJS.user: ~1 rows (approximately)
DELETE FROM `user`;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` (`id`, `username`, `password`) VALUES
	(1, 'admin', '21232f297a57a5a743894a0e4a801fc3');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
