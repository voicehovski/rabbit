DROP TABLE IF EXISTS `#__rabbit`;
 
CREATE TABLE `#__rabbit` (
	`id`       INT(11)     NOT NULL AUTO_INCREMENT,
	`message` BLOB,
	PRIMARY KEY (`id`)
)
	ENGINE =MyISAM
	AUTO_INCREMENT =0
	DEFAULT CHARSET =utf8;
 
INSERT INTO `#__rabbit` (`message`) VALUES
('Initial message'),
('Test message');


DROP TABLE IF EXISTS `#__rabbit_ccf`;
 
CREATE TABLE `#__rabbit_ccf` (
	`ccf_id`       INT(11)     NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(128),
	PRIMARY KEY (`ccf_id`)
)
	ENGINE =MyISAM
	AUTO_INCREMENT =0
	DEFAULT CHARSET =utf8;
	
INSERT INTO `#__rabbit_ccf` (`name`) VALUES
('TEST_CCF_1'),
('TEST_CCF_2');
	
	
DROP TABLE IF EXISTS `#__rabbit_vmp_ccf`;	

CREATE TABLE `#__rabbit_vmp_ccf` (
	`id`       INT(11)     NOT NULL AUTO_INCREMENT,
	`vmp_id` INT(11),
	`ccf_id` INT(11),
	`lang` CHAR(5),
	`ccf_value` VARCHAR(1024),
	PRIMARY KEY (`id`)
)
	ENGINE =MyISAM
	AUTO_INCREMENT =0
	DEFAULT CHARSET =utf8;
