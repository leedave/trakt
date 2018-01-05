<?php

namespace Leedch\Trakt\Renderer;

use Leedch\Html\Html5 as H;
use Leedch\View\View as V;
use Leedch\Convert\Convert;
use Leedch\Trakt\Trakt\Connector;
use Leedch\Trakt\Trakt\Users\Collection;
use Leedch\Trakt\Trakt\Users\History;
use Leedch\Trakt\Trakt\Users\Rating;

/**
 * Description of ShowSeason
 *
 * @author leed
 */
class ShowSeason
{
    public static function render(array $traktData)
    {
        $traktId = (int) $traktData['show']['ids']['trakt'];
        $slug = (string) $traktData['show']['ids']['slug'];
        
        $seasonDataJson = self::callSeasonData($slug);
        $seasonData = self::prepareSeasonData($seasonDataJson);
        
        $collection = new Collection();
        $episodesOwned = $collection->getCollectedEpisodes($traktId);
        $history = new History();
        $episodesWatched = $history->getWatchedEpisodes($traktId);
        $rating = new Rating();
        $episodesRated = $rating->getRatedEpisodes($traktId);
        return self::renderSeasons($seasonData, $traktId, $episodesOwned, $episodesWatched, $episodesRated);
    }
    
    protected static function renderSeasons(
        array $arrSeasons, 
        int $traktId, 
        array $episodesOwned = [], 
        array $episodesWatched = [], 
        array $episodesRated = []
    ){
        $return = "";
        foreach ($arrSeasons as $season => $arrEpisodes) {
            $attributes = [
                "class" => "seasonBlock",
            ];
            $episodeCount = count($arrEpisodes);
            $ownedCount = 0;
            $seasonName = "S".$season;
            if ($season < 10) {
                $seasonName = "S0".$season;
            }
            foreach ($episodesOwned as $owned) {
                if (substr($owned, 0, strlen($seasonName)) === $seasonName) {
                    $ownedCount++;
                }
            }
            $watchedCount = 0;
            foreach ($episodesWatched as $watched) {
                if (substr($watched, 0, strlen($seasonName)) === $seasonName) {
                    $watchedCount++;
                }
            }
            $ratedCount = 0;
            foreach ($episodesRated as $rated => $value) {
                if (substr($rated, 0, strlen($seasonName)) === $seasonName) {
                    $ratedCount++;
                }
            }
            $episodes = "";
            foreach ($arrEpisodes as $epId =>  $ep) {
                $episodes .= self::renderEpisode($season, $epId, $traktId, $ep, $episodesOwned, $episodesWatched, $episodesRated);
            }
            $ownedStr = H::span("Owned: ".$ownedCount."/".$episodeCount, ["class" => "owned"]);
            $watchedStr = H::span("Watched: ".$watchedCount."/".$episodeCount, ["class" => "watched"]);
            $ratedStr = H::span("Rated: ".$ratedCount."/".$episodeCount, ["class" => "rated"]);
            
            $controlDiv = H::div("", ["id" => "controls-loader-".$season, "class" => "controls-loader"]);
            
            $return .= H::div(
                            H::div(
                                H::label("Season ".$season." ".$ownedStr." ".$watchedStr." ".$ratedStr, "S".$season)
                            , ['class' => "seasonDiv"])
                            .H::div(
                                $episodes
                                .$controlDiv
                            , ['class' => "episodesDiv"])
                        , $attributes);
        }
        return $return;
    }
    
    protected static function renderEpisode(
        int $season, 
        int $epId, 
        int $traktId,
        array $arrEpisode, 
        array $episodesOwned = [], 
        array $episodesWatched = [], 
        array $episodesRated = []
    ){
        $divAttributes = [
            "class" => "episodeBlock",
            "title" => $arrEpisode['title'],
            "data-episode" => (int) $epId,
            "data-season" => (int) $season,
            "data-trakt" => (int) $traktId,
        ];
        $episodeName = self::makeEpisodeName($season, $epId);
        if (in_array($episodeName, $episodesOwned)) {
            $divAttributes['class'] .= " owned";
        }
        if (in_array($episodeName, $episodesWatched)) {
            $divAttributes['class'] .= " watched";
        }
        $rating = "";
        if (isset($episodesRated[$episodeName])) {
            $rating = " ".H::span($episodesRated[$episodeName], ["class" => "trakt-rating-number"]);
        }
        $return = H::div($episodeName.$rating, $divAttributes);
        return $return;
    }
    
    /**
     * Get all episodes from trakt
     * @param string $slug
     * @return string JSON
     * @throws Exception
     */
    protected static function callSeasonData(string $slug)
    {
        $connector = Connector::getInstance();
        $response = $connector->call('GET', 'shows/'.$slug.'/seasons?extended=episodes', "", 10);
        $responseCode = (int) $response->getStatusCode();
        if ($responseCode !== 200) {
            throw new Exception('Invalid response from UserData');
        }
        $responseJson = $response->getBody()->getContents();
        return $responseJson;
    
    }
    
