<?php

namespace Leedch\Trakt\Trakt;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Leedch\Trakt\Trakt\Config;

/**
 * Description of Trakt
 *
 * @author leed
 */
class Connector
{
    protected static $instance;
    protected $client; 
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
            $this->client = new Client(['base_uri' => trakt_uri]);
            return true;
        } catch (Exception $ex) {
            unset($this->client);
            throw new Exception("Cannot connect to Trakt");
        }
        return false;
    }
    
    protected function makeHeaders()
    {
        $this->headers = [
            'Content-Type' => 'application/json',
            'trakt-api-key' => trakt_clientId,
            'trakt-api-version' => 2,
        ];
        
        $config = new Config();
        $accessToken = $config->getConfig("access_token");
        $tokenType = $config->getConfig("token_type");
        if (!$accessToken) {
            return;
        }
        $this->headers['Authorization'] = $tokenType." ".$accessToken;
    }

    /**
     * Refresh the Access Token if older than 3 days
     * @return null
     */
    protected function refreshAccessTokenIfOld()
    {
        $config = new Config();
        $accessTokenRow = $config->getConfigRow("access_token");
        if (!$accessTokenRow) {
            return;
        }
        
        $datetime = strtotime($accessTokenRow['createDate']);
        $current = time();
        if (($current - $datetime) > 259200) {
            $this->refreshAccessToken();
        }
        return;
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
        
        $this->headers['debug'] = true;
        
        $request = new Request($verb, trakt_uri.'/'.$uri, $this->headers, $body);
        $response = $this->client->send($request, ['timeout' => $timeout]);
        
        return $response;
    }
    
    /**
     * Fetches the device_code from trakt and saves it to db
     */
    public function getDeviceCode()
    {
        $config = new Config();
        $body = '{"client_id": "'.trakt_clientId.'"}';
        $response = $this->call('POST', "oauth/device/code", $body);
        $json = $response->getBody()->getContents();
        $arrResponse = json_decode($json, true);
        if (!$this->validateDeviceCodeResponse($arrResponse)) {
            throw new Exception('Bad Response for getDeviceCode');
        }
        
        $config->setConfig("device_code", $arrResponse['device_code']);
        $config->setConfig("user_code", $arrResponse['user_code']);
        $config->setConfig("verification_url", $arrResponse['verification_url']);
    }
    
    /**
     * Validates the Response from getDeviceCode
     * 
     * @param array $arrResponse
     * @return boolean
     */
    protected function validateDeviceCodeResponse(array $arrResponse)
    {
        $arrMustExist = [
            "device_code",
            "user_code",
            "verification_url",
            "expires_in",
            "interval",
        ];
        
        foreach ($arrMustExist as $value) {
            if (!isset($arrResponse[$value])) {
                return false;
            }
        }
        return true;
    }
    
    public function getAccessToken()
    {
        $config = new Config();
        $deviceCode = (string) $config->getConfig("device_code");
        $clientId = trakt_clientId;
        $clientSecret = trakt_clientSecret;
        $arrBody = [
            "code" => $deviceCode,
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
        ];
        $body = json_encode($arrBody, JSON_UNESCAPED_UNICODE);
        $response = $this->call('POST', 'oauth/device/token', $body);
        $statusCode = (int) $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new Exception('OAuth Login Failed');
        }
        
        $jsonResponse = $response->getBody()->getContents();
        $arrResponse = json_decode($jsonResponse, true);
        
        if (!$this->validateAccessTokenResponse($arrResponse)) {
            throw new Exception('OAuth Login Failed to give Access Token');
        }
        
        $config->setConfig("access_token", $arrResponse['access_token']);
        $config->setConfig("token_type", $arrResponse['token_type']);
        $config->setConfig("scope", $arrResponse['scope']);
        $config->setConfig("refresh_token", $arrResponse['refresh_token']);
    }
    
    /**
     * Refreshes the Access Token
     * @throws Exception
     */
    public function refreshAccessToken()
    {
        $config = new Config();
        $refreshToken = (string) $config->getConfig("refresh_token");
        $clientId = trakt_clientId;
        $clientSecret = trakt_clientSecret;
        $arrBody = [
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "refresh_token" => $refreshToken,
            "grant_type" => "refresh_token",
        ];
        $body = json_encode($arrBody, JSON_UNESCAPED_UNICODE);
        $response = $this->call('POST', 'oauth/token', $body);
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
        
        $config->setConfig("access_token", $arrResponse['access_token']);
        $config->setConfig("token_type", $arrResponse['token_type']);
        $config->setConfig("scope", $arrResponse['scope']);
        $config->setConfig("refresh_token", $arrResponse['refresh_token']);
    }
    
    /**
     * Check for valid response from access_token
     * @param array $arrResponse
     * @return boolean
     */
    protected function validateAccessTokenResponse(array $arrResponse)
    {
        $arrExpected = [
            "access_token",
            "token_type",
            "expires_in",
            "refresh_token",
            "scope",
            "created_at",
        ];
        foreach ($arrExpected as $expected) {
            if (!isset($arrResponse[$expected])) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Checks the Config Database Table for "device_id"
     * @return boolean
     */
    public function hasDeviceCode() {
        $config = new Config();
        $deviceId = (string) $config->getConfig('device_code');
        if (!$deviceId) {
            return false;
        }
        return true;
    }
    
    /**
     * Checks the config database table for "access_token"
     * @return boolean
     */
    public function hasAccessToken()
    {
        $config = new Config();
        $accessToken = (string) $config->getConfig('access_token');
        if (!$accessToken) {
            return false;
        }
        return true;
    }
}
