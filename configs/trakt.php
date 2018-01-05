<?php
if (!defined('trakt_constants')) {
    define('trakt_constants', true);
    
    //Get an account @ www.trakt.tv
    define('trakt_uri', 'https://api.trakt.tv');
    //Get a client_id and client_secret @ https://trakt.tv/oauth/applications (account required)
    define('trakt_clientId', '');
    define('trakt_clientSecret', '');
    
    //Get an account @ www.themoviedb.org
    define('tmdb_uri', 'https://api.themoviedb.org/3');
    define('tmdb_imgMovie_uri', 'https://image.tmdb.org/t/p/w500');
    //From your account https://www.themoviedb.org/settings/api
    define('tmdb_api3Auth', '');
    
    //Get an account @ www.thetvdb.com
    define('tvdb_uri', 'https://api.thetvdb.com/');
    define('tvdb_imgShow_uri', 'https://www.thetvdb.com/banners/');
    //Apply for an API Key here https://www.thetvdb.com/?tab=apiregister
    define('tvdb_apiKey', '');
    
    //Log Data, adjust to meet your needs
    define('trakt_logFile', __DIR__ . '../../../log/trakt.log');
    define('trakt_logSendMailOnCritical', true);
    define('trakt_logEmailRecepient', 'email@domain.com');
    define('trakt_logEmailSender', 'email@domain.com');
    define('trakt_logEmailWebsiteName', 'Trakt Control Center');
    
    
    //Table name, you can change them, but I wouldn't
    define('trakt_mysqlTableTraktConfig', 'trakt_config');
    define('trakt_mysqlTableTraktUser', 'trakt_user');
    define('trakt_mysqlTableTraktImage', 'trakt_image');
    define('trakt_mysqlTableTraktCollection', 'trakt_collection');
    define('trakt_mysqlTableTraktRating', 'trakt_rating');
    define('trakt_mysqlTableTraktHistory', 'trakt_history');
    define('tmdb_mysqlTableTmdbImage', 'tmdb_image');
    define('tvdb_mysqlTableConfig', 'tvdb_config');
    define('tvdb_mysqlTableImage', 'tvdb_image');
    
    //Here Trakt Images are saved
    define('trakt_mediaFolder', leedch_pathAdminMedia . "trakt/");
    define('trakt_mediaFolderUrl', 'media/trakt/'); //Url path to above folder
    //Here TMDB Images are saved
    define('tmdb_mediaFolder', leedch_pathAdminMedia . "tmdb/");
    define('tmdb_mediaFolderUrl', 'media/tmdb/'); //Url path to above folder
    //Here TVDB Images are saved
    define('tvdb_mediaFolder', leedch_pathAdminMedia . "tvdb/");
    define('tvdb_mediaFolderUrl', 'media/tvdb/'); //Url path to above folder
    
    define('trakt_templateFolder', leedch_pathRoot . 'trakt/templates/');
    define('trakt_templateAdminRegisterDevice', trakt_templateFolder . 'registerDevicePage.html');
    define('trakt_templateAdminDashboard', trakt_templateFolder . 'dashboard.html');
    define('trakt_templateAdminMovieDetail', trakt_templateFolder . 'movieDetail.html');
    define('trakt_templateAdminShowDetail', trakt_templateFolder . 'showDetail.html');
    define('trakt_templateAdminShowSeason', trakt_templateFolder . 'showSeason.html');
    define('trakt_templateAdminMovieNotRated', trakt_templateFolder . 'controls/notRatedMovie.html');
    define('trakt_templateAdminEpisodeNotRated', trakt_templateFolder . 'controls/notRatedEpisode.html');
    define('trakt_templateAdminMovieRated', trakt_templateFolder . 'controls/rated.html');
    define('trakt_templateAdminMovieNotWatched', trakt_templateFolder . 'controls/notWatched.html');
    define('trakt_templateAdminEpisodeNotWatched', trakt_templateFolder . 'controls/notWatchedEpisode.html');
    define('trakt_templateAdminMovieWatched', trakt_templateFolder . 'controls/watched.html');
    define('trakt_templateAdminMovieCollected', trakt_templateFolder . 'controls/collected.html');
    define('trakt_templateAdminMovieNotCollected', trakt_templateFolder . 'controls/notCollected.html');
    define('trakt_templateAdminEpisodeCollected', trakt_templateFolder . 'controls/collectedEpisode.html');
    define('trakt_templateAdminEpisodeNotCollected', trakt_templateFolder . 'controls/notCollectedEpisode.html');
}