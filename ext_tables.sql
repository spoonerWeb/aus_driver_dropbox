#
# TABLE STRUCTURE FOR TABLE 'cf_ausdriverdropbox_cache'
#
CREATE TABLE cf_ausdriverdropbox_cache (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    identifier VARCHAR(250) DEFAULT '' NOT NULL,
    crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
    content mediumblob,
    expires INT(11) UNSIGNED DEFAULT '0' NOT NULL,
    PRIMARY KEY (id),
    KEY cache_id (identifier)
) ENGINE=InnoDB;

#
# TABLE STRUCTURE FOR TABLE 'cf_ausdriverdropbox_cache_tags'
#
CREATE TABLE cf_ausdriverdropbox_cache_tags (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    identifier VARCHAR(250) DEFAULT '' NOT NULL,
    tag VARCHAR(250) DEFAULT '' NOT NULL,
    PRIMARY KEY (id),
    KEY cache_id (identifier),
    KEY cache_tag (tag)
) ENGINE=InnoDB;
