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


DROP TABLE IF EXISTS `#__localized_custom_fields`;
 
CREATE TABLE `#__localized_custom_fields` (
	`lcf_id`       INT(11)     NOT NULL AUTO_INCREMENT,
	`lcf_name` VARCHAR(128),
	`parent_only` BOOLEAN,
	`field_type` CHAR(1),
	`ordering` TINYINT(4) UNSIGNED DEFAULT 0,
	PRIMARY KEY (`lcf_id`)
)
	ENGINE =MyISAM
	AUTO_INCREMENT =0
	DEFAULT CHARSET =utf8;
	
	
DROP TABLE IF EXISTS `#__localized_custom_field_values`;	

CREATE TABLE `#__localized_custom_field_values` (
	`lcf_value_id`       INT(11)     NOT NULL AUTO_INCREMENT,
	`lcf_id` INT(11),
	`vm_product_id` INT(11),
	`vm_parent_id` INT(11),
	`lang` CHAR(5),
	`lcf_value` VARCHAR(1024),
	`image_file` VARCHAR(1024),
	`lcf_value_code` INT(11),
	PRIMARY KEY (`lcf_value_id`)
)
	ENGINE =MyISAM
	AUTO_INCREMENT =0
	DEFAULT CHARSET =utf8;
	
	
DROP TABLE IF EXISTS `#__lcf_to_category`;	

CREATE TABLE `#__lcf_to_category` (
	`id`       INT(11)     NOT NULL AUTO_INCREMENT,
	`lcf_id` INT(11),
	`category_id` INT(11),
	PRIMARY KEY (`id`)
)
	ENGINE =MyISAM
	AUTO_INCREMENT =0
	DEFAULT CHARSET =utf8;