<?php

namespace Leedch\Trakt;

use Leedch\Translate\Translate as T;
use Leedch\Website\Core\Router;

/**
 * Starting Point for all actions
 *
 * @author leed
 */
class App
{
    protected $activeRoutes = ["\\Leedch\\Trakt\\Router"];
    protected $translations = [__DIR__ . '../translations/trakt.csv'];
    
    public function run()
    {
        if (!session_id()) {
            session_start();
        }
        
        $translator = T::getInstance();
        $translator->loadTranslations($this->translations);
        
        $router = new Router();
        $router->setActiveRoutes($this->activeRoutes);
        $response = $router->route();
        return $response;
    }
    
    
    
}
