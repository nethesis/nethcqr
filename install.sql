CREATE TABLE `cqr` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(60) default NULL,
  `description` varchar(120) default NULL,
  `db_type` varchar(30) default 'mysql',
  `db_url` varchar(60) default 'localhost',
  `db_name` varchar(30) default NULL,
  `db_user` varchar(30) default NULL,
  `db_pass` varchar(90) default NULL,
  `query` varchar(8000) default NULL,
  PRIMARY KEY  (`id`)
);


