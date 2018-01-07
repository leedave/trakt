<?php

namespace Leedch\Trakt\Admin;

use Exception;
use Leedch\Html\Html5 as H;
use Leedch\Convert\Convert;
use Leedch\InfiniteScroll\Traits\TableTrait;
use Leedch\InfiniteScroll\Interfaces\TableInterface;
use Leedch\Trakt\Trakt\Users\Rating as feRating;
use Leedch\Trakt\Renderer\Movie;

/**
 * Description of Collection
 *
 * @author leed
 */
class Rating extends feRating implements TableInterface
{
    use TableTrait {
        renderTable as protected traitRenderTable;
        jsonRows as protected traitJsonRows;
    }
    
    public function getTableAttributes()
    {
        $this->tableAttributes = [
            "id" => "collectionTable",
            "class" => "infiniteScrollTable",
            "data-loadurl" => "/trakt/ratings/load/",
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
        $this->headerColumns = [
            "id" => "id",
            "Title" => "title",
            "Year" => "year",
            "Rating" => "rating",
            "Type" => "type",
            "Detail" => "showDetail",
            "Rated At" => "ratedAt",
        ];
        return $this->headerColumns;
    }
    
    public function renderTable()
    {
        $content = $this->traitRenderTable();
        $contentExtended = H::div(
                        H::a("Back", ["href" => "/trakt"])
                        . " "
                        . H::a("Refresh", ["href" => "/trakt/ratings/refresh/"])
                    , ["class" => "infiniteScrollTableHeaderLinks"])
                    .H::br(1)
                    .$content;
        return $contentExtended;
    }
    
    public function jsonRows()
    {
        $jsonTrait = $this->traitJsonRows();
        $arrTrait = Convert::json_decode($jsonTrait);
        foreach ($arrTrait as $key => $val) {
            $arrRow = $this->jsonRow($val);
            $arrTrait[$key] = $arrRow;
        }
        $jsonResponse = Convert::json_encode($arrTrait);
        return $jsonResponse;
    }
    
    protected function jsonRow(array $arrRow)
    {
        if ($arrRow['type'] === "movie") {
            return $this->jsonMovieRow($arrRow);
        }
        return $arrRow;
    }
    
    protected function jsonMovieRow(array $arrRow)
    {
        $tmpRating = new Rating();
        $tmpRating->load($arrRow['id']);
        $jsonCollection = $tmpRating->getJson();
        $arrTraktData = Convert::json_decode($jsonCollection);
        $renderMovie = Movie::render($arrTraktData);
        $arrRow['title'] = $renderMovie;
        return $arrRow;
    }
}
