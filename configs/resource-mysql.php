<?php
if (!defined('config_leedch_mysql')) {
    define('config_leedch_mysql', true);
    
    //Root path of Application (not webroot)
    define('leedch_resourceMysqlPathRoot', __DIR__ . '/../');
    define('leedch_resourceMysqlPathLogFile', leedch_resourceMysqlPathRoot.'/log/mysql.log');
    define('leedch_resourceMysqlLogName', 'Mysql');
    
    //Put your DB Configurations here
    define('leedch_resourceMysqlHost', 'localhost');
    define('leedch_resourceMysqlDatabase', 'leed_website');
    define('leedch_resourceMysqlUsername', 'root');
    define('leedch_resourceMysqlPassword', 'toor');
    define('leedch_resourceMysqlCharset', 'utf8');
    
    //These Settings are needed to be able to load Database Updates
    define('leedch_resourceMysqlUpdateFolder', __DIR__ . '/../updates/mysql/'); //Base Folder of Update Files
    define('leedch_mysqlResourceTableDbUpdate', 'db_update'); //Table to store History
    
    //You need these Values if you want to get E-Mail Alerts on errors, if you dont want this
    //just set all to null
    define('leedch_resourceMysqlAppName', null); //Name of your Website/Application
    define('leedch_resourceMysqlLogEmail', null); //Email Address to receive message
    define('leedch_resourceMysqlLogServerEmail', null); //Sender Email from Server
}

