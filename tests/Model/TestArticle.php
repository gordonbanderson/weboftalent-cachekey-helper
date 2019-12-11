<?php

namespace WebOfTalent\Cache\Tests\Model;

use SilverStripe\Dev\TestOnly;
use WebOfTalent\Cache\CacheKeyHelper;

class TestArticle extends \Page implements TestOnly
{
    private static $extensions = [
        CacheKeyHelper::class
    ];
}
