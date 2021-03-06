<?php
namespace KmbModuleManagerTest\Controller;

use KmbDomain\Model\Environment;
use KmbModuleManagerTest\Bootstrap;
use KmbPmProxy\Model\PuppetModule;
use Zend\Json\Json;
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
        $environmentRepository = $this->getMock('KmbDomain\Service\EnvironmentRepositoryInterface');
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

    /** @test */
    public function canEnableAutoUpdate()
    {
        $this->dispatch('/env/1/module-manager/module/apache/enable-auto-update');

        $this->assertResponseStatusCode(200);
        $this->assertEquals(['message' => 'Auto update has been successfully enabled on this module.'], Json::decode($this->getResponse()->getContent(), Json::TYPE_ARRAY));
    }

    /** @test */
    public function canDisableAutoUpdate()
    {
        $this->dispatch('/env/1/module-manager/module/apache/disable-auto-update');

        $this->assertResponseStatusCode(200);
        $this->assertEquals(['message' => 'Auto update has been successfully disabled on this module.'], Json::decode($this->getResponse()->getContent(), Json::TYPE_ARRAY));
    }
}
