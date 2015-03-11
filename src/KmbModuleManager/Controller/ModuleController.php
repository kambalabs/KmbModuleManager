<?php
/**
 * @copyright Copyright (c) 2014, 2015 Orange Applications for Business
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
namespace KmbModuleManager\Controller;

use KmbAuthentication\Controller\AuthenticatedControllerInterface;
use KmbDomain\Model\EnvironmentInterface;
use KmbPmProxy\Model\PuppetModule;
use KmbPmProxy\Service;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class ModuleController extends AbstractActionController implements AuthenticatedControllerInterface
{
    public function versionsAction()
    {
        /** @var EnvironmentInterface $environment */
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        if ($environment == null) {
            return $this->notFoundAction();
        }

        /** @var Service\PuppetModule $moduleService */
        $moduleService = $this->getServiceLocator()->get('pmProxyPuppetModuleService');

        $moduleName = $this->params()->fromRoute('name');

        /** @var PuppetModule[] $modules */
        $modules = $moduleService->getAllAvailable();
        if (!array_key_exists($moduleName, $modules)) {
            return $this->notFoundAction();
        }
        /** @var PuppetModule $module */
        $module = $modules[$moduleName];
        return new JsonModel($module->getAvailableVersions());
    }

    public function updateAction()
    {
        $back = $this->params()->fromQuery('back');
        /** @var EnvironmentInterface $environment */
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        if ($environment == null) {
            return $this->notFoundAction();
        }

        /** @var Service\PuppetModule $moduleService */
        $moduleService = $this->getServiceLocator()->get('pmProxyPuppetModuleService');

        $moduleName = $this->params()->fromRoute('name');
        $version = $this->params()->fromPost('version');
        $force = $this->params()->fromPost('force_action');

        /** @var PuppetModule[] $modules */
        $modules = $moduleService->getAllInstalledByEnvironment($environment);
        if (!array_key_exists($moduleName, $modules)) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('Module %s is unknown or not installed !'), $moduleName));
            return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => [ 'back' => $back ]], true);
        }
        /** @var PuppetModule $module */
        $module_list = $moduleService->getAllAvailable();
        $module = $module_list[$moduleName];
        if (!in_array($version, $module->getAvailableVersions())) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('Version %s is not available for module %s !'), $version, $moduleName));
            return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => [ 'back' => $back ]], true);
        }

        try {
            $moduleService->upgradeModuleInEnvironment($environment, $module, $version, $force);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('An error occured when installing module %s %s : %s'), $moduleName, $version, $e->getMessage()));
            return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => [ 'back' => $back ]], true);
        }

        $this->flashMessenger()->addSuccessMessage(sprintf($this->translate('Module %s %s has been successfully installed !'), $moduleName, $version));
        return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => [ 'back' => $back ]], true);
    }

    public function removeAction()
    {
        /** @var EnvironmentInterface $environment */
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        if ($environment == null) {
            return $this->notFoundAction();
        }

        /** @var Service\PuppetModule $moduleService */
        $moduleService = $this->getServiceLocator()->get('pmProxyPuppetModuleService');

        $moduleName = $this->params()->fromRoute('name');

        /** @var PuppetModule[] $modules */
        $modules = $moduleService->getAllInstalledByEnvironment($environment);
        if (!array_key_exists($moduleName, $modules)) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('Module %s is not installed in environment %s !'), $moduleName, $environment->getNormalizedName()));
            return $this->redirect()->toRoute('puppet', ['controller' => 'modules', 'action' => 'index'], [], true);
        }
        /** @var PuppetModule $module */
        $module = $modules[$moduleName];

        try {
            $moduleService->removeFromEnvironment($environment, $module);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('An error occured when removing module %s : %s'), $moduleName, $e->getMessage()));
            return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => [ 'back' => $this->params()->fromQuery('back') ]], true);
        }

        $this->flashMessenger()->addSuccessMessage(sprintf($this->translate('Module %s has been successfully remove !'), $moduleName));
        return $this->redirect()->toRoute('puppet', ['controller' => 'modules', 'action' => 'index'], [], true);
    }
}
