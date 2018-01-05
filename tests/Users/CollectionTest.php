<?php

namespace Leedch\Trakt\Test\Users;

use Leedch\Trakt\Trakt\Connector;
use Leedch\Trakt\Trakt\Users\Collection;
use Leedch\Trakt\Trakt\Users\Settings;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testMovieValidation()
    {
        $m = new Collection();
        //Not in use for now
        $this->assertTrue(true);
    }
    
    protected function fakeMovie()
    {
        $json = '{
                    "collected_at": "2014-09-01T09:10:11.000Z",
                    "movie": {
                      "title": "TRON: Legacy",
                      "year": 2010,
                      "ids": {
                        "trakt": 1,
                        "slug": "tron-legacy-2010",
                        "imdb": "tt1104001",
                        "tmdb": 20526
                      }
                    }
                  }';
        return json_decode($json, true);
    }
}
