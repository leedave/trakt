<?php

namespace Leedch\Trakt\Renderer;

use Leedch\Html\Html5 as H;
use Leedch\View\View as V;
use Leedch\Convert\Convert;
use Leedch\Trakt\Trakt\Connector;
use Leedch\Trakt\Tvdb\Connector as TvdbConnector;
use Leedch\Trakt\Tvdb\Image as TvdbImage;
use Leedch\Trakt\Renderer\ShowSeason;

/**
 * Description of Show
 *
 * @author leed
 */
class Show
{
    public static function render(array $arrShow)
    {
        $traktId = (int) $arrShow['show']['ids']['trakt'];
        $return = (string) $arrShow['show']['title'];
        $returnLink = H::a($return, ["href" => "javascript:tModal.openShow(".$traktId.")", "class" => "showRender"]);
        return $returnLink;
    }
    
    public static function renderShowDetail()
    {
        $traktId = (int) $_POST['traktId'];
        if ($traktId < 1) {
            return "Show not found";
        }
        $jsonTrakt = self::callShow($traktId);
        $traktShowDataFull = Convert::json_decode($jsonTrakt);
        $traktShowData = array_pop($traktShowDataFull);
        
        $tvdb = TvdbConnector::getInstance();
        $coverImg = "";
        $tvdbRating = "";
        $description = "No description found";
        if (isset($traktShowData['show']['ids']['tvdb'])) {
            $responseTvdb = $tvdb->findShow($traktShowData['show']['ids']['tvdb']);
            $jsonTvdb = $responseTvdb->getBody()->getContents();
            $tvdbDataFull = Convert::json_decode($jsonTvdb);
            $tvdbData = $tvdbDataFull['data'];
            //print_r($jsonTvdb);
            $tvdbImage = new TvdbImage();
            $coverImgSrc = tvdb_imgShow_uri.$tvdbData['banner'];
            $coverImgCache = $tvdbImage->getImageWebUrl($coverImgSrc);
            if ($coverImgCache) {
                $coverImg = H::img(['src' => $coverImgCache, "alt" => "Movie Poster"]);
            }
            if (isset($tvdbData['overview'])) { 
                $description = $tvdbData['overview'];
            }
            if (isset($tvdbData['siteRating'])) {
                $tvdbRating = (float) $tvdbData['siteRating'];
            }
        }
        //return print_r($tmdbMovieData, true);
        $seasons = ShowSeason::render($traktShowData);
        $arrView = [
            "TITLE" => $traktShowData['show']['title'],
            "YEAR" => $traktShowData['show']['year'],
            "DESCRIPTION" => $description,
            "COVERIMG" => $coverImg,
            //"IMDBRATING" => "IMDB RATING",
            "TVDBRATING" => $tvdbRating,
            "SEASONS" => $seasons,
            //"TRAKTCONTROLS" => self::drawMovieControls($traktMovieData),
        ];
        $response = V::loadView(trakt_templateAdminShowDetail, $arrView);
        
        return $response;
    }
    
    /**
     * Search for Show at Trakt API
     * @param int $traktId
     * @return string json
     * @throws Exception
     */
    protected function callShow(int $traktId)
    {
        $connector = Connector::getInstance();
        $response = $connector->call('GET', 'search/trakt/'.$traktId.'?type=show&extended=metadata', "", 10);
        $responseCode = (int) $response->getStatusCode();
        if ($responseCode !== 200) {
            throw new Exception('Invalid response from UserData');
        }
        $responseJson = $response->getBody()->getContents();
        return $responseJson;
    }
}
