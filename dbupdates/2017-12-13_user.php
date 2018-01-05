<?php
$arrUpdate = [
    "CREATE TABLE IF NOT EXISTS `".trakt_mysqlTableTraktUser."`("
    . "`id` INT(11) NOT NULL AUTO_INCREMENT, "
    . "`username` VARCHAR(255) NOT NULL, "
    . "`avatar` VARCHAR(255) NOT NULL, "
    . "`json` TEXT NOT NULL, "
    . "`createDate` DATETIME NOT NULL, "
    . "`updateDate` DATETIME NOT NULL, "
    . "PRIMARY KEY (`id`)"
    . ") ENGINE=InnoDB;",
];
