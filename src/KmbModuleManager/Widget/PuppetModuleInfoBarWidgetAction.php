<?php
/**
 * @copyright Copyright (c) 2014 Orange Applications for Business
 * @link      http://github.com/kambalabs for the sources repositories
 *
 * This file is part of Kamba.
 *
 * Kamba is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * Kamba is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kamba.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace KmbModuleManager\Widget;

use KmbBase\Widget\AbstractWidgetAction;
use KmbPmProxy\Model\PuppetModule;
use KmbPmProxy\Service\PuppetModuleInterface;
use Zend\View\Model\ViewModel;

class PuppetModuleInfoBarWidgetAction extends AbstractWidgetAction
{
    /** @var  PuppetModuleInterface */
    protected $moduleService;

    /**
     * @param ViewModel $model
     * @return ViewModel
     */
    public function call(ViewModel $model = null)
    {
        $moduleName = $this->params()->fromRoute('moduleName');

        /** @var PuppetModule[] $modules */
        $modules = $this->moduleService->getAllAvailable();
        if (array_key_exists($moduleName, $modules)) {
            /** @var PuppetModule $module */
            $module = $modules[$moduleName];
            $model->setVariable('availableVersions', $module->getAvailableVersions());
        }
        return $model;
    }

    /**
     * Set ModuleService.
     *
     * @param \KmbPmProxy\Service\PuppetModuleInterface $moduleService
     * @return PuppetModuleInfoBarWidgetAction
     */
    public function setModuleService($moduleService)
    {
        $this->moduleService = $moduleService;
        return $this;
    }

    /**
     * Get ModuleService.
     *
     * @return \KmbPmProxy\Service\PuppetModuleInterface
     */
    public function getModuleService()
    {
        return $this->moduleService;
    }
}