    /**
     * Make an Array with all interesting data in it
     * @param string $responseJson
     * @return array
     */
    protected static function prepareSeasonData(string $responseJson)
    {
        $arrResponse = [];
        $arrTrakt = Convert::json_decode($responseJson);
        foreach ($arrTrakt as $season) {
            $arrResponse[$season['number']] = [];
            foreach($season['episodes'] as $episode) {
                $arrResponse[$season['number']][$episode['number']] = [
                    "title" => $episode['title'],
                    "number" => $episode['number'],
                    "trakt" => $episode['ids']['trakt'],
                ];
            }
        }
        return $arrResponse;
    }
    
    public static function renderEpisodeControls()
    {
        self::validateControlsRequest();
        $traktId = (int) $_POST['traktId'];
        $episode = (int) $_POST['episode'];
        $season = (int) $_POST['season'];
        
        $episodeName = self::makeEpisodeName($season, $episode);
        
        $rating = self::getRating($traktId, $episodeName);
        if (!$rating) {
            $controlRating = V::loadView(trakt_templateAdminEpisodeNotRated, ['TRAKTID' => $traktId, 'SEASON' => $season, 'EPISODE' => $episode]);
        } else {
            $controlRating = V::loadView(trakt_templateAdminMovieRated, ["RATING" => $rating]);
        }
        
        $watched = self::checkIfWatched($traktId, $episodeName);
        if ($watched) {
            $dateFormatted = strftime("%d.%m.%Y %H:%M", strtotime($watched));
            $controlWatched = V::loadView(trakt_templateAdminMovieWatched, ["DATE" => $dateFormatted]);
        } else {
            $arrWatched = [
                "DATE" => strftime("%Y-%m-%d"), 
                "TIME" => strftime("%H:%M:%S"), 
                "TRAKTID" => $traktId, 
                "SEASON" => $season, 
                "EPISODE" => $episode
            ];
            $controlWatched = V::loadView(trakt_templateAdminEpisodeNotWatched, $arrWatched);
        }
        
        $collected = self::checkIfInCollection($traktId, $season, $episode);
        if ($collected) {
            $controlCollection = V::loadView(trakt_templateAdminEpisodeCollected);
        } else {
            $controlCollection = V::loadView(trakt_templateAdminEpisodeNotCollected);
        }
        
        return H::tag("h3", $episodeName)
                .$controlRating
                .$controlWatched
                .$controlCollection;
    }
    
    protected function checkIfInCollection(int $traktId, int $season, int $episode)
    {
        $collection = new Collection();
        
        $collection->getByTraktId("shows", $traktId);
        $json = $collection->getJson();
        $arrData = Convert::json_decode($json);
        $seasons = $arrData['seasons'];
        foreach ($seasons as $arrSeason) {
            if ($arrSeason['number'] !== $season) {
                continue;
            }
            foreach ($arrSeason['episodes'] as $arrEpisode) {
                if ($arrEpisode['number'] !== $episode) {
                    continue;
                }
                return $arrEpisode['collected_at'];
            }
        }
        return null;
    }
    
    /**
     * Check in the Database if Movie was watched
     * @param int $traktId
     * @param string $episodeName
     * @return null|datetime
     */
    protected function checkIfWatched(int $traktId, string $episodeName)
    {
        $history = new History();
        
        $history->getByTraktId("show", $traktId, $episodeName);
        $watchedAt = $history->getWatchedAt();
        if (!$watchedAt) {
            return null;
        }
        return $watchedAt;
    }
    
    /**
     * Fetches the Rating to the Episode
     * @param int $traktId
     * @param string $episode
     * @return int|null
     */
    protected function getRating(int $traktId, string $episode) {
        $rating = new Rating();
        $rating->getByTraktId('show', $traktId, $episode);
        if ($rating->getRating() > 0) {
            return (int) $rating->getRating();
        }
        return null;
    }
    
    /**
     * Check if the required params for Episode Controls are available
     * @return boolean
     * @throws Exception
     */
    protected static function validateControlsRequest()
    {
        $expected = [
            "traktId",
            "episode",
            "season",
        ];
        
        foreach ($expected as $exp) {
            if (!isset($_POST[$exp]) || (int) $_POST[$exp] < 1) {
                throw new Exception('Invalid or missing param: '.$exp);
            }
        }
        return true;        
    }
    
    /**
     * Make the classic S01E01 Name 
     * @param int $season
     * @param int $episode
     * @return string
     */
    protected static function makeEpisodeName(int $season, int $episode)
    {
        if ($season < 10) {
            $season = "0".$season;
        }
        if ($episode < 10) {
            $episode = "0".$episode;
        }
        return "S".$season."E".$episode;
    }
}
