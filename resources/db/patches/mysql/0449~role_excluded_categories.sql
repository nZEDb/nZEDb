# Create role_excluded_categories table

DROP TABLE IF EXISTS role_excluded_categories;
CREATE TABLE role_excluded_categories
(
    id              INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
    role            INT(11) NOT NULL,
    categories_id   INT(11),
    createddate     DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX ix_roleexcat_rolecat (role, categories_id)
)
    ENGINE = MyISAM
    DEFAULT CHARSET = utf8
    COLLATE = utf8_unicode_ci
    AUTO_INCREMENT = 1;
