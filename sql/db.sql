CREATE TABLE `interval` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `price` float NOT NULL,
  `property_id` int(11) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `property_date` (`property_id`,`date_start`),
) ENGINE=InnoDB;