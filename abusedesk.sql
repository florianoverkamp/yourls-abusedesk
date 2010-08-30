CREATE TABLE IF NOT EXISTS `yourls_banned` (
  `ban` varchar(255) NOT NULL,
  `bantype` enum('src','dst') NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `reason` text NOT NULL,
  `clicks` int(10) NOT NULL default '0',
  PRIMARY KEY  (`ban`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `yourls_blocked` (
  `keyword` varchar(200) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ip` varchar(41) default NULL,
  `reason` text,
  `addr` varchar(200) default NULL,
  PRIMARY KEY  (`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

