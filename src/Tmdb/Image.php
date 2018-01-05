<?php

namespace Leedch\Trakt\Tmdb;

use Exception;
use Leedch\Mysql\Mysql;
use Leedch\Filehandler\File;

/**
 * Dowloads and Caches Images from TMBD
 * @author leed
 */
class Image extends Mysql
{
    protected $id;
    protected $srcPath;
    protected $cachePath;
    protected $createDate;
    
    public function __construct()
    {
        parent::__construct();
        $this->connector = Connector::getInstance();
    }
    
    protected function getTableName() : string
    {
        return tmdb_mysqlTableTmdbImage;
    }
    
    /**
     * Fetch from cache if available, else download
     * @param string $path
     * @return string
     */
    public function getImage(string $path)
    {
        if (!$this->existsInDb($path)) {
            $this->downloadImage($path);
        }
        $this->loadByPath($path);
        return $this->cachePath;
    }
    
    public function getImageWebUrl(string $path)
    {
        $folderPath = $this->getImage($path);
        $arrPath = explode(DIRECTORY_SEPARATOR, $folderPath);
        $fileName = array_pop($arrPath);
        return '/' . tmdb_mediaFolderUrl . '/' . $fileName;
    }
    
    /**
     * Load Data from DB by srcPath
     * @param string $srcPath
     * @throws Exception
     */
    protected function loadByPath(string $srcPath)
    {
        $rows = $this->getAllRows(["*"], ["`srcPath` = '".$srcPath."'"], [], [0,1]);
        if (count($rows) < 1) {
            throw new Exception('Image not found in db');
        }
        $row = array_pop($rows);
        $this->loadWithData($row);
    }
    
    /**
     * Check if Image is cached in DB
     * @param string $srcPath
     * @return boolean
     */
    protected function existsInDb(string $srcPath)
    {
        try {
            $this->loadByPath($srcPath);
        } catch (Exception $ex) {
            return false;
        }
        
        return true;
    }
    
    /**
     * API Images must be cached, this downloads the image and makes a db reference to 
     * $srcPath
     * @param string $srcPath
     */
    protected function downloadImage(string $srcPath)
    {
        $arrPath = explode(DIRECTORY_SEPARATOR, $srcPath);
        $fileName = array_pop($arrPath);
        $mediaFolder = File::getFolder(tmdb_mediaFolder);
        $target = $mediaFolder . $fileName;
        $content = file_get_contents($srcPath);
        $savedTarget = File::saveFile($target, $content);
        $this->srcPath = $srcPath;
        $this->cachePath = $savedTarget;
        $this->save();
    }
}
