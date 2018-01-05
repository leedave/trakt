<?php

namespace Leedch\Trakt\Trakt\Users;

use Exception;
use Leedch\Convert\Convert;
use Leedch\Mysql\Mysql;
use Leedch\Trakt\Log;
use Leedch\Trakt\Trakt\Connector;
use Leedch\Trakt\Trakt\Users\Settings;

/**
 * Description of Collection
 *
 * @author leed
 */
class Collection extends Mysql
{
    protected $id;
    protected $title;
    protected $slug;
    protected $imdb;
    protected $tmdb;
    protected $tvdb;
    protected $tvrage;
    protected $traktId;
    protected $year;
    protected $json;
    protected $type;
    protected $collectedAt;
    protected $updateDate;
    
    public function __construct()
    {
        parent::__construct();
        $this->connector = Connector::getInstance();
    }
    
    protected function getTableName() : string
    {
        return trakt_mysqlTableTraktCollection;
    }
    
    public function getByTraktId(string $type, int $traktId, string $showDetail = null) {
        $arrWhat = ['*'];
        $arrWhere = ["traktId" => $traktId, "type" => $type];
        if ($showDetail) {
            $arrWhere['showDetail'] = $showDetail;
        }
        $arrOrder = [];
        $arrLimit = [0,1];
        $rows = $this->loadByPrepStmt($arrWhat, $arrWhere, $arrOrder, $arrLimit);
        $row = array_pop($rows);
        if ($row) {
           $this->loadWithData($row); 
        } 
        
        return $this;
    }
    
    /**
     * Simple Getter for collectedAt
     * @return datetime
     */
    public function getCollectedAt()
    {
        return $this->collectedAt;
    }
    
    /**
     * Simple getter for $this->json
     * @return string json
     */
    public function getJson()
    {
        return $this->json;
    }
    
    public function renderCollection()
    {
        return $this->callCollection();
    }
    
    /**
     * Update the DB by getting new data from Trakt API
     * @param string $type  datatype
     * @return string   empty String
     */
    public function refreshCollection(string $type)
    {
        if ($type === "movies") {
            $arrData = $this->callCollection($type);
            foreach ($arrData as $data) {
                try {
                    $movie = new Collection();
                    $movie->importMovie($data);
                } catch (Exception $ex) {
                    $log = new Log();
                    $log->err($ex->getMessage());
                }
            }
        } elseif ($type === "shows") {
            $arrData = $this->callCollection($type);
            foreach ($arrData as $data) {
                try {
                    $show = new Collection();
                    $show->importShow($data);
                } catch (Exception $ex) {
                    $log = new Log();
                    $log->err($ex->getMessage());
                }
            }
        }
        return "";
    }
    
    protected function dataExists(string $type, array $data)
    {
        $traktId = (int) $data['ids']['trakt'];
        $arrWhere = [
            'traktId' => $traktId,
            'type' => $type,
        ];
        $rows = $this->loadByPrepStmt(['*'], $arrWhere, ['`id` ASC'], [0,1]);
        if (count($rows) > 0) {
            $row = array_pop($rows);
            return (int) $row['id'];
        }
        return false;
    }
    
