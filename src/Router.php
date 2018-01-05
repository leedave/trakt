<?php

namespace Leedch\Trakt;

use Leedch\Router\Router as parentRouter;
use Leedch\Website\Routers\RouterInterface;
use Leedch\Trakt\Admin\Dashboard;
use Leedch\Trakt\Trakt\Users\Collection;
use Leedch\Trakt\Admin\Collection as AdminCollection;
use Leedch\Trakt\Trakt\Users\History;
use Leedch\Trakt\Admin\History as AdminHistory;
use Leedch\Trakt\Trakt\Users\Rating;
use Leedch\Trakt\Admin\Rating as AdminRating;
use Leedch\Trakt\Trakt\Search\Search;
use Leedch\Trakt\Renderer\Movie as MovieRenderer;
use Leedch\Trakt\Renderer\Show as ShowRenderer;
use Leedch\Trakt\Renderer\ShowSeason as ShowSeasonRenderer;

/**
 * Trakt router 
 *
 * @author leed
 */
class Router implements RouterInterface
{
    protected $encoding = "utf-8";
    protected $language = "en";
    protected $headers;
    public $embed = false;
    
    public function route(parentRouter $r)
    {
        $response = false;
        if ($r->requestPath === "/trakt" && $r->requestMethod === "GET") {
            $dashboard = new Dashboard();
            $content = $dashboard->renderOverview();
            $this->embed = true;
            $response = $content;
        } elseif ($r->requestPath === "/trakt/collection/refresh/movies/" && $r->requestMethod === "GET") {
            $collection = new Collection();
            $content = $collection->refreshCollection("movies");
            http_response_code(302);
            header("Location: /trakt/collection/movies/");
            return;
        } elseif ($r->requestPath === "/trakt/collection/refresh/shows/" && $r->requestMethod === "GET") {
            $collection = new Collection();
            $content = $collection->refreshCollection("shows");
            http_response_code(302);
            header("Location: /trakt/collection/shows/");
            return;
        } elseif ($r->requestPath === "/trakt/collection/load/movies/" && $r->requestMethod === "POST") {
            header("Content-type:application/json");
            $collection = new AdminCollection();
            $response = $collection->jsonMoviesRows();
            return $response;
        } elseif ($r->requestPath === "/trakt/collection/load/shows/" && $r->requestMethod === "POST") {
            header("Content-type:application/json");
            $collection = new AdminCollection();
            $response = $collection->jsonShowRows();
            return $response;
        } elseif ($r->requestPath === "/trakt/collection/movies/" && $r->requestMethod === "GET") {
            $collection = new AdminCollection();
            $content = $collection->renderMoviesCollectionTable();
            $this->embed = true;
            $response = $content;
        } elseif ($r->requestPath === "/trakt/collection/shows/" && $r->requestMethod === "GET") {
            $collection = new AdminCollection();
            $content = $collection->renderShowsCollectionTable();
            $this->embed = true;
            $response = $content;
        } elseif ($r->requestPath === "/trakt/ratings/" && $r->requestMethod === "GET") {
            $rating = new AdminRating();
            $content = $rating->renderTable();
            $this->embed = true;
            $response = $content;
        } elseif ($r->requestPath === "/trakt/ratings/refresh/" && $r->requestMethod === "GET") {
            $rating = new Rating();
            $content = $rating->refreshData();
            http_response_code(302);
            header("Location: /trakt/ratings/");
            return;
        } elseif ($r->requestPath === "/trakt/ratings/load/" && $r->requestMethod === "POST") {
            header("Content-type:application/json");
            $rating = new AdminRating();
            $response = $rating->jsonRows();
            return $response;
        } elseif ($r->requestPath === "/trakt/watched/movies/" && $r->requestMethod === "GET") {
            $history = new AdminHistory();
            $content = $history->renderMovieTable();
            $this->embed = true;
            $response = $content;
        } elseif ($r->requestPath === "/trakt/watched/shows/" && $r->requestMethod === "GET") {
            $history = new AdminHistory();
            $content = $history->renderShowsTable();
            $this->embed = true;
            $response = $content;
        } elseif ($r->requestPath === '/trakt/watched/refresh/' && $r->requestMethod === "GET") {
            $history = new History();
            $content = $history->refreshData();
            http_response_code(302);
            header("Location: /trakt/watched/movies/");
            return;
        } elseif ($r->requestPath === '/trakt/watched/load/movies/' && $r->requestMethod === 'POST') {
            header("Content-type:application/json");
            $history = new AdminHistory();
            $response = $history->jsonRowsMovies();
            return $response;
        } elseif ($r->requestPath === '/trakt/watched/load/shows/' && $r->requestMethod === 'POST') {
            header("Content-type:application/json");
            $history = new AdminHistory();
            $response = $history->jsonRowsShows();
            return $response;
        } elseif ($r->requestPath === '/trakt/search/' && $r->requestMethod === 'POST') {
            header("Content-type:application/json");
            $search = new Search();
            $response = $search->performSearch();
            return $response;
        } elseif ($r->requestPath === '/trakt/searchByTraktId/movie/' && $r->requestMethod === 'POST') {
            $response = MovieRenderer::renderMovieDetail();
            return $response;
        } elseif ($r->requestPath === '/trakt/searchByTraktId/show/' && $r->requestMethod === 'POST') {
            $response = ShowRenderer::renderShowDetail();
            return $response;
        } elseif ($r->requestPath === '/trakt/sendrating/' && $r->requestMethod === "POST") {
            $rating = new Rating();
            $response = $rating->setRatingByPost();
            return $response;
        } elseif ($r->requestPath === '/trakt/setaswatched/movie/' && $r->requestMethod === "POST") {
            $history = new History();
            $response = $history->setMovieWatchedByPost();
            return $response;
        } elseif ($r->requestPath === '/trakt/setaswatched/episode/' && $r->requestMethod === "POST") {
            $history = new History();
            $response = $history->setEpisodeWatchedByPost();
        } elseif ($r->requestPath === '/trakt/episodecontrols/' && $r->requestMethod === "POST") {
            $response = ShowSeasonRenderer::renderEpisodeControls();
            return $response;
        }
        if ($response === false) {
            return false;
        }
        
        return $response;
    }
}
