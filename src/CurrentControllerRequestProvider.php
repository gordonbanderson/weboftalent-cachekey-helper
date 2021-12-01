<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;

class CurrentControllerRequestProvider
{
    public function getRequest(): HTTPRequest
    {
        return Controller::curr()->getRequest();
    }
}
