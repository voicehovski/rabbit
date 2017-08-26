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