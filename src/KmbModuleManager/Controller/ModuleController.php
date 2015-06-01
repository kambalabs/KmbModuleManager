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
use KmbDomain\Service\EnvironmentRepositoryInterface;
use KmbPmProxy\Exception\PuppetModuleException;
use KmbPmProxy\Model\PuppetModule;
use KmbPmProxy\Service;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class ModuleController extends AbstractActionController implements AuthenticatedControllerInterface
{
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
            return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => ['back' => $back]], true);
        }
        /** @var PuppetModule $module */
        $module_list = $moduleService->getAllAvailable();
        $module = $module_list[$moduleName];
        if (!in_array($version, $module->getAvailableVersions())) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('Version %s is not available for module %s !'), $version, $moduleName));
            return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => ['back' => $back]], true);
        }

        try {
            $moduleService->upgradeModuleInEnvironment($environment, $module, $version, $force);
        } catch (PuppetModuleException $e) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate("The command 'puppet module upgrade' for module %s %s returned the following error on the puppet master : %s"), $moduleName, $version, $e->getMessage()));
            $this->writeLog(sprintf($this->translate("The command 'puppet module upgrade' for module %s %s on environment %s returned the following error : <code>%s</code>"), $moduleName, $version, $environment->getNormalizedName(), $e->getMessage()));
            return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => ['back' => $back]], true);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('An error occured when updating module %s %s : %s'), $moduleName, $version, $e->getMessage()));
            $this->writeLog(sprintf($this->translate("Failed to update module %s to %s on environment %s : <code>%s</code>"), $moduleName, $version, $environment->getNormalizedName(), $e->getMessage()));
            return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => ['back' => $back]], true);
        }

        $this->writeLog(sprintf($this->translate("Module %s has been successfully updated to %s on environment %s"), $moduleName, $version, $environment->getNormalizedName()));
        $this->flashMessenger()->addSuccessMessage(sprintf($this->translate('Module %s %s has been successfully installed !'), $moduleName, $version));
        return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => ['back' => $back]], true);
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

        $back = $this->params()->fromQuery('back');
        try {
            $moduleService->removeFromEnvironment($environment, $module);
        } catch (PuppetModuleException $e) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate("The command 'puppet module uninstall' for module %s returned the following error on the puppet master : %s"), $moduleName, $e->getMessage()));
            $this->writeLog(sprintf($this->translate("The command 'puppet module uninstall' for module %s on environment %s returned the following error : <code>%s</code>"), $moduleName, $environment->getNormalizedName(), $e->getMessage()));
            return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => ['back' => $back]], true);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('An error occured when removing module %s : %s'), $moduleName, $e->getMessage()));
            $this->writeLog(sprintf($this->translate("Failed to remove module %s on environment %s : <code>%s</code>"), $moduleName, $environment->getNormalizedName(), $e->getMessage()));
            return $this->redirect()->toRoute('puppet-module', ['controller' => 'modules', 'action' => 'show', 'moduleName' => $moduleName], ['query' => ['back' => $back]], true);
        }

        /** @var EnvironmentRepositoryInterface $environmentRepository */
        $environmentRepository = $this->getServiceLocator()->get('EnvironmentRepository');
        $environment->removeAutoUpdatedModule($moduleName);
        $environmentRepository->update($environment);

        $this->writeLog(sprintf($this->translate("Remove module %s from environment %s"), $moduleName, $environment->getNormalizedName()));
        $this->flashMessenger()->addSuccessMessage(sprintf($this->translate('Module %s has been successfully remove !'), $moduleName));
        return $this->redirect()->toRoute('puppet', ['controller' => 'modules', 'action' => 'index'], [], true);
    }

    public function enableAutoUpdateAction()
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
            return $this->notFoundAction();
        }
        $module = $modules[$moduleName];

        /** @var EnvironmentRepositoryInterface $environmentRepository */
        $environmentRepository = $this->getServiceLocator()->get('EnvironmentRepository');
        $environment->addAutoUpdatedModule($module->getName(), $module->getBranchNameFromVersion());
        $environmentRepository->update($environment);

        $this->writeLog(sprintf($this->translate("Enable auto update for module %s on environment %s"), $moduleName, $environment->getNormalizedName()));
        return new JsonModel(['message' => $this->translate('Auto update has been successfully enabled on this module.')]);
    }

    public function disableAutoUpdateAction()
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
            return $this->notFoundAction();
        }

        /** @var EnvironmentRepositoryInterface $environmentRepository */
        $environmentRepository = $this->getServiceLocator()->get('EnvironmentRepository');
        $environment->removeAutoUpdatedModule($moduleName);
        $environmentRepository->update($environment);

        $this->writeLog(sprintf($this->translate("Disable auto update for module %s on environment %s"), $moduleName, $environment->getNormalizedName()));
        return new JsonModel(['message' => $this->translate('Auto update has been successfully disabled on this module.')]);
    }
}
