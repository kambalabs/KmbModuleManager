<?php
namespace KmbModuleManagerTest\Controller;

use KmbDomain\Model\Environment;
use KmbModuleManagerTest\Bootstrap;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ModuleControllerTest extends AbstractHttpControllerTestCase
{
    protected $traceError = true;

    public function setUp()
    {
        $this->setApplicationConfig(Bootstrap::getApplicationConfig());
        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);

        $environmentRepository = $this->getMock('KmbDomain\Model\EnvironmentRepositoryInterface');
        $environmentRepository->expects($this->any())
            ->method('getById')
            ->will($this->returnValue(new Environment()));
        $serviceManager->setService('EnvironmentRepository', $environmentRepository);
    }

    /** @test */
    public function canGetVersions()
    {
        $this->dispatch('/env/1/module-manager/module/ntp/versions');

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('KmbModuleManager\Controller\Module');
        $this->assertActionName('versions');
    }
}
