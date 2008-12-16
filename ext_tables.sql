#
# Table structure for table 'tx_kesmallads_smallads'
#
CREATE TABLE tx_kesmallads_smallads (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	cat text NOT NULL,
	cat2 text NOT NULL,
	cat3 text NOT NULL,
	user text NOT NULL,
	content text NOT NULL,
	image text NOT NULL,
	phone tinytext NOT NULL,
	email tinytext NOT NULL,
	displayemail tinyint(3) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	reviewed tinyint(3) DEFAULT '0' NOT NULL,
	fe_user_uid blob NOT NULL,	
	comment text NOT NULL,
	iscommercial int(11) unsigned DEFAULT '0' NOT NULL,	

	PRIMARY KEY (uid),
	KEY parent (pid)
);
