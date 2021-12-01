<?php

declare(strict_types = 1);

namespace WebOfTalent\Cache\Tests;

use SilverStripe\Dev\SapphireTest;
use WebOfTalent\Cache\CurrentControllerRequestProvider;

class CurrentControllerRequestProviderTest extends SapphireTest
{
    public function testDefaultProvider(): void
    {
        /** @var \WebOfTalent\Cache\RequestProvider $provider */
        $provider = new CurrentControllerRequestProvider();

        /** @var \SilverStripe\Control\HTTPRequest $request */
        $request = $provider->getRequest();

        $this->assertInstanceOf('SilverStripe\Control\HTTPRequest', $request);
    }
}
