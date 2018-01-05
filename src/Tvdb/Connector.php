<?php

namespace Leedch\Trakt\Tvdb;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Leedch\Trakt\Tvdb\Config;

/**
 * Description of Connector
 *
 * @author leed
 */
class Connector
{
    protected static $instance;
    protected $client = null; 
    protected $access_token;
    protected $headers;
    
    /**
     * Make a static method that returns the instanciated class object
     * @return \Leedch\Website\Trakt\Core\Connector
     */
    public static function getInstance(){
        if(!self::$instance){
            //The first time called, your class creates the object
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    protected function connect()
    {
        $this->makeHeaders();
        if ($this->client) {
            //Already exists
            return true;
        }
        try {
            $this->client = new Client(['base_uri' => tvdb_uri]);
            return true;
        } catch (Exception $ex) {
            if (isset($this->client)) {
                $this->client = null;
            }
            throw new Exception("Cannot connect to Tmdb: ".$ex->getMessage());
        }
        return false;
    }
    
    protected function makeHeaders()
    {
        $config = new Config();
        $token = $config->getConfigRow('access_token');
        if (!$token) {
            $this->headers = [
                'Content-Type' => 'application/json',
            ];
        }
        /*$tokenDate = strtotime($token['createDate']);
        $current = time();
        if (($current - $tokenDate) > 43200) {
            
        }*/
        $this->headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$token['value'],
        ];
    }


    /**
     * Sends a call to the api
     * @param string $verb  GET, PUT, POST, DELETE
     * @param string $uri test => http://api.trakt.tv/api/test  /root => http://api.trakt.tv/root
     * @param int $timeout  seconds
     * @return type
     */
    public function call(string $verb, string $uri, string $body = "", int $timeout = 2)
    {
        $this->connect();
        $request = new Request($verb, tvdb_uri.$uri, $this->headers, $body);
        $response = $this->client->send($request, ['timeout' => $timeout]);
        return $response;
        //$responseJson = $response->getBody()->getContents();
        //return $responseJson;
    }
    
    /**
     * Retrieve an Access Token from API
     * @throws Exception
     */
    public function getAccessToken()
    {
        $config = new Config();
        $arrBody = [
            "apikey" => tvdb_apiKey,
        ];
        $body = json_encode($arrBody, JSON_UNESCAPED_UNICODE);
        $response = $this->call('POST', 'login', $body);
        $statusCode = (int) $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new Exception('OAuth Login Failed');
        }
        
        $jsonResponse = $response->getBody()->getContents();
        $arrResponse = json_decode($jsonResponse, true);
        
        if (!$this->validateAccessTokenResponse($arrResponse)) {
            throw new Exception('OAuth Login Failed to give Access Token');
        }
        
        $config->setConfig("access_token", $arrResponse['token']);
        if (isset($this->client)) {
            $this->client = null;
        }
        return $config->getConfigRow("access_token");
    }
    
    protected function refreshAccessTokenIfOld()
    {
        $config = new Config();
        $tokenRow = $config->getConfigRow("access_token");
        if (!$tokenRow) {
            return $this->getAccessToken();
        }
        $date = strtotime($tokenRow['createDate']);
        $current = time();
        if (($current - $date) > 21600) {
            $this->headers['token'] = $tokenRow['value'];
            return $this->getAccessToken();
        }
    }
    
    /**
     * Refreshes the Access Token
     * @throws Exception
     */
    protected function refreshAccessToken()
    {
        $config = new Config();
        //$response = $this->call('GET', 'refresh_token');
        
        $request = new Request('GET', tvdb_uri.'refresh_token', $this->headers);
        $response = $this->client->send($request, ['timeout' => 2]);
        $statusCode = (int) $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new Exception('OAuth Login Failed');
        }
        
        $jsonResponse = $response->getBody()->getContents();
        $arrResponse = json_decode($jsonResponse, true);
        
        if (!$this->validateAccessTokenResponse($arrResponse)) {
            print($arrResponse);
            throw new Exception('OAuth Login Failed to give Access Token');
        }
        
        $config->setConfig("access_token", $arrResponse['token']);
        if (isset($this->client)) {
            $this->client = null;
        }
        $this->client = new Client(['base_uri' => tvdb_uri]);
    }
    
    /**
     * Check if response from Token Service is correct
     * @param array $arrResponse
     * @return boolean
     * @throws Exception
     */
    protected function validateAccessTokenResponse(array $arrResponse)
    {
        $expected = ["token"];
        foreach ($expected as $key) {
            if (!isset($arrResponse[$key])) {
                throw new Exception('Invalid Response from Token Service');
            }
        }
        return true;
    }
    
    public function findShow(int $tvdbId)
    {
        try {
            return $this->call("GET", "series/".$tvdbId, '', 10);
        } catch (Exception $ex) {
            $this->connect();
            $this->getAccessToken();
            return $this->call("GET", "series/".$tvdbId, '', 10);
        }
    }
}
