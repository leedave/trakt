<?php

namespace Leedch\Trakt\Trakt;

use Exception;
use Leedch\Mysql\Mysql;
use Leedch\Filehandler\File;

/**
 * Description of Image
 *
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
        return trakt_mysqlTableTraktImage;
    }
    
    /**
     * Fetch from cache if available, else download
     * @param string $traktPath
     * @return string
     */
    public function getImage(string $traktPath)
    {
        if (!$this->existsInDb($traktPath)) {
            $this->downloadImage($traktPath);
        }
        $this->loadByPath($traktPath);
        return $this->cachePath;
    }
    
    /**
     * Load Data from DB by traktPath
     * @param string $traktPath
     * @throws Exception
     */
    protected function loadByPath(string $traktPath)
    {
        $rows = $this->getAllRows(["*"], ["`srcPath` = '".$traktPath."'"], [], [0,1]);
        if (count($rows) < 1) {
            throw new Exception('Image not found in trakt image db');
        }
        $row = array_pop($rows);
        $this->loadWithData($row);
    }
    
    /**
     * Check if Image is cached in DB
     * @param string $traktPath
     * @return boolean
     */
    protected function existsInDb(string $traktPath)
    {
        try {
            $this->loadByPath($traktPath);
        } catch (Exception $ex) {
            return false;
        }
        
        return true;
    }
    
    /**
     * API Images must be cached, this downloads the image and makes a db reference to 
     * $traktPath
     * @param string $traktPath
     */
    protected function downloadImage(string $traktPath)
    {
        $arrTraktPath = explode(DIRECTORY_SEPARATOR, $traktPath);
        $fileName = array_pop($arrTraktPath);
        $mediaFolder = File::getFolder(trakt_mediaFolder);
        $target = $mediaFolder . $fileName;
        $content = file_get_contents($traktPath);
        $savedTarget = File::saveFile($target, $content);
        $this->srcPath = $traktPath;
        $this->cachePath = $savedTarget;
        $this->save();
    }
}
