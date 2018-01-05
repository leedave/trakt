<?php

namespace Leedch\Trakt;

use Leedch\logger\Logger;

/**
 * Just want to try out Monolog, by Extending Logger I should be able to set up
 * my own Config
 *
 * @author leed
 */
class Log extends Logger
{
    public function __construct() {
        parent::__construct('Trakt');
        $this->setFileHandler(trakt_logFile);
        if (trakt_logSendMailOnCritical) {
            $this->setMailHandler(trakt_logEmailRecepient, "Trakt Emergeny @ ".trakt_logEmailWebsiteName, trakt_logEmailSender, Logger::CRITICAL);
        }
    }

    
}
