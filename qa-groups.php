<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

	require_once QA_INCLUDE_DIR . 'db/users.php';
	require_once QA_INCLUDE_DIR . 'util/string.php';
	require_once QA_INCLUDE_DIR . 'app/users.php';
	require_once QA_INCLUDE_DIR . 'app/blobs.php';
	require_once QA_PLUGIN_DIR . 'q2a-groups/qa-base.php';
    require_once QA_PLUGIN_DIR . 'q2a-groups/db/selects.php';

	class group_plugin
	{
		private $directory;
		private $urltoroot;
		private $user;
		private $dates;

		public function load_module($directory, $urltoroot)
		{
			$this->directory = $directory;
			$this->urltoroot = $urltoroot;
		}

		public function suggest_requests() // for display in admin INTerface
		{
			return array(
				array(
					'title' => qa_lang('group_lang/group_title'),
					'request' => 'groups',
					'nav' => 'M',
				),
			);
		}

		public function match_request( $request )
		{
			return strpos($request, 'groups') !== false;
		}
		
		function init_queries( $tableslc )
		{
			$tbl1 = qa_db_add_table_prefix('group');
			$tbl2 = qa_db_add_table_prefix('group_cats');
			$tbl3 = qa_db_add_table_prefix('group_posts');
			$tbl4 = qa_db_add_table_prefix('group_users');

			if (in_array($tbl1, $tableslc) && 
				in_array($tbl2, $tableslc) && 
				in_array($tbl3, $tableslc) && 
				in_array($tbl4, $tableslc))
				return null;
			
			return array(
				//catid, type, title, tags, content, note, avatarblobid, avatarwidth, avatarheight, coverblobid, coverwidth, coverheight, pcount, userid, lastpost, created, updated
				'CREATE TABLE IF NOT EXISTS ^group (
					`groupid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`catid` INT(10) UNSIGNED DEFAULT NULL,
					`type` ENUM("PUBLIC", "PRIVATE") NOT NULL,
					`title` VARCHAR(80) NOT NULL,
					`tags` VARCHAR(200) NOT NULL,
					`content` VARCHAR(800) NOT NULL DEFAULT \'\',
					`note` VARCHAR(800) NOT NULL DEFAULT \'\',
					`avatarblobid` BIGINT UNSIGNED,
					`avatarwidth` SMALLINT UNSIGNED,
					`avatarheight` SMALLINT UNSIGNED,
					`coverblobid` BIGINT UNSIGNED,
					`coverwidth` SMALLINT UNSIGNED,
					`coverheight` SMALLINT UNSIGNED,
					`pcount` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`position` SMALLINT(5) UNSIGNED NOT NULL,
					`backpath` VARCHAR(804) NOT NULL DEFAULT \'\',
					`userid` INT(10) UNSIGNED DEFAULT NULL,
					`lastpost` DATETIME,
					`created` DATETIME NOT NULL,
					`updated` DATETIME,
					PRIMARY KEY (`groupid`),
					KEY `backpath` (`backpath`(200))
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',
				
				'CREATE TABLE IF NOT EXISTS ^group_cats (
					`catid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`parentid` INT(10) UNSIGNED DEFAULT NULL,
					`title` VARCHAR(80) NOT NULL,
					`tags` VARCHAR(200) NOT NULL,
					`content` VARCHAR(800) NOT NULL DEFAULT \'\',
					`gcount` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`position` SMALLINT(5) UNSIGNED NOT NULL,
					PRIMARY KEY (`catid`),
					UNIQUE `parentid` (`parentid`, `tags`),
					UNIQUE `parentid_2` (`parentid`, `position`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',

				'CREATE TABLE IF NOT EXISTS ^group_posts (
					`postid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`type` ENUM("Q", "A", "C", "Q_HIDDEN", "A_HIDDEN", "C_HIDDEN", "Q_QUEUED", "A_QUEUED", "C_QUEUED", "NOTE") NOT NULL,
					`groupid` INT UNSIGNED,
					`parentid` INT UNSIGNED,
					`categoryid` INT UNSIGNED,
					`catidpath1` INT UNSIGNED,
					`catidpath2` INT UNSIGNED,
					`catidpath3` INT UNSIGNED,
					`acount` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
					`amaxvote` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
					`selchildid` INT UNSIGNED,
					`closedbyid` INT UNSIGNED,
					`userid` INT(10) UNSIGNED DEFAULT NULL,
					`cookieid` BIGINT UNSIGNED,
					`createip` VARBINARY(16),
					`lastuserid` INT(10) UNSIGNED DEFAULT NULL,
					`lastip` VARBINARY(16),
					`upvotes` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
					`downvotes` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
					`netvotes` SMALLINT NOT NULL DEFAULT 0,
					`lastviewip` VARBINARY(16),
					`views` INT UNSIGNED NOT NULL DEFAULT 0,
					`hotness` FLOAT,
					`flagcount` TINYINT UNSIGNED NOT NULL DEFAULT 0,
					`format` VARCHAR(20) CHARACTER SET ascii NOT NULL DEFAULT \'\',
					`created` DATETIME NOT NULL,
					`updated` DATETIME,
					`updatetype` char(1) CHARACTER SET ascii DEFAULT NULL,
					`title` VARCHAR(800) DEFAULT NULL,
					`content` VARCHAR(18000) DEFAULT NULL,
					`tags` VARCHAR(800) DEFAULT NULL,
					`name` VARCHAR(40) DEFAULT NULL,
					`notify` VARCHAR(80) DEFAULT NULL,
					PRIMARY KEY (`postid`),
					KEY `type` (`type`, `created`),
					KEY `type_2` (`type`, `acount`, `created`),
					KEY `type_4` (`type`, `netvotes`, `created`),
					KEY `type_5` (`type`, `views`, `created`),
					KEY `type_6` (`type`, `hotness`),
					KEY `type_7` (`type`, `amaxvote`, `created`),
					KEY `parentid` (`parentid`, `type`),
					KEY `userid` (`userid`, `type`, `created`),
					KEY `selchildid` (`selchildid`, `type`, `created`),
					KEY `closedbyid` (`closedbyid`), 
					KEY `catidpath1` (`catidpath1`, `type`, `created`),
					KEY `catidpath2` (`catidpath2`, `type`, `created`),
					KEY `catidpath3` (`catidpath3`, `type`, `created`),
					KEY `categoryid` (`categoryid`, `type`, `created`), 
					KEY `createip` (`createip`, `created`),
					KEY `updated` (`updated`, `type`), 
					KEY `flagcount` (`flagcount`, `created`, `type`), 
					KEY `catidpath1_2` (`catidpath1`, `updated`, `type`),
					KEY `catidpath2_2` (`catidpath2`, `updated`, `type`),
					KEY `catidpath3_2` (`catidpath3`, `updated`, `type`),
					KEY `categoryid_2` (`categoryid`, `updated`, `type`),
					KEY `lastuserid` (`lastuserid`, `updated`, `type`),
					KEY `lastip` (`lastip`, `updated`, `type`),
					CONSTRAINT `^group_posts_ibfk_2` FOREIGN KEY (`parentid`) REFERENCES ^group_posts(`postid`),
					CONSTRAINT `^group_posts_ibfk_3` FOREIGN KEY (`categoryid`) REFERENCES ^categories(`categoryid`) ON DELETE SET NULL,
					CONSTRAINT `^group_posts_ibfk_4` FOREIGN KEY (`closedbyid`) REFERENCES ^group_posts(`postid`)			
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',
				
				'CREATE TABLE IF NOT EXISTS ^group_users (
					`userid` INT(10) UNSIGNED NOT NULL,
					`groupid` INT UNSIGNED,
					`type` ENUM("MANAGER", "EDITOR", "USER") NOT NULL,
					`lastposted` DATETIME NOT NULL,
					`pcount` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`ccount` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`dcount` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`points` INT NOT NULL DEFAULT 0,
					`cselects` MEDIUMINT NOT NULL DEFAULT 0,
					`cselecteds` MEDIUMINT NOT NULL DEFAULT 0,
					`pupvotes` MEDIUMINT NOT NULL DEFAULT 0,
					`pdownvotes` MEDIUMINT NOT NULL DEFAULT 0,
					`cupvotes` MEDIUMINT NOT NULL DEFAULT 0,
					`cdownvotes` MEDIUMINT NOT NULL DEFAULT 0,
					`pvoteds` INT NOT NULL DEFAULT 0,
					`cvoteds` INT NOT NULL DEFAULT 0,
					`upvoteds` INT NOT NULL DEFAULT 0,
					`downvoteds` INT NOT NULL DEFAULT 0,
					`kickeduntil` DATETIME NOT NULL,
					PRIMARY KEY (`userid`),
					KEY `userid` (`userid`),
					KEY `points` (`points`),
					KEY `active` (`lastposted`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			);
		}
		
		public function process_request( $request )
		{
			global $qa_request;
			$action = qa_request_part(1);

			if (is_numeric( $action )) {
				$qa_content = require QA_PLUGIN_DIR . 'q2a-groups/pages/group.php';
			} else {
				switch ( $action ) {
					case 'discover':
						$qa_content = require QA_PLUGIN_DIR . 'q2a-groups/pages/discover.php';
						break;

					case 'create':
						$qa_content = require QA_PLUGIN_DIR . 'q2a-groups/pages/create.php';
						break;
				
					case 'admin_cats':
						$qa_content = require QA_PLUGIN_DIR . 'q2a-groups/pages/home.php';
						break;
					
					default:
						$qa_content = require QA_PLUGIN_DIR . 'q2a-groups/pages/home.php';
						break;
				}
			}
			return $qa_content;
		}
	}
