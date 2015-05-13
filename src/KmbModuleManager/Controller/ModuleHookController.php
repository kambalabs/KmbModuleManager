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

        if (isset($data['object_kind']) && $data['object_kind'] == 'push') {
            $moduleName = $this->params()->fromRoute('name');
            $branch = str_replace('refs/heads/', '', isset($data['ref']) ? $data['ref'] : '');
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
                    $modules = $moduleService->getAllInstallableByEnvironment($environment);
                    if (!array_key_exists($moduleName, $modules)) {
                        $logger->err("Module $moduleName cannot be installed in environment " . $environment->getNormalizedName() . " (already installed or unknown module) !");
                        continue;
                    }
                    /** @var Model\PuppetModule $module */
                    $module = $modules[$moduleName];
                    $version = $module->getAvailableVersionMatchingBranch($branch);
                    if (is_null($version)) {
                        $logger->err("Branch $branch is not available for module $moduleName !");
                        continue;
                    }

                    try {
                        $logger->info("Upgrading module $moduleName to version $version on " . $environment->getNormalizedName());
                        $moduleService->upgradeModuleInEnvironment($environment, $module, $version, true);
                    } catch (PuppetModuleException $e) {
                        $logger->err("The command 'puppet module install' for module $moduleName $version returned the following error on the puppet master : " . $e->getMessage());
                        continue;
                    } catch (\Exception $e) {
                        $logger->err("An error occured when installing module $moduleName $version : " . $e->getMessage());
                        continue;
                    }
                    $installablePuppetModuleCacheManager->forceRefreshCache($environment);
                    $installedPuppetModuleCacheManager->forceRefreshCache($environment);
                }
            } else {
                $logger->debug("Not found environments where module $moduleName is auto updated on branch $branch");
            }
        }

        return new JsonModel();
    }
}
