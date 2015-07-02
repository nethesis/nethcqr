CREATE TABLE IF NOT EXISTS `nethcqr_details` (
  `id_cqr` int(11) NOT NULL auto_increment,
  `name` varchar(60) NOT NULL UNIQUE,
  `description` varchar(120) default NULL,
  `announcement` int(11),
  `use_code` BOOLEAN default FALSE,
  `manual_code` BOOLEAN default FALSE, 
  `use_workphone` BOOLEAN default TRUE, 
  `cod_cli_announcement` int(11) DEFAULT NULL,
  `err_announcement` int(11) DEFAULT NULL,
  `code_length` int(2) default 5,
  `code_retries` int(1) default 3,
  `db_type` varchar(30) default 'mysql',
  `db_url` varchar(60) default 'localhost',
  `db_name` varchar(30) default NULL,
  `db_user` varchar(30) default NULL,
  `db_pass` varchar(90) default NULL,
  `query` varchar(8000) default NULL,
  `cc_db_type` varchar(30) default 'mysql',
  `cc_db_url` varchar(60) default 'localhost',
  `cc_db_name` varchar(30) default NULL,
  `cc_db_user` varchar(30) default NULL,
  `cc_db_pass` varchar(90) default NULL,
  `cc_query` varchar(8000) default NULL,
  `ccc_query` varchar(8000) default NULL,
  `default_destination` varchar(50) default NULL,
  PRIMARY KEY  (`id_cqr`)
);

CREATE TABLE IF NOT EXISTS `nethcqr_entries` (
  `id_dest` int(11) NOT NULL auto_increment,
  `id_cqr` int(11) NOT NULL,
  `position` int(11),
  `condition` varchar(8000) default NULL,
  `destination` varchar(50) default NULL,
  PRIMARY KEY  (`id_dest`)
);



