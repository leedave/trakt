<?php

namespace Leedch\Trakt\Trakt\Users;

use Exception;
use Leedch\Mysql\Mysql;
use Leedch\Convert\Convert;
use Leedch\Trakt\Log;
use Leedch\Trakt\Trakt\Connector;
use Leedch\Trakt\Trakt\Users\Settings;

/**
 * Description of Collection
 *
 * @author leed
 */
class Rating extends Mysql
{
    protected $id;
    protected $title;
    protected $year;
    protected $rating;
    protected $slug;
    protected $traktId;
    protected $json;
    protected $type;
    protected $showDetail;
    protected $ratedAt;
    protected $updateDate;
    
    public function __construct()
    {
        parent::__construct();
        $this->connector = Connector::getInstance();
    }
    
    protected function getTableName() : string
    {
        return trakt_mysqlTableTraktRating;
    }


    /**
     * Simple Getter for json
     * @return string json
     */
    public function getJson()
    {
        return $this->json;
    }
    
    public function getByTraktId(string $type, int $traktId, string $episode = null) {
        $arrWhat = ['*'];
        $arrWhere = ["traktId" => $traktId, "type" => $type];
        if ($episode && $type === "show") {
            $arrWhere['showDetail'] = $episode;
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
    
    public function getRating() {
        return (int) $this->rating;
    }
       
    public function refreshData()
    {
        $arrData = $this->callData();
        foreach ($arrData as $data) {
            try {
                $rating = new Rating();
                $rating->importData($data);
            } catch (Exception $ex) {
                $log = new Log();
                $log->err($ex->getMessage());
            }
        }
        return "";
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
    
    protected function importData($data)
    {
        if (!isset($data['type'])) {
            return false;
        }
        $type = $data['type'];
        if (!$this->validateData($type, $data)) {
            throw new Exception('Invalid Rating Data '.json_encode($data, JSON_UNESCAPED_UNICODE));
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
        $this->rating = $data['rating'];
        $this->traktId = $data[$type]['ids']['trakt'];
        $this->slug = $data[$type]['ids']['slug'];
        $this->ratedAt = strftime("%Y-%m-%d %H:%M:%S", strtotime($data['rated_at']));
        $this->updateDate = strftime("%Y-%m-%d %H:%M:%S");
        $this->json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->type = $type;
        $this->showDetail = $this->getShowDetailFromData($data);
        $this->save();
    }
    
    /**
     * Returns S01 or S01E01 for Show Ratings
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
        $topLvl = ['movie', 'rated_at', 'rating'];
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
        $topLvl = ['show', 'rated_at', 'rating'];
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
        $topLvl = ['season', 'rated_at', 'rating'];
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
        $topLvl = ['episode', 'rated_at', 'rating'];
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
    
    
    /**
     * Fetch Data from API
     * @return array
     * @throws Exception
     */
    protected function callData()
    {
        $settings = new Settings();
        $settings->getUserSettings();
        $slug = $settings->getSlug();
        $response = $this->connector->call('GET', 'users/'.$slug.'/ratings/', "", 10);
        $responseCode = (int) $response->getStatusCode();
        if ($responseCode !== 200) {
            throw new Exception('Invalid response from Ratings Data');
        }
        $responseJson = $response->getBody()->getContents();
        $arrResponse = json_decode($responseJson, true);
        return $arrResponse;
    }
    
    public function setRatingByPost()
    {
        try {
            $this->validateRatingPost();
            $traktId = (int) $_POST['traktId'];
            $type = (string) $_POST['type'];
            $rating = (int) $_POST['rating'];
            //$datetime = (string) $_POST['datetime'];
            //$datetime = '2014-09-01T09:10:11.000Z';
            $response = $this->setRating($traktId, $type, $rating); //, $datetime);
            $this->refreshData();
            return $response;
        } catch (Exception $ex) {
            http_response_code(400);
            return $ex->getMessage();
        }
    }
    
    /**
     * Checks if the post params are set
     * @return boolean
     * @throws Exception
     */
    protected function validateRatingPost()
    {
        $arrRequired = [
            "traktId",
            "type",
            "rating",
        ];
        
        foreach ($arrRequired as $val) {
            if (!isset($_POST[$val])) {
                throw new Exception("Missing POST Param ".$val);
            }
        }
        if ($_POST['type'] === "episode") {
            $arrRequired = [
                "episode",
                "season",
            ];
            foreach ($arrRequired as $val) {
                if (!isset($_POST[$val])) {
                    throw new Exception("Missing POST Param ".$val);
                }
            }
        }
        
        return true;
    }
    
    protected function setRating(int $traktId, string $type, int $rating) //, string $datetime)
    {
        $payload = "";
    
        if ($type === "movie") {
            $payload = $this->makeMovieRatingPayload($traktId, $rating);
        }
        if ($type === "episode") {
            $season = (int) $_POST['season'];
            $episode = (int) $_POST['episode'];
            $payload = $this->makeEpisodeRatingPayload($traktId, $rating, $season, $episode);
        }
        
        if ($payload === "") {
            throw new Exception('Nothing to rate');
        }
        
        $response = $this->connector->call("POST", 'sync/ratings', $payload, 10);
        //$this->refreshData();
        $responseJson = $response->getBody()->getContents();
        return $responseJson;
    }
    
    protected function makeEpisodeRatingPayload(int $traktId, int $rating, int $season, int $episode)
    {
        $arrRating = [
            "shows" => [
                [
                    "ids" => [
                        "trakt" => $traktId,
                    ],
                    "seasons" => [
                        "number" => $season,
                        "episodes" => [
                            "number" => $episode,
                            "rating" => $rating,
                        ]
                    ]
                ]
            ]
        ];
        $json = Convert::json_encode($arrRating);
        return $json;
    }
    
    protected function makeMovieRatingPayload(int $traktId, int $rating)
    {
        $arrRating = [
            "movies" => [
                [
                    "rating" => $rating,
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
     * Returns shorthand episode names (key) with rating (S01E01)
     * @param int $traktId
     * @return array
     */
    public function getRatedEpisodes(int $traktId)
    {
        $arrRows = $this->getShowCollectionByTraktId($traktId);
        $arrResponse = [];
        foreach ($arrRows as $row) {
            $arrResponse[$row['showDetail']] = $row['rating'];
        }
        return $arrResponse;
    }
}