    /**
     * Save a Show to DB
     * @param array $data
     * @return null
     * @throws Exception
     */
    protected function importShow($data)
    {
        if (!$this->validateData("shows", $data)) {
            throw new Exception('Invalid Show Data '.json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        $exists = $this->dataExists("shows", $data['show']);
        if ($exists) {
            //dont just skip, update the episode list
            $this->load($exists);
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($json !== $this->json) {
                $this->json = $json;
                $this->save();
            }
            return;
        }
        $this->title = $data['show']['title'];
        $this->year = $data['show']['year'];
        $this->traktId = $data['show']['ids']['trakt'];
        $this->slug = $data['show']['ids']['slug'];
        $this->imdb = $data['show']['ids']['imdb'];
        $this->tmdb = $data['show']['ids']['tmdb'];
        $this->tvdb = $data['show']['ids']['tvdb'];
        $this->tvrage = $data['show']['ids']['tvrage'];
        $this->json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->collectedAt = strftime("%Y-%m-%d %H:%M:%S", strtotime($data['last_collected_at']));
        $this->updateDate = strftime("%Y-%m-%d %H:%M:%S");
        $this->type = "shows";
        $this->save();
    }
    
    /**
     * Save a movie to DB
     * @param array $data
     * @return null
     * @throws Exception
     */
    protected function importMovie($data)
    {
        if (!$this->validateData("movies", $data)) {
            throw new Exception('Invalid Movie Data '.json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        if ($this->dataExists("movies", $data['movie'])) {
            return;
        }
        $this->title = $data['movie']['title'];
        $this->year = $data['movie']['year'];
        $this->traktId = $data['movie']['ids']['trakt'];
        $this->slug = $data['movie']['ids']['slug'];
        $this->imdb = $data['movie']['ids']['imdb'];
        $this->tmdb = $data['movie']['ids']['tmdb'];
        $this->collectedAt = strftime("%Y-%m-%d %H:%M:%S", strtotime($data['collected_at']));
        $this->updateDate = strftime("%Y-%m-%d %H:%M:%S");
        $this->json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->type = "movies";
        $this->save();
    }
    
    /**
     * Just validates Response from API
     * @param string $type
     * @param array $data
     * @return boolean
     */
    protected function validateData(string $type, array $data)
    {
        if ($type === "movies") {
            return $this->validateMovie($data);
        } elseif ($type === "shows") {
            return $this->validateShow($data);
        }
        
        return false;
    }
    
    /**
     * Checks the data of a show object
     * @param array $data
     * @return boolean
     */
    protected function validateShow(array $data)
    {
        $topLvl = ['show', 'last_collected_at'];
        foreach ($topLvl as $check) {
            if (!isset($data[$check])) {
                return false;
            }
        }
        
        $showLvl = [
            'title',
            'year',
            'ids',
        ];
        
        foreach ($showLvl as $check) {
            if (!isset($data['show'][$check])) {
                return false;
            }
        }
        
        $idsLvl = [
            'trakt',
            'slug',
        ];
        
        foreach ($idsLvl as $check) {
            if (!isset($data['show']['ids'][$check])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Checks for expected movie fields
     * @param array $data
     * @return boolean
     */
    protected function validateMovie(array $data)
    {
        $topLvl = ['movie', 'collected_at'];
        foreach ($topLvl as $check) {
            if (!isset($data[$check])) {
                return false;
            }
        }
        
        $movieLvl = [
            'title',
            'year',
            'ids',
        ];
        
        foreach ($movieLvl as $check) {
            if (!isset($data['movie'][$check])) {
                return false;
            }
        }
        
        $idsLvl = [
            'trakt',
            'slug',
        ];
        
        foreach ($idsLvl as $check) {
            if (!isset($data['movie']['ids'][$check])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Calls the REST API for new data
     * @param string $type
     * @return array decoded json
     * @throws Exception
     */
    protected function callCollection($type)
    {
        $settings = new Settings();
        $settings->getUserSettings();
        $slug = $settings->getSlug();
        //$response = $this->connector->call('GET', '/users/'.$slug.'/collection/'.$type.'?extended=metadata', "", 10);
        $response = $this->connector->call('GET', 'users/'.$slug.'/collection/'.$type, "", 10);
        $responseCode = (int) $response->getStatusCode();
        if ($responseCode !== 200) {
            throw new Exception('Invalid response from UserData');
        }
        $responseJson = $response->getBody()->getContents();
        $arrResponse = Convert::json_decode($responseJson);
        return $arrResponse;
    }
    
    /**
     * Fetch a list of Episodes
     * @param int $showTraktId
     * @return array
     */
    public function getCollectedEpisodes(int $showTraktId)
    {
        $this->getByTraktId("shows", $showTraktId);
        if ($this->id < 1) {
            return [];
        }
        $arrData = Convert::json_decode($this->json);
        $episodes = [];
        foreach ($arrData['seasons'] as $season) {
            $number = $season['number'];
            if ($number < 10) {
                $number = "0".$number;
            }
            $prefix = "S".$number;
            foreach ($season['episodes'] as $episode) {
                $epNumber = $episode['number'];
                if ($epNumber < 10) {
                    $epNumber = "0".$epNumber;
                }
                $episodes[] = $prefix."E".$epNumber;
            }
        }
        return $episodes;
    }
}
