<?php
$arrUpdate = [
    "CREATE TABLE IF NOT EXISTS `".trakt_mysqlTableTraktImage."`("
    . "`id` INT(11) NOT NULL AUTO_INCREMENT, "
    . "`srcPath` VARCHAR(255) NOT NULL, "
    . "`cachePath` VARCHAR(255) NOT NULL, "
    . "`createDate` DATETIME NOT NULL, "
    . "PRIMARY KEY (`id`)"
    . ") ENGINE=InnoDB;",
];
