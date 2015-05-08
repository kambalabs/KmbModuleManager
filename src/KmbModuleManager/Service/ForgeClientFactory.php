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
namespace KmbModuleManager\Service;

use KmbModuleManager\Options\ForgeClientOptionsInterface;
use Zend\Http;
use Zend\Log\Logger;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ForgeClientFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $service = new ForgeClient();

        /** @var ForgeClientOptionsInterface $moduleOptions */
        $moduleOptions = $serviceLocator->get('KmbModuleManager\Options\ModuleOptions');
        $service->setOptions($moduleOptions);

        /** @var Http\Client $httpClient */
        $httpClient = $serviceLocator->get('KmbModuleManager\Http\Client');
        $httpClient->setOptions($moduleOptions->getHttpOptions());
        $service->setHttpClient($httpClient);

        /** @var Logger $logger */
        $logger = $serviceLocator->get('Logger');
        $service->setLogger($logger);

        return $service;
    }
}
