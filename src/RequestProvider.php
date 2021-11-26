<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache;

use SilverStripe\Control\HTTPRequest;

interface RequestProvider
{
    public function getRequest(): HTTPRequest;
}
