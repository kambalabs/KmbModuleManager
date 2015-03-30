<?php
namespace KmbModuleManagerTest\Widget;

use KmbModuleManager\Widget\PuppetModuleInfoBarWidgetAction;
use KmbPmProxy\Model\PuppetModule;
use Zend\Mvc\Controller\PluginManager;
use Zend\View\Model\ViewModel;

class PuppetModuleInfoBarWidgetActionTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function canCall()
    {
        $module = new PuppetModule('kmb-fake');
        $module->setAvailableVersions(['1.1.0', '0.0.1']);
        $paramsHelper = $this->getMock('Zend\Mvc\Controller\Plugin\Params');
        $paramsHelper->expects($this->any())->method('__invoke')->will($this->returnValue($paramsHelper));
        $paramsHelper->expects($this->any())->method('fromRoute')->will($this->returnValue('kmb-fake'));
        $pluginManager = new PluginManager();
        $pluginManager->setService('params', $paramsHelper);
        $moduleService = $this->getMock('KmbPmProxy\Service\PuppetModuleInterface');
        $moduleService->expects($this->any())
            ->method('getAllAvailable')
            ->will($this->returnValue(['kmb-fake' => $module]));
        $action = new PuppetModuleInfoBarWidgetAction();
        $action->setController($this->getMock('Zend\Stdlib\DispatchableInterface'));
        $action->setPluginManager($pluginManager);
        $action->setModuleService($moduleService);
        $model = new ViewModel();

        $model = $action->call($model);

        $this->assertEquals(['1.1.0', '0.0.1'], $model->getVariable('availableVersions'));
    }
}
