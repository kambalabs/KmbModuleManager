<?php
namespace KmbModuleManagerTest\Widget;

use KmbModuleManager\Widget\PuppetModuleInfoBarWidgetAction;
use KmbModuleManagerTest\Bootstrap;

class PuppetModuleInfoBarWidgetActionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function canCreateService()
    {
        /** @var PuppetModuleInfoBarWidgetAction $service */
        $service = Bootstrap::getServiceManager()->get('KmbModuleManager\Widget\PuppetModuleInfoBarWidgetAction');

        $this->assertInstanceOf('KmbModuleManager\Widget\PuppetModuleInfoBarWidgetAction', $service);
        $this->assertInstanceOf('KmbPmProxy\Service\PuppetModuleInterface', $service->getModuleService());
    }
}
