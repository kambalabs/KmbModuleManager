<?php
namespace KmbModuleManagerTest\Service;

use KmbModuleManager\Service\Forge;
use KmbModuleManagerTest\Bootstrap;

class ForgeFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function canCreateService()
    {
        /** @var Forge $service */
        $service = Bootstrap::getServiceManager()->get('KmbModuleManager\Service\Forge');

        $this->assertInstanceOf('KmbModuleManager\Service\Forge', $service);
        $this->assertInstanceOf('KmbModuleManager\Service\ForgeClient', $service->getClient());
    }
}
