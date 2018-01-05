<?php

namespace Leedch\Trakt\Tmdb;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Description of Connector
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
            throw new Exception("Cannot connect to Tmdb");
        }
        return false;
    }
    
    protected function makeHeaders()
    {
        $this->headers = [
            'Content-Type' => 'application/json',
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
        
        $request = new Request($verb, tmdb_uri.$uri, $this->headers, $body);
        $response = $this->client->send($request, ['timeout' => $timeout]);
        $responseJson = $response->getBody()->getContents();
        return $responseJson;
    }
    
    public function findMovie(string $imdb)
    {
        return $this->call("GET", "/find/".$imdb."?api_key=".tmdb_api3Auth."&external_source=imdb_id", '', 10);
    }
}
