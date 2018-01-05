<?php

namespace Leedch\Trakt\Trakt\Users;

use Exception;
use Leedch\Validator\Validator;
use Leedch\Convert\Convert;
use Leedch\Mysql\Mysql;
use Leedch\Trakt\Log;
use Leedch\Trakt\Trakt\Connector;
use Leedch\Trakt\Trakt\Users\Settings;

/**
 * Description of Settings
 *
 * @author leed
 */
class History extends Mysql
{
    protected $connector;
    
    protected $id;
    protected $title;
    protected $year;
    protected $slug;
    protected $traktId;
    protected $json;
    protected $type;
    protected $showDetail;
    protected $watchedAt;
    protected $updateDate;
    
    public function __construct()
    {
        parent::__construct();
        $this->connector = Connector::getInstance();
    }
    
    protected function getTableName() : string
    {
        return trakt_mysqlTableTraktHistory;
    }
    
    /**
     * Simple Getter
     * @return string json
     */
    public function getJson()
    {
        return $this->json;
    }
    
    public function getByTraktId(string $type, int $traktId, string $episodeName = null) {
        $arrWhat = ['*'];
        $arrWhere = ["traktId" => $traktId, "type" => $type];
        if ($type === "show") {
            $arrWhere['showDetail'] = $episodeName;
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
    
    protected function getShowCollectionByTraktId(int $traktId)
    {
        $arrWhat = ['*'];
        $arrWhere = ["traktId" => $traktId, "type" => "show"];
        $arrOrder = [];
        $arrLimit = [];
        $rows = $this->loadByPrepStmt($arrWhat, $arrWhere, $arrOrder, $arrLimit);
        return $rows;
    }


    /**
     * Returns the original JSON
     * @return string
     */
    public function getData()
    {
        return json_decode($this->json, true);
    }
    
    public function getWatchedAt()
    {
        return $this->watchedAt;
    }
    
    /**
     * Fetch Data from API
     * @param bool $recent  only get the latest 30 entries
     * @return array
     * @throws Exception
     */
    protected function callData(bool $recent = false)
    {
        $settings = new Settings();
        $settings->getUserSettings();
        $slug = $settings->getSlug();
        //$today = strftime("%Y-%m-%d");
        //$params = '?start_at=2000-01-01T00%3A00%3A00.000Z&end_at='.$today.'T00%3A00%3A00.000Z'
        $params = '?limit=100000';
        if ($recent) {
            $params = '?limit=30';
        }
        $response = $this->connector->call('GET', 'users/'.$slug.'/history/'.$params, "", 10);
        $responseCode = (int) $response->getStatusCode();
        if ($responseCode !== 200) {
            throw new Exception('Invalid response from Watched Data');
        }
        $responseJson = $response->getBody()->getContents();
        $arrResponse = json_decode($responseJson, true);
        return $arrResponse;
    }
    
    /**
     * Fetch Data from Trakt
     * @param bool $recent  set to true to only get the latest updates
     * @return string
     */
    public function refreshData(bool $recent = false)
    {
        $arrData = $this->callData($recent);
        foreach ($arrData as $data) {
            try {
                $history = new History();
                $history->importData($data);
            } catch (Exception $ex) {
                $log = new Log();
                $log->err($ex->getMessage());
            }
        }
        return "";
    }
    
    protected function importData($data)
    {
        if (!isset($data['type'])) {
            return false;
        }
        $type = $data['type'];
        if (!$this->validateData($type, $data)) {
            throw new Exception('Invalid History Data '.json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        if ($this->dataExists($data)) {
            return;
        }
        $type = "show";
        if ($data['type'] === "movie") {
            $type = "movie";
        }
        $this->title = $data[$type]['title'];
        $this->year = $data[$type]['year'];
        $this->traktId = $data[$type]['ids']['trakt'];
        $this->slug = $data[$type]['ids']['slug'];
        $this->watchedAt = strftime("%Y-%m-%d %H:%M:%S", strtotime($data['watched_at']));
        $this->updateDate = strftime("%Y-%m-%d %H:%M:%S");
        $this->json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->type = $type;
        $this->showDetail = $this->getShowDetailFromData($data);
        $this->save();
    }
    
    /**
     * Check if already cached in DB
     * @param array $data
     * @return boolean
     */
    protected function dataExists(array $data)
    {
        $type = $data['type'];
        $typeDb = "show";
        if ($data['type'] === "movie") {
            $typeDb = "movie";
        }
        $traktId = (int) $data[$typeDb]['ids']['trakt'];
        $arrWhere = [
            'traktId' => $traktId,
            'type' => $typeDb,
            'showDetail' => $this->getShowDetailFromData($data),
        ];
        $rows = $this->loadByPrepStmt(['*'], $arrWhere, ['`id` ASC'], [0,1]);
        if (count($rows) > 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns S01 or S01E01 for Show 
     * @param array $data
     * @return string
     */
    protected function getShowDetailFromData(array $data)
    {
        $type = $data['type'];
        if ($type === "show") {
            return "All";
        } elseif ($type === "season") {
            $season = (int) $data['season']['number'];
            if ($season < 10) {
                return "S0".$season;
            }
            return "S".$season;
        } elseif ($type === "episode") {
            $season = (int) $data['episode']['season'];
            $episode = (int) $data['episode']['number'];
            $response = "";
            if ($season < 10) {
                $response .= "S0".$season;
            } else {
                $response .= "S".$season;
            }
            if ($episode < 10) {
                $response .= "E0".$episode;
            } else {
                $response .= "E".$episode;
            }
            return $response;
        }
        return "";
    }
    
    /**
     * Just validates Response from API
     * @param string $type
     * @param array $data
     * @return boolean
     */
    protected function validateData(string $type, array $data)
    {
        if ($type === "movie") {
            return $this->validateMovie($data);
        } elseif ($type === "show") {
            return $this->validateShow($data);
        } elseif ($type === "season") {
            return $this->validateSeason($data);
        } elseif ($type === "episode") {
            return $this->validateEpisode($data);
        }
        
        return false;
    }
    
    /**
     * Checks for expected movie fields
     * @param array $data
     * @return boolean
     */
    protected function validateMovie(array $data)
    {
        $topLvl = ['movie', 'watched_at', 'type'];
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
     * Checks for expected show fields
     * @param array $data
     * @return boolean
     */
    protected function validateShow(array $data)
    {
        $topLvl = ['show', 'watched_at', 'type'];
        foreach ($topLvl as $check) {
            if (!isset($data[$check])) {
                return false;
            }
        }
        
        $subLvl = [
            'title',
            'year',
            'ids',
        ];
        
        foreach ($subLvl as $check) {
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
     * Checks for expected show fields
     * @param array $data
     * @return boolean
     */
    protected function validateSeason(array $data)
    {
        $topLvl = ['season', 'watched_at', 'type'];
        foreach ($topLvl as $check) {
            if (!isset($data[$check])) {
                return false;
            }
        }
        
        $subLvl = [
            'number',
            'ids',
        ];
        
        foreach ($subLvl as $check) {
            if (!isset($data['season'][$check])) {
                return false;
            }
        }
        $subLvl2 = [
            'title',
            'year',
            'ids',
        ];
        
        foreach ($subLvl2 as $check) {
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
     * Checks for expected show fields
     * @param array $data
     * @return boolean
     */
    protected function validateEpisode(array $data)
    {
        $topLvl = ['episode', 'watched_at', 'type'];
        foreach ($topLvl as $check) {
            if (!isset($data[$check])) {
                return false;
            }
        }
        
        $subLvl = [
            'season',
            'number',
            'ids',
        ];
        
        foreach ($subLvl as $check) {
            if (!isset($data['episode'][$check])) {
                return false;
            }
        }
        $subLvl2 = [
            'title',
            'year',
            'ids',
        ];
        
        foreach ($subLvl2 as $check) {
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
    
    public function setMovieWatchedByPost()
    {
        try {
            $this->validateMovieWatchedPost();
            $traktId = (int) $_POST['traktId'];
            $date = (string) $_POST['date'];
            $time = (string) $_POST['time'];
            $response = $this->setMovieWatched($traktId, $date, $time);
            $this->refreshData(true);
            return $response;
        } catch (Exception $ex) {
            http_response_code(400);
            return $ex->getMessage();
        }
    }
    
    public function setEpisodeWatchedByPost()
    {
        try {
            $this->validateEpisodeWatchedPost();
            $traktId = (int) $_POST['traktId'];
            $season = (int) $_POST['season'];
            $episode = (int) $_POST['episode'];
            $date = (string) $_POST['date'];
            $time = (string) $_POST['time'];
            $response = $this->setEpisodeWatched($traktId, $date, $time, $season, $episode);
            $this->refreshData(true);
            return $response;
        } catch (Exception $ex) {
            http_response_code(400);
            return $ex->getMessage();
        }
    }
    
    protected function setEpisodeWatched(
        int $traktId, 
        string $date, 
        string $time, 
        int $season, 
        int $episode
    ){
        $payload = $this->makeEpisodeWatchedPayload($traktId, $date, $time, $season, $episode);
        return $this->setAsWatched($payload);
    }
    
    protected function setMovieWatched(int $traktId, string $date, string $time)
    {
        $payload = $this->makeMovieWatchedPayload($traktId, $date, $time);
        return $this->setAsWatched($payload);
    }
    
    protected function setAsWatched($payload)
    {
        if ($payload === "") {
            throw new Exception('Nothing to set as watched');
        }
        
        $response = $this->connector->call("POST", 'sync/history', $payload, 10);
        $responseJson = $response->getBody()->getContents();
        return $responseJson;
    }
    
    protected function makeEpisodeWatchedPayload(
        int $traktId, 
        string $date, 
        string $time, 
        int $season, 
        int $episode
    ){
        $datetimeTrue = Validator::validateDate($date." ".$time);
        if (!$datetimeTrue) {
            throw new Exception('Invalid Datetime');
        }
        $arrRating = [
            "shows" => [
                [
                    "ids" => [
                        "trakt" => $traktId,
                    ],
                    "seasons" => [
                        [
                            "number" => $season,
                            "episodes" => [
                                [
                                    "number" => $episode,
                                    "watched_at" => $date." ".$time,                
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];
        $json = Convert::json_encode($arrRating);
        return $json;
    }
    protected function makeMovieWatchedPayload(int $traktId, string $date, string $time)
    {
        $datetimeTrue = Validator::validateDate($date." ".$time);
        if (!$datetimeTrue) {
            throw new Exception('Invalid Datetime');
        }
        $arrRating = [
            "movies" => [
                [
                    "watched_at" => $date." ".$time,
                    "ids" => [
                        "trakt" => $traktId,
                    ],
                ],
            ],
        ];
        $json = Convert::json_encode($arrRating);
        return $json;
    }
    
    /**
     * Checks the Posted Fields
     * @return boolean
     * @throws Exception
     */
    protected function validateMovieWatchedPost()
    {
        $arrRequired = [
            "traktId",
            "date",
            "time",
        ];
        
        foreach ($arrRequired as $val) {
            if (!isset($_POST[$val])) {
                throw new Exception("Missing POST Param ".$val);
            }
        }
        return true;
    }
    
    /**
     * Checks the Posted Fields
     * @return boolean
     * @throws Exception
     */
    protected function validateEpisodeWatchedPost()
    {
        $arrRequired = [
            "traktId",
            "date",
            "time",
            "season",
            "episode",
        ];
        
        foreach ($arrRequired as $val) {
            if (!isset($_POST[$val])) {
                throw new Exception("Missing POST Param ".$val);
            }
        }
        return true;
    }
    
    /**
     * Returns shorthand episode names (S01E01)
     * @param int $traktId
     * @return array
     */
    public function getWatchedEpisodes(int $traktId)
    {
        $arrRows = $this->getShowCollectionByTraktId($traktId);
        $arrResponse = [];
        foreach ($arrRows as $row) {
            $arrResponse[] = $row['showDetail'];
        }
        return $arrResponse;
    }
}
