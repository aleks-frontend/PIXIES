# phpLedAds 2.0 SQL Def file
# http://www.ledscripts.com/

# Do NOT try to dump this straight in
# If you must do dump this in yourself, then replace all of the
# {prefix} with whatever prefix you decided on when configuring everything

# Host: localhost
# Generation Time: Feb 11, 2002 at 08:32 PM
# Server version: 3.23.42
# Database : `pla`
# --------------------------------------------------------

#
# Table structure for table `pla_ads`
#

CREATE TABLE {prefix}_ads (
  aid int(10) unsigned NOT NULL auto_increment,
  type enum('image','rich') NOT NULL default 'image',
  did int(10) unsigned NOT NULL default '0',
  active enum('yes','no') NOT NULL default 'yes',
  datetime datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (aid)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `pla_images`
#

CREATE TABLE {prefix}_images (
  did int(10) unsigned NOT NULL auto_increment,
  image_url varchar(255) NOT NULL default '',
  url varchar(255) NOT NULL default '',
  alt_text varchar(150) NOT NULL default '',
  target varchar(20) NOT NULL default '',
  width int(11) NOT NULL default '0',
  height int(11) NOT NULL default '0',
  PRIMARY KEY  (did)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `pla_impressions`
#

CREATE TABLE {prefix}_impressions (
  aid int(10) unsigned NOT NULL default '0',
  impdate date NOT NULL default '0000-00-00',
  displays bigint(20) unsigned NOT NULL default '0',
  clicks int(11) NOT NULL default '0',
  PRIMARY KEY  (aid,impdate)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `pla_richtext`
#

CREATE TABLE {prefix}_richtext (
  did int(10) unsigned NOT NULL auto_increment,
  data mediumtext NOT NULL,
  PRIMARY KEY  (did)
) TYPE=MyISAM;