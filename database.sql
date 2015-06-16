SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `thumbnail` varchar(255) DEFAULT NULL,
  `full_filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `books` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `dir` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_id` varchar(32) NOT NULL,
  `full_filename` varchar(255) NOT NULL,
  `sha` varchar(40) NOT NULL,
  `content` longtext NOT NULL,
  `base64_md5` varchar(255) NOT NULL,
  `size` int(20) NOT NULL,
  `html_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `full_filename` (`full_filename`),
  KEY `reference_id` (`reference_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `fragments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `reference_id` varchar(32) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `full_filename` varchar(255) NOT NULL,
  `menu_label` varchar(255) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  `child` tinyint(4) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `reference_id` (`reference_id`),
  KEY `full_filename` (`full_filename`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `github_references` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_id` varchar(32) NOT NULL,
  `repo_user` varchar(255) NOT NULL,
  `repo_name` varchar(255) NOT NULL,
  `repo_path` varchar(255) NOT NULL,
  `sha` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reference_id` (`reference_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `references` (
  `id` varchar(32) NOT NULL,
  `book_id` int(11) NOT NULL,
  `subref` varchar(30) NOT NULL,
  `synchrony` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  UNIQUE KEY `id` (`id`),
  KEY `book_id` (`book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `related_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) NOT NULL,
  `book_id` int(11) DEFAULT NULL,
  `article_id` int(11) DEFAULT NULL,
  `bookmark_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
