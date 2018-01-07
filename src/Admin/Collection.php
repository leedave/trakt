<?php

namespace Leedch\Trakt\Admin;

use Leedch\Html\Html5 as H;
use Leedch\Convert\Convert;
use Leedch\InfiniteScroll\Interfaces\TableInterface;
use Leedch\InfiniteScroll\Traits\TableTrait;
use Leedch\Trakt\Trakt\Users\Collection as feCollection;
use Leedch\Trakt\Renderer\Movie;

/**
 * Description of Collection
 *
 * @author leed
 */
class Collection extends feCollection implements TableInterface
{
    use TableTrait {
        renderTable as protected traitRenderTable;
        jsonRows as protected traitJsonRows;
    }
    
    public function getTableAttributes()
    {
        if (isset($this->tableAttributes['id'])) {
            return $this->tableAttributes;
        }
        $this->tableAttributes = [
            "id" => "collectionTable",
            "class" => "infiniteScrollTable",
            "data-loadurl" => "/trakt/collection/load/movies/",
            //"data-loadrowurl" => "/blog/entry/row/",
            //"data-updateurl" => "/blog/entry/update/",
            //"data-blogid" => $blogId,
        ];
        return $this->tableAttributes;
    }
    
    public function getActionLinks()
    {
        $this->actionLinks = [];
        return $this->actionLinks;
    }
    
    public function getHeaderColumns()
    {
        if (isset($this->headerColumns['id'])) {
            return $this->headerColumns;
        }
        $this->headerColumns = [
            "id" => "id",
            "Title" => "title",
            "Year" => "year",
            "Type" => "type",
            "Collected At" => "collectedAt",
        ];
        return $this->headerColumns;
    }
    
    public function renderMoviesCollectionTable()
    {
        $content = H::div(
                        H::a("Back", ["href" => "/trakt"])
                        . " "
                        . H::a("Refresh", ["href" => "/trakt/collection/refresh/movies/"])
                    , ["class" => "infiniteScrollTableHeaderLinks"])
                    .H::br(1)
                    .$this->traitRenderTable();
        return $content;
    }
    
    public function renderShowsCollectionTable()
    {
        $this->getTableAttributes();
        $this->tableAttributes["data-loadurl"] ="/trakt/collection/load/shows/";
        
        $content = H::a("Refresh", ["href" => "/trakt/collection/refresh/shows/"])
                    .H::br(2)
                    .$this->traitRenderTable();
        return $content;
    }
    
    /**
     * Fetches Data for Table Rows and returns as json
     * uses POST vars page, pageSize, sort, sortDir
     * @return string JSON Data
     * @throws Exception
     */
    public function jsonMoviesRows()
    {
        $_POST['filter_type'] = "movies";
        $jsonTrait = $this->traitJsonRows();
        $arrTrait = Convert::json_decode($jsonTrait);
        foreach ($arrTrait as $key => $val) {
            $arrRow = $this->jsonMovieRow($val);
            $arrTrait[$key] = $arrRow;
        }
        $jsonResponse = Convert::json_encode($arrTrait);
        return $jsonResponse;
    }
    
    /**
     * Formats a movie row in Table
     * @param array $arrRow
     * @return array
     */
    protected function jsonMovieRow($arrRow)
    {
        $tmpCollection = new Collection();
        $tmpCollection->load($arrRow['id']);
        $jsonCollection = $tmpCollection->getJson();
        $arrTraktData = Convert::json_decode($jsonCollection);
        $renderMovie = Movie::render($arrTraktData);
        $arrRow['title'] = $renderMovie;
        return $arrRow;
    }


    /**
     * Fetches Data for Table Rows and returns as json
     * uses POST vars page, pageSize, sort, sortDir
     * @return string JSON Data
     * @throws Exception
     */
    public function jsonShowRows()
    {
        $_POST['filter_type'] = "shows";
        return $this->traitJsonRows();
    }
}
