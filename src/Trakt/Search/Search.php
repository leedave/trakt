<?php

namespace Leedch\Trakt\Trakt\Search;

use Leedch\Convert\Convert;
use Leedch\Trakt\Trakt\Connector;
use Leedch\Trakt\Renderer\Movie;
use Leedch\Trakt\Renderer\Show;

/**
 * Description of Search
 * @author leed
 */
class Search
{
    protected $connector;
    
    public function __construct()
    {
        //parent::__construct();
        $this->connector = Connector::getInstance();
    }
    
    /**
     * Fetch Data from API
     * @return array
     * @throws Exception
     */
    protected function callData()
    {
        $type = $this->getSearchType();
        $searchString = $this->getSearchQuery();
        $response = $this->connector->call('GET', 'search/'.$type.'?query='.$searchString.'&limit=100', "", 10);
        $responseCode = (int) $response->getStatusCode();
        if ($responseCode !== 200) {
            throw new Exception('Invalid response from Search Data');
        }
        $responseJson = $response->getBody()->getContents();
        return $responseJson;
    }
    
    public function performSearch()
    {
        if ($this->getSearchQuery() === "") {
            return "[]";
        }
        $jsonResponse = $this->callData();
        $jsonSearchResult = $this->formatSearchResults($jsonResponse);
        return $jsonSearchResult;
        /*return "Search Query: ".$this->getSearchQuery()."<br />\n"
                . "Search Type: ".$this->getSearchType()."<br />\n"
                . "Response: ".$jsonSearchResult;*/
    }
    
    /**
     * Make JSON Search Results
     * @param string $json
     * @return string
     */
    protected function formatSearchResults(string $json)
    {
        $arrResults = Convert::json_decode($json);
        $arrReturn = [];
        foreach ($arrResults as $result) {
            $arrReturn[] = $this->formatSingleResult($result);
        }
        $jsonReturn = Convert::json_encode($arrReturn);
        return $jsonReturn;
    }
    
    /**
     * Creates an array needed to build up the full response array
     * @param array $result
     * @return array
     */
    protected function formatSingleResult(array $result)
    {
        $type = (string) $result['type'];
        if ($type === "movie") {
            $title = (string) Movie::render($result);
        } else {
            $title = (string) Show::render($result);
        }
        $year = (int) $result[$type]['year'];
        $traktId = (int) $result[$type]['ids']['trakt'];
        
        $arrJson = [
            "type" => $type,
            "title" => $title,
            "year" => $year,
            "traktId" => $traktId,
        ];
        return $arrJson;
    }
    
    /**
     * Fetch the posted search string and slugify it
     * @return string
     */
    protected function getSearchQuery()
    {
        $searchQuery = "";
        if (isset($_POST['searchName'])) {
            $searchQuery = Convert::slugify($_POST['searchName']);
        }
        return (string) str_replace("-", "+", $searchQuery);
    }
    
    /**
     * get the posted searchtype and slugify
     * @return string
     */
    protected function getSearchType()
    {
        $searchType = "";
        if (isset($_POST['searchType'])) {
            $searchType = Convert::slugify($_POST['searchType']);
        }
        return (string) $searchType;
    }
}
