CREATE TABLE IF NOT EXISTS `#__mec_events` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `post_id` int(10) NOT NULL,
  `start` date NOT NULL,
  `end` date NOT NULL,
  `repeat` tinyint(4) NOT NULL DEFAULT '0',
  `rinterval` varchar(10) COLLATE [:COLLATE:] DEFAULT NULL,
  `year` varchar(80) COLLATE [:COLLATE:] DEFAULT NULL,
  `month` varchar(80) COLLATE [:COLLATE:] DEFAULT NULL,
  `day` varchar(80) COLLATE [:COLLATE:] DEFAULT NULL,
  `week` varchar(80) COLLATE [:COLLATE:] DEFAULT NULL,
  `weekday` varchar(80) COLLATE [:COLLATE:] DEFAULT NULL,
  `weekdays` varchar(80) COLLATE [:COLLATE:] DEFAULT NULL,
  `days` text COLLATE [:COLLATE:] NOT NULL DEFAULT '',
  `not_in_days` text COLLATE [:COLLATE:] NOT NULL DEFAULT '',
  `time_start` int(10) NOT NULL DEFAULT '0',
  `time_end` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ID` (`id`),
  UNIQUE KEY `post_id` (`post_id`),
  KEY `date_filters` (`start`, `end`, `repeat`, `rinterval`, `year`, `month`, `day`, `week`, `weekday`, `weekdays`, `time_start`, `time_end`)
) DEFAULT CHARSET=[:CHARSET:] COLLATE=[:COLLATE:] AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `#__mec_dates` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` int(10) NOT NULL,
  `dstart` date NOT NULL,
  `dend` date NOT NULL,
  `tstart` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `tend` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `status` varchar(20) COLLATE [:COLLATE:] NOT NULL DEFAULT 'publish',
  `public` int(4) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `tstart` (`tstart`),
  KEY `tend` (`tend`)
) DEFAULT CHARSET=[:CHARSET:] COLLATE=[:COLLATE:];

CREATE TABLE IF NOT EXISTS `#__mec_occurrences` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` int(10) UNSIGNED NOT NULL,
  `occurrence` int(10) UNSIGNED NOT NULL,
  `params` text COLLATE [:COLLATE:],
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `occurrence` (`occurrence`)
) DEFAULT CHARSET=[:CHARSET:] COLLATE=[:COLLATE:];

CREATE TABLE IF NOT EXISTS `#__mec_users` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE [:COLLATE:] NOT NULL,
  `last_name` varchar(255) COLLATE [:COLLATE:] NOT NULL,
  `email` varchar(127) COLLATE [:COLLATE:] NOT NULL,
  `reg` text COLLATE [:COLLATE:] DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) DEFAULT CHARSET=[:CHARSET:] COLLATE=[:COLLATE:] AUTO_INCREMENT=1000000;

CREATE TABLE IF NOT EXISTS `#__mec_bookings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` int UNSIGNED NOT NULL,
  `transaction_id` varchar(20) COLLATE [:COLLATE:] DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `event_id` int UNSIGNED NOT NULL,
  `ticket_ids` varchar(655) COLLATE [:COLLATE:] NOT NULL,
  `seats` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` varchar(20) COLLATE [:COLLATE:] NOT NULL DEFAULT 'pending',
  `confirmed` tinyint NOT NULL DEFAULT '0',
  `verified` tinyint NOT NULL DEFAULT '0',
  `all_occurrences` tinyint NOT NULL DEFAULT '0',
  `date` datetime NOT NULL,
  `timestamp` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`, `ticket_ids`, `status`, `confirmed`, `verified`, `date`),
  KEY `booking_id` (`booking_id`),
  KEY `timestamp` (`timestamp`),
  KEY `user_id` (`user_id`)
) DEFAULT CHARSET=[:CHARSET:] COLLATE=[:COLLATE:];

CREATE TABLE IF NOT EXISTS `#__mec_booking_attendees` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `mec_booking_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `ticket_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mec_booking_id` (`mec_booking_id`),
  CONSTRAINT `mec_booking_id` FOREIGN KEY (`mec_booking_id`) REFERENCES `#__mec_bookings`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) DEFAULT CHARSET=[:CHARSET:] COLLATE=[:COLLATE:];
