<?php
namespace KmbModuleManagerTest\Controller;

use KmbDomain\Model\Environment;
use KmbModuleManagerTest\Bootstrap;
use KmbPmProxy\Model\PuppetModule;
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

        $puppetModuleService = $this->getMock('KmbPmProxy\Service\PuppetModuleInterface');
        $ntpModule = new PuppetModule('ntp');
        $ntpModule->setAvailableVersions(['1.0', '0.9', '0.8']);
        $apacheModule = new PuppetModule('apache');
        $apacheModule->setAvailableVersions(['2.4.2', '2.3.9', '2.2.10']);
        $puppetModuleService->expects($this->any())
            ->method('getAllAvailable')
            ->will($this->returnValue(['ntp' => $ntpModule, 'apache' => $apacheModule]));
        $puppetModuleService->expects($this->any())
            ->method('getAllInstalledByEnvironment')
            ->will($this->returnValue(['apache' => $apacheModule]));
        $serviceManager->setService('pmProxyPuppetModuleService', $puppetModuleService);
        $environmentRepository = $this->getMock('KmbDomain\Model\EnvironmentRepositoryInterface');
        $environmentRepository->expects($this->any())
            ->method('getById')
            ->will($this->returnValue(new Environment()));
        $serviceManager->setService('EnvironmentRepository', $environmentRepository);
    }

    /** @test */
    public function canUpdateModule()
    {
        $this->dispatch('/env/1/module-manager/module/apache/update', 'POST', ['version' => '2.4.2']);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/env/1/puppet/module/apache');
    }

    /** @test */
    public function canRemoveModule()
    {
        $this->dispatch('/env/1/module-manager/module/apache/remove', 'POST', ['version' => '2.4.2']);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/env/1/puppet/modules');
    }
}
