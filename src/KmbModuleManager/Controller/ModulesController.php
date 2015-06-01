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

class ModulesController extends AbstractActionController implements AuthenticatedControllerInterface
{
    public function installableAction()
    {
        /** @var EnvironmentInterface $environment */
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        if ($environment == null) {
            return $this->notFoundAction();
        }
        /** @var PuppetModule[] $modules */
        $modules = $this->getServiceLocator()->get('pmProxyPuppetModuleService')->getAllInstallableByEnvironment($environment);
        $response = [];

        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');
        $formatModuleVersion = $viewHelperManager->get('formatModuleVersion');
        if (!empty($modules)) {
            foreach ($modules as $module) {
                foreach ($module->getAvailableVersions() as $version) {
                    $response[$module->getName()][$version] = $formatModuleVersion($version);
                }
            }
        }
        return new JsonModel($response);
    }

    public function installAction()
    {
        /** @var EnvironmentInterface $environment */
        $environment = $this->getServiceLocator()->get('EnvironmentRepository')->getById($this->params()->fromRoute('envId'));
        if ($environment == null) {
            return $this->notFoundAction();
        }

        /** @var Service\PuppetModule $moduleService */
        $moduleService = $this->getServiceLocator()->get('pmProxyPuppetModuleService');

        $moduleName = $this->params()->fromPost('module');
        $version = $this->params()->fromPost('version');

        /** @var PuppetModule[] $modules */
        $modules = $moduleService->getAllInstallableByEnvironment($environment);
        if (!array_key_exists($moduleName, $modules)) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('Module %s cannot be installed in environment %s (already installed or unknown module) !'), $moduleName, $environment->getNormalizedName()));
            return $this->redirect()->toRoute('puppet', ['controller' => 'modules', 'action' => 'index'], [], true);
        }
        /** @var PuppetModule $module */
        $module = $modules[$moduleName];
        if (!in_array($version, $module->getAvailableVersions())) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('Version %s is not available for module %s !'), $version, $moduleName));
            return $this->redirect()->toRoute('puppet', ['controller' => 'modules', 'action' => 'index'], [], true);
        }

        try {
            $moduleService->installInEnvironment($environment, $module, $version);
            $module->setVersion($version);
        } catch (PuppetModuleException $e) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate("The command 'puppet module install' for module %s %s returned the following error on the puppet master : %s"), $moduleName, $version, $e->getMessage()));
            $this->writeLog(sprintf($this->translate("The command 'puppet module install' for module %s %s on environment %s returned the following error : %s"), $moduleName, $version, $environment->getNormalizedName(), $e->getMessage()));
            return $this->redirect()->toRoute('puppet', ['controller' => 'modules', 'action' => 'index'], [], true);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage(sprintf($this->translate('An error occured when installing module %s %s : %s'), $moduleName, $version, $e->getMessage()));
            $this->writeLog(sprintf($this->translate("Failed to install module %s %s on environment %s : %s"), $moduleName, $version, $environment->getNormalizedName(), $e->getMessage()));
            return $this->redirect()->toRoute('puppet', ['controller' => 'modules', 'action' => 'index'], [], true);
        }

        if ($module->isOnBranch() && $this->params()->fromPost('autoUpdate')) {
            /** @var EnvironmentRepositoryInterface $environmentRepository */
            $environmentRepository = $this->getServiceLocator()->get('EnvironmentRepository');
            $environment->addAutoUpdatedModule($module->getName(), $module->getBranchNameFromVersion());
            $environmentRepository->update($environment);
        }

        $this->writeLog(sprintf($this->translate("Module %s %s has been successfully installed on environment %s"), $moduleName, $version, $environment->getNormalizedName()));
        $this->flashMessenger()->addSuccessMessage(sprintf($this->translate('Module %s %s has been successfully installed !'), $moduleName, $version));
        return $this->redirect()->toRoute('puppet', ['controller' => 'modules', 'action' => 'index'], [], true);
    }
}
