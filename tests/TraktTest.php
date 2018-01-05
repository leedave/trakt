<?php

namespace Leedch\Trakt\Test;

use Leedch\Trakt\Trakt\Connector;
use Leedch\Trakt\Trakt\Users\Settings;
use PHPUnit\Framework\TestCase;

class TraktTest extends TestCase
{
    public function testConnection()
    {
        $m = new Connector();
        $response = $m->call('GET', 'movies/trending');

        $statusCode = (int) $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $arrJson = json_decode($body, JSON_UNESCAPED_UNICODE);
        
        $this->assertTrue(is_array($arrJson));
        $this->assertTrue((bool) count($arrJson));
        $this->assertEquals(200, $statusCode);
        
        //$m->getDeviceCode();
        //$m->getAccessToken();
        //$m->refreshAccessToken();
        $settings = new Settings();
        $settings->getUserSettings();
    }
}
