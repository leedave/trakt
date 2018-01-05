<?php
$arrUpdate = [
    "CREATE TABLE IF NOT EXISTS `".trakt_mysqlTableTraktConfig."`("
    . "`id` INT(11) NOT NULL AUTO_INCREMENT, "
    . "`name` VARCHAR(255) NOT NULL, "
    . "`value` VARCHAR(255) NOT NULL, "
    . "`createDate` DATETIME NOT NULL, "
    . "PRIMARY KEY (`id`)"
    . ") ENGINE=InnoDB;",
];
