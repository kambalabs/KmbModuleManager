<?php
namespace KmbModuleManagerTest\Controller;

use KmbDomain\Model\Environment;
use KmbModuleManagerTest\Bootstrap;
use KmbPmProxy\Model\PuppetModule;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ModuleHookControllerTest extends AbstractHttpControllerTestCase
{
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
        $serviceManager->setService('KmbModuleManager\Service\Forge', $this->getMock('KmbModuleManager\Service\ForgeInterface'));
        $mainCacheManager = $this->getMock('KmbCache\Service\MainCacheManager', ['getCacheManager']);
        $mainCacheManager->expects($this->any())
            ->method('getCacheManager')
            ->will($this->returnValue($this->getMock('KmbCache\Service\AbstractCacheManager', ['forceRefreshCache'])));
        $serviceManager->setService('KmbCache\Service\MainCacheManager', $mainCacheManager);
    }

    /** @test */
    public function canPostHook()
    {
        $this->dispatch('/api/module-manager/module/apache/hook', 'POST', [ 'object_kind' => 'push', 'ref' => 'refs/heads/master']);

        $this->assertResponseStatusCode(200);
    }
}
