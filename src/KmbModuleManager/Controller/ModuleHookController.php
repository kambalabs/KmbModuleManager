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

use KmbCache\Service\InstallablePuppetModuleCacheManager;
use KmbCache\Service\InstalledPuppetModuleCacheManager;
use KmbCache\Service\MainCacheManager;
use KmbDomain\Service\EnvironmentRepositoryInterface;
use KmbModuleManager\Service\ForgeInterface;
use KmbPmProxy\Exception\PuppetModuleException;
use KmbPmProxy\Model;
use KmbPmProxy\Service;
use Zend\Log\Logger;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

class ModuleHookController extends AbstractRestfulController
{
    public function create($data)
    {
        $moduleName = $this->params()->fromRoute('name');
        $ref = isset($data['ref']) ? $data['ref'] : '';
        $userName = isset($data['user_name']) ? $data['user_name'] : '-';
        $this->writeLog(sprintf($this->translate("Receive Gitlab Hook for module %s : %s"), $moduleName, $ref), $userName);

        /** @var ForgeInterface $forgeService */
        $forgeService = $this->getServiceLocator()->get('KmbModuleManager\Service\Forge');
        $forgeService->postHook($data);

        /** @var MainCacheManager $mainCacheManager */
        $mainCacheManager = $this->serviceLocator->get('KmbCache\Service\MainCacheManager');
        $availableModulesCacheManager = $mainCacheManager->getCacheManager('availableModules');
        $availableModulesCacheManager->forceRefreshCache();

        /** @var Service\PuppetModule $moduleService */
        $moduleService = $this->serviceLocator->get('pmProxyPuppetModuleService');

        /** @var Logger $logger */
        $logger = $this->serviceLocator->get('Logger');

        if (strpos($ref, 'refs/heads/') === 0) {
            $branch = str_replace('refs/heads/', '', $ref);
            $logger->debug("PUSH event on branch $branch of module $moduleName");

            /** @var EnvironmentRepositoryInterface $environmentRepository */
            $environmentRepository = $this->serviceLocator->get('EnvironmentRepository');
            $environments = $environmentRepository->getAllWhereModuleIsAutoUpdated($moduleName, $branch);

            if (!empty($environments)) {
                $logger->debug("Found " . count($environments) . " environments where module $moduleName is auto updated on branch $branch");
                /** @var InstalledPuppetModuleCacheManager $installedPuppetModuleCacheManager */
                $installedPuppetModuleCacheManager = $mainCacheManager->getCacheManager('installedModules');
                /** @var InstallablePuppetModuleCacheManager $installablePuppetModuleCacheManager */
                $installablePuppetModuleCacheManager = $mainCacheManager->getCacheManager('installableModules');
                foreach ($environments as $environment) {
                    /** @var Model\PuppetModule[] $modules */
                    $modules = $moduleService->getAllInstalledByEnvironment($environment);
                    if (!array_key_exists($moduleName, $modules)) {
                        $logger->err("Module $moduleName is unknown or not installed in " . $environment->getNormalizedName() . " !");
                        continue;
                    }
                    /** @var Model\PuppetModule $module */
                    $moduleList = $moduleService->getAllAvailable();
                    $module = $moduleList[$moduleName];
                    $version = $module->getAvailableVersionMatchingBranch($branch);
                    if (is_null($version)) {
                        $logger->err("Branch $branch is not available for module $moduleName !");
                        continue;
                    }

                    try {
                        $logger->info("Upgrading module $moduleName to version $version on " . $environment->getNormalizedName());
                        $moduleService->upgradeModuleInEnvironment($environment, $module, $version, true);
                        $this->writeLog(sprintf($this->translate("Module %s has been successfully auto updated to %s on environment %s"), $moduleName, $version, $environment->getNormalizedName()), $userName);
                    } catch (PuppetModuleException $e) {
                        $logger->err("The command 'puppet module upgrade' for module $moduleName $version returned the following error on the puppet master : " . $e->getMessage());
                        $this->writeLog(sprintf($this->translate("The command 'puppet module upgrade' for auto update module %s %s on environment %s returned the following error : %s"), $moduleName, $version, $environment->getNormalizedName(), $e->getMessage()), $userName);
                        continue;
                    } catch (\Exception $e) {
                        $logger->err("An error occured when updating module $moduleName $version : " . $e->getMessage());
                        $this->writeLog(sprintf($this->translate("Failed to auto update module %s to %s on environment %s : %s"), $moduleName, $version, $environment->getNormalizedName(), $e->getMessage()), $userName);
                        continue;
                    }
                    $installablePuppetModuleCacheManager->forceRefreshCache($environment);
                    $installedPuppetModuleCacheManager->forceRefreshCache($environment);
                }
            } else {
                $logger->debug("Didn't find environments where module $moduleName is auto updated on branch $branch");
            }
        }

        return new JsonModel();
    }
}
