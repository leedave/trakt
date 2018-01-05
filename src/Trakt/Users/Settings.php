<?php

namespace Leedch\Trakt\Trakt\Users;

use Exception;
use Leedch\Mysql\Mysql;
use Leedch\Trakt\Trakt\Connector;

/**
 * Description of Settings
 *
 * @author leed
 */
class Settings extends Mysql
{
    protected $connector;
    
    protected $id;
    protected $username;
    protected $avatar;
    protected $json;
    protected $createDate;
    protected $updateDate;
    
    public function __construct()
    {
        parent::__construct();
        $this->connector = Connector::getInstance();
    }
    
    protected function getTableName() : string
    {
        return trakt_mysqlTableTraktUser;
    }
    
    public function getSlug()
    {
        $arrData = json_decode($this->json, true);
        
        if (isset($arrData['user']['ids']['slug'])) {
            return $arrData['user']['ids']['slug'];
        }
        return "";
    }
    
    public function getUserSettings()
    {
        $rows = $this->getAllRows(['*'], [], [], [0,1]);
        if (count($rows)) {
            $row = array_pop($rows);
            $this->loadWithData($row);
            if (strtotime($this->updateDate) > (time() - 86400)) {
                return $this;
            }
        }
        $this->callUserSettings();
        return $this;
    }
    
    /**
     * Fetch Up-To-Date User Info from Trakt
     * @throws Exception
     */
    protected function callUserSettings()
    {
        $response = $this->connector->call('GET', 'users/settings');
        $responseCode = (int) $response->getStatusCode();
        if ($responseCode !== 200) {
            throw new Exception('Invalid response from UserData');
        }
        $responseJson = $response->getBody()->getContents();
        $arrUser = json_decode($responseJson, true);
        //print_r($responseJson);
        if (!$this->validateUserData($arrUser)) {
            throw new Exception('Invalid Userdata received');
        }
        
        $this->username = $arrUser['user']['username'];
        $this->avatar = $arrUser['user']['images']['avatar']['full'];
        $this->json = $responseJson;
        $this->createDate = strftime("%Y-%m-%d %H:%M:%S");
        $this->updateDate = strftime("%Y-%m-%d %H:%M:%S");
        $this->save();
    }
    
    /**
     * Validate Response from API
     * @param array $arrUser
     * @return boolean
     */
    protected function validateUserData(array $arrUser)
    {
        if (!isset($arrUser['user'])) {
            return false;
        }
        $arrUserData = $arrUser['user'];
        $arrExpected = [
            "username",
            "images",
        ];
        foreach ($arrExpected as $expected) {
            if (!isset($arrUserData[$expected])) {
                return false;
            }
        }
        return true;
    }
    
    public function getData()
    {
        return json_decode($this->json, true);
    }
}
