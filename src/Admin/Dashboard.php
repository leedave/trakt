<?php

namespace Leedch\Trakt\Admin;

use GuzzleHttp\Exception\ClientException;
use Leedch\View\View as V;
use Leedch\Trakt\Trakt\Connector;
use Leedch\Trakt\Trakt\Config;
use Leedch\Trakt\Trakt\Users\Settings;
use Leedch\Trakt\Trakt\Image;

/**
 * Description of Dashboard
 *
 * @author leed
 */
class Dashboard {
    
    protected $connector;
    
    public function __construct() {
        $this->connector = Connector::getInstance();
    }
    
    public function renderOverview() {
        if (!$this->connector->hasDeviceCode()) {
            //Device not yet registered
            $this->connector->getDeviceCode();
            return $this->renderRegisterDevicePage();
        }
        if (!$this->connector->hasAccessToken()) {
            //No OAuth Access Token
            try {
                $this->connector->getAccessToken();
            } catch (ClientException $ex) {
                return $this->renderRegisterDevicePage();
            }
        }
        
        return $this->renderDashboard();
    }
    
    protected function renderDashboard()
    {
        $img = new Image();
        $settings = new Settings();
        $settings->getUserSettings();
        $arrSettings = $settings->getData();
        $avatarFile = $img->getImage($arrSettings['user']['images']['avatar']['full']);
        $arrAvatarFile = explode(DIRECTORY_SEPARATOR, $avatarFile);
        $avatarFileName = array_pop($arrAvatarFile);
        $avatarUrl = trakt_mediaFolderUrl . $avatarFileName;
        $attributes = [
            "AVATARURL" => $avatarUrl,
            "USERNAME" => $arrSettings['user']['username'],
            "WATCHING" => $arrSettings['sharing_text']['watching'],
            "JUSTWATCHED" => $arrSettings['sharing_text']['watched'],
        ];
        return V::loadView(trakt_templateAdminDashboard, $attributes);
    }
    
    


    /**
     * This Page appears if the automatic login failed or is not possible
     * @return string
     */
    protected function renderRegisterDevicePage()
    {
        $c = new Config();
        
        $userCode = $c->getConfig("user_code");
        $verificationUrl = $c->getConfig("verification_url");
        $attributes = [
            "USERCODE" => $userCode,
            "VERIFICATIONURL" => $verificationUrl,
        ];
        return V::loadView(trakt_templateAdminRegisterDevice, $attributes);
    }
}
