<?php
namespace EVT\CoreDomain\Tests\Provider;

use EVT\CoreDomain\Provider\ProviderId;

use EVT\CoreDomain\Provider\Provider;

/**
 * ProviderTest
 *
 * @author    Marco Ferrari <marco.ferrari@bodaclick.com>
 * @copyright 2014 Bodaclick S.A
 */
class ProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testProviderCreation()
    {
        $testName = "TestName";
        $provider = new Provider(new ProviderId(''), $testName);
        $this->assertEquals($testName, $provider->getName());
        $this->assertEquals($testName, $provider->getSlug());
    }
}