<?php

namespace Leedch\Trakt\Admin;

use Exception;
use Leedch\Html\Html5 as H;
use Leedch\Convert\Convert;
use Leedch\InfiniteScroll\Traits\TableTrait;
use Leedch\InfiniteScroll\Interfaces\TableInterface;
use Leedch\Trakt\Renderer\Movie;
use Leedch\Trakt\Trakt\Users\History as feHistory;

/**
 * Description of Collection
 *
 * @author leed
 */
class History extends feHistory implements TableInterface
{
    use TableTrait {
        renderTable as protected traitRenderTable;
    }
    
    public function getTableAttributes()
    {
        if (isset($this->tableAttributes['id'])) {
            return $this->tableAttributes;
        }
        $this->tableAttributes = [
            "id" => "historyTable",
            "class" => "infiniteScrollTable",
            "data-loadurl" => "/trakt/watched/load/movies/",
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
            "Type" => "type",
            "Detail" => "showDetail",
            "Watched At" => "watchedAt",
        ];
        return $this->headerColumns;
    }
    
    public function renderMovieTable()
    {
        $this->getTableAttributes();
        $content = $this->traitRenderTable();
        $contentExtended = H::div(
                        H::a("Back", ["href" => "/trakt"])
                        . " "
                        . H::a("Refresh", ["href" => "/trakt/watched/refresh/"])
                    , ["class" => "infiniteScrollTableHeaderLinks"])
                    .H::br(1)
                    .$content;
        return $contentExtended;
    }
    
    public function renderShowsTable()
    {
        $this->getTableAttributes();
        $this->tableAttributes['data-loadurl'] = "/trakt/watched/load/shows/";
        $content = $this->traitRenderTable();
        $contentExtended = H::a("Refresh", ["href" => "/trakt/watched/refresh/"])
                    .H::br(2)
                    .$content;
        return $contentExtended;
    }
    
    public function jsonRowsMovies()
    {
        $_POST['filter_type'] = "movie";
        $rows = Convert::json_decode($this->jsonRows());
        foreach ($rows as $key => $row) {
            $rows[$key] = $this->jsonMovieRow($row);
        }
        return Convert::json_encode($rows);
    }
    
    protected function jsonMovieRow(array $arrRow)
    {
        $tmpHistory = new History();
        $tmpHistory->load($arrRow['id']);
        $jsonCollection = $tmpHistory->getJson();
        $arrTraktData = Convert::json_decode($jsonCollection);
        $renderMovie = Movie::render($arrTraktData);
        $arrRow['title'] = $renderMovie;
        return $arrRow;
    }
    
    public function jsonRowsShows()
    {
        $_POST['filter_type'] = "show";
        return $this->jsonRows();
    }
    
}
