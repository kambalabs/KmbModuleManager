<?php
namespace KmbModuleManagerTest\Controller;

use KmbDomain\Model\Environment;
use KmbModuleManagerTest\Bootstrap;
use KmbPmProxy\Model\PuppetModule;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Zend\Json\Json;

class ModulesControllerTest extends AbstractHttpControllerTestCase
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
            ->method('getAllInstallableByEnvironment')
            ->will($this->returnValue([$ntpModule, $apacheModule]));
        $serviceManager->setService('pmProxyPuppetModuleService', $puppetModuleService);
        $environmentRepository = $this->getMock('KmbDomain\Model\EnvironmentRepositoryInterface');
        $environmentRepository->expects($this->any())
            ->method('getById')
            ->will($this->returnValue(new Environment()));
        $serviceManager->setService('EnvironmentRepository', $environmentRepository);
    }

    /** @test */
    public function canGetInstallable()
    {
        $this->dispatch('/env/1/module-manager/modules/installable');

        $this->assertResponseStatusCode(200);
        $this->assertControllerName('KmbModuleManager\Controller\Modules');
        $this->assertActionName('installable');
        $this->assertEquals([
            'ntp' => [
                '1.0',
                '0.9',
                '0.8',
            ],
            'apache' => [
                '2.4.2',
                '2.3.9',
                '2.2.10'
            ]
        ], Json::decode($this->getResponse()->getContent(), Json::TYPE_ARRAY));
    }

    /** @test */
    public function canInstallModule()
    {
        $this->dispatch('/env/1/module-manager/modules/install', 'POST', ['module' => 'apache', 'version' => '2.4.2']);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/env/1/puppet/modules');
    }
}
