<?php
namespace KmbModuleManagerTest\Controller;

use KmbDomain\Model\Environment;
use KmbModuleManagerTest\Bootstrap;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ModuleHookControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(Bootstrap::getApplicationConfig());
        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);

        $environmentRepository = $this->getMock('KmbDomain\Service\EnvironmentRepositoryInterface');
        $environmentRepository->expects($this->any())
            ->method('getById')
            ->will($this->returnValue(new Environment()));
        $serviceManager->setService('EnvironmentRepository', $environmentRepository);
        $serviceManager->setService('KmbModuleManager\Service\Forge', $this->getMock('KmbModuleManager\Service\ForgeInterface'));
    }

    /** @test */
    public function canPostHook()
    {
        $this->dispatch('/api/module-manager/module/apache/hook', 'POST', ['version' => '2.4.2']);

        $this->assertResponseStatusCode(200);
    }
}
