<?php
namespace KmbModuleManagerTest\Service;

use KmbModuleManager\Service\ForgeClient;
use KmbModuleManagerTest\Bootstrap;

class ForgeClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function canCreateService()
    {
        /** @var ForgeClient $service */
        $service = Bootstrap::getServiceManager()->get('KmbModuleManager\Service\ForgeClient');

        $this->assertInstanceOf('KmbModuleManager\Service\ForgeClient', $service);
        $this->assertInstanceOf('KmbModuleManager\Options\ForgeClientOptionsInterface', $service->getOptions());
        $this->assertInstanceOf('Zend\Http\Client', $service->getHttpClient());
        $this->assertInstanceOf('Zend\Log\Logger', $service->getLogger());
    }
}
