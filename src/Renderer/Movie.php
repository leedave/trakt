<?php

namespace Leedch\Trakt\Renderer;

use Leedch\Html\Html5 as H;
use Leedch\View\View as V;
use Leedch\Convert\Convert;
use Leedch\Trakt\Trakt\Connector;
use Leedch\Trakt\Trakt\Users\Rating;
use Leedch\Trakt\Trakt\Users\History;
use Leedch\Trakt\Trakt\Users\Collection;
use Leedch\Trakt\Tmdb\Connector as TmdbConnector;
use Leedch\Trakt\Tmdb\Image as TmdbImage;

/**
 * Description of Movie
 * @author leed
 */
class Movie
{
    /**
     * Renders the Movie Title in Tables
     * @param array $arrMovie
     * @return string   HTML Code
     */
    public static function render(array $arrMovie)
    {
        $traktId = (int) $arrMovie['movie']['ids']['trakt'];
        $rating = (int) self::getRating($traktId);
        $return = $arrMovie['movie']['title'];
        $year = (int) $arrMovie['movie']['year'];
        if ($year > 0) {
            $return .= " (".$year.")";
        }
        $watched = self::checkIfWatched($traktId);
        $collected = self::checkIfInCollection($traktId);
        
        $controls = [];
        if ($rating) {
            $controls[] = H::span($rating, ["class" => "trakt-rating-number"]);
        }
        
        if ($watched) {
            $controls[] = H::tag('i', '', ['class' => "fas fa-eye"]);
        } else {
            $controls[] = H::tag('i', '', ['class' => "far fa-eye-slash"]);
        }
        
        if ($collected) {
            $controls[] = H::tag('i', '', ['class' => "fas fa-file-video"]);
        } else {
            $controls[] = H::tag('i', '', ['class' => "fas fa-file-excel"]);
        }
        
        
        
        $return .= H::span(implode(" ", $controls), ["class" => "float-right control-details"]);
        
        $returnLink = H::a($return, ["href" => "javascript:tModal.openMovie(".$traktId.")", "class" => "movieRender"]);
        return $returnLink;
    }
    
    /**
     * Fetches the Rating to the Movie
     * @param int $traktId
     * @return int|null
     */
    protected function getRating(int $traktId) {
        $rating = new Rating();
        $rating->getByTraktId('movie', $traktId);
        if ($rating->getRating() > 0) {
            return (int) $rating->getRating();
        }
        return null;
    }
    
    public static function renderMovieDetail()
    {
        $traktId = (int) $_POST['traktId'];
        if ($traktId < 1) {
            return "Movie not found";
        }
        $jsonTrakt = self::callMovie($traktId);
        $traktMovieDataFull = Convert::json_decode($jsonTrakt);
        $traktMovieData = array_pop($traktMovieDataFull);
        
        $tmdb = TmdbConnector::getInstance();
        $coverImg = "";
        $tmdbRating = "";
        $description = "No description found";
        if (isset($traktMovieData['movie']['ids']['imdb'])) {
            $jsonTmdb = $tmdb->findMovie($traktMovieData['movie']['ids']['imdb']);
            $tmdbMovieDataFull = Convert::json_decode($jsonTmdb);
            $tmdbMovieData = array_pop($tmdbMovieDataFull['movie_results']);
            $tmdbImage = new TmdbImage();
            $coverImgSrc = tmdb_imgMovie_uri.$tmdbMovieData['poster_path'];
            $coverImgCache = $tmdbImage->getImageWebUrl($coverImgSrc);
            if ($coverImgCache) {
                $coverImg = H::img(['src' => $coverImgCache, "alt" => "Movie Poster"]);
            }
            if (isset($tmdbMovieData['overview'])) { 
                $description = $tmdbMovieData['overview'];
            }
            if (isset($tmdbMovieData['vote_average'])) {
                $tmdbRating = (float) $tmdbMovieData['vote_average'];
            }
        }
        //return print_r($tmdbMovieData, true);
        $arrView = [
            "TITLE" => $traktMovieData['movie']['title'],
            "YEAR" => $traktMovieData['movie']['year'],
            "DESCRIPTION" => $description,
            "COVERIMG" => $coverImg,
            "IMDBRATING" => "IMDB RATING",
            "TMDBRATING" => $tmdbRating,
            "TRAKTCONTROLS" => self::drawMovieControls($traktMovieData),
        ];
        $response = V::loadView(trakt_templateAdminMovieDetail, $arrView);
        return $response;
    }
    
    
    protected function drawMovieControls(array $data)
    {
        $traktId = (int) $data['movie']['ids']['trakt'];
        $rating = self::getRating($traktId);
        if (!$rating) {
            $controlRating = V::loadView(trakt_templateAdminMovieNotRated, ['TRAKTID' => $traktId]);
        } else {
            $controlRating = V::loadView(trakt_templateAdminMovieRated, ["RATING" => $rating]);
        }
        
        $watched = self::checkIfWatched($data['movie']['ids']['trakt']);
        if ($watched) {
            $dateFormatted = strftime("%d.%m.%Y %H:%M", strtotime($watched));
            $controlWatched = V::loadView(trakt_templateAdminMovieWatched, ["DATE" => $dateFormatted]);
        } else {
            $controlWatched = V::loadView(trakt_templateAdminMovieNotWatched, ["DATE" => strftime("%Y-%m-%d"), "TIME" => strftime("%H:%M:%S"), "TRAKTID" => $traktId]);
        }
        
        $collected = self::checkIfInCollection($traktId);
        if ($collected) {
            $controlCollection = V::loadView(trakt_templateAdminMovieCollected);
        } else {
            $controlCollection = V::loadView(trakt_templateAdminMovieNotCollected);
        }
        
        return $controlRating
                .$controlWatched
                .$controlCollection;
    }
    
    /**
     * Check in the Database if Movie was watched
     * @param int $traktId
     * @return null|datetime
     */
    protected function checkIfWatched(int $traktId)
    {
        $history = new History();
        
        $history->getByTraktId("movie", $traktId);
        $watchedAt = $history->getWatchedAt();
        if (!$watchedAt) {
            return null;
        }
        return $watchedAt;
    }
    
    protected function checkIfInCollection(int $traktId)
    {
        $collection = new Collection();
        
        $collection->getByTraktId("movies", $traktId);
        $collectedAt = $collection->getCollectedAt();
        if (!$collectedAt) {
            return null;
        }
        return $collectedAt;
    }
    
    
    /**
     * Search for Movie at Trakt API
     * @param int $traktId
     * @return string json
     * @throws Exception
     */
    protected function callMovie(int $traktId)
    {
        $connector = Connector::getInstance();
        $response = $connector->call('GET', 'search/trakt/'.$traktId.'?type=movie', "", 10);
        $responseCode = (int) $response->getStatusCode();
        if ($responseCode !== 200) {
            throw new Exception('Invalid response from UserData');
        }
        $responseJson = $response->getBody()->getContents();
        return $responseJson;
    }
}
