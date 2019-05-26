# Amazing intervals

Incredible system of injecting date ranges into database in optimal way


### Requirements

```
PHP 7.2
MySQL 5.6
```

### Installing

Create MySQL database
```
CREATE TABLE `interval` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `price` float NOT NULL,
  `property_id` int(11) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `property_date` (`property_id`,`date_start`),
) ENGINE=InnoDB;
```

Run http server on document root `public`

```
cd public
php -S 127.0.0.1:8000
```

Navigate to `http://127.0.0.1:8000` in browser

## Running the tests

Run `IntervalModelTest.php` within `phpUnit`

### Performance test

Populate database with fake data.
Run

```
php scripts/generator.php -n 100000
```
This will populate table `interval_full` with number of intervals. Choose `Full table` in web interface.
