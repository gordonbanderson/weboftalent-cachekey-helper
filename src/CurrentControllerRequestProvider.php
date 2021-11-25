<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

class CurrentControllerRequestProvider
{
    /**
     * @return HTTPRequest
     */
    public function getRequest()
    {
        return Controller::curr()->getRequest();
    }
}
