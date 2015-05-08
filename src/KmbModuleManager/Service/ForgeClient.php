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

use KmbModuleManager\Exception\NotFoundException;
use KmbModuleManager\Exception\RuntimeException;
use KmbModuleManager\Options\ForgeClientOptionsInterface;
use Zend\Http\Headers;
use Zend\Http\Request;
use Zend\Json\Json;
use Zend\Log\Logger;

class ForgeClient implements ForgeClientInterface
{
    /** @var ForgeClientOptionsInterface */
    protected $options;

    /** @var \Zend\Http\Client */
    protected $httpClient;

    /** @var Logger */
    protected $logger;

    /**
     * @param string $uri
     * @return mixed
     */
    public function get($uri)
    {
        return $this->send(Request::METHOD_GET, $uri);
    }

    /**
     * @param string $uri
     * @param array  $content
     */
    public function put($uri, $content)
    {
        $this->send(Request::METHOD_PUT, $uri, $content);
    }

    /**
     * @param string $uri
     * @param array  $content
     */
    public function post($uri, $content)
    {
        $this->send(Request::METHOD_POST, $uri, $content);
    }

    /**
     * @param string $uri
     */
    public function delete($uri)
    {
        $this->send(Request::METHOD_DELETE, $uri);
    }

    /**
     * Set Options.
     *
     * @param ForgeClientOptionsInterface $options
     * @return ForgeClient
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get Options.
     *
     * @return ForgeClientOptionsInterface
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set HttpClient.
     *
     * @param \Zend\Http\Client $httpClient
     * @return ForgeClient
     */
    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    /**
     * Get HttpClient.
     *
     * @return \Zend\Http\Client
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Set Logger.
     *
     * @param \Zend\Log\Logger $logger
     * @return ForgeClient
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get Logger.
     *
     * @return \Zend\Log\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param $method
     * @param $uri
     * @param $content
     * @return mixed
     * @throws RuntimeException
     * @throws NotFoundException
     */
    protected function send($method, $uri, $content = null)
    {
        $request = new Request();
        $request->setUri($this->getUri($uri));
        $request->setMethod($method);
        $headers = new Headers();
        $headers->addHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
        $request->setHeaders($headers);
        if ($content !== null) {
            $request->setContent(Json::encode($content));
        }

        $start = microtime(true);
        try {
            $httpResponse = $this->getHttpClient()->send($request);
        } catch (\Exception $exception) {
            $errorMessage = 'Error on sending request ' . $request->getUri() . ' : ' . $exception->getMessage();
            $this->getLogger()->err($errorMessage);
            throw new RuntimeException($errorMessage, 0, $exception);
        }
        $this->logRequest($start, $httpResponse->renderStatusLine(), $uri);

        $body = $httpResponse->getBody();
        $result = Json::decode(trim($body));
        $message = $result !== null && !empty($result->message) ? $result->message : $httpResponse->renderStatusLine();
        if ($httpResponse->isNotFound()) {
            throw new NotFoundException($message);
        } elseif (!$httpResponse->isSuccess()) {
            $this->getLogger()->err('[' . $httpResponse->renderStatusLine() . '] ' . $body);
            throw new RuntimeException($message);
        }

        return $result;
    }

    /**
     * @param $uri
     * @return string
     */
    protected function getUri($uri)
    {
        return rtrim($this->getOptions()->getBaseUri(), '/') . $uri;
    }

    /**
     * @param $start
     * @param $statusLine
     * @param $uri
     */
    protected function logRequest($start, $statusLine, $uri)
    {
        $duration = intval((microtime(true) - $start) * 1000);
        $splittedUri = explode('?', $uri);
        $uriToLog = array_shift($splittedUri);
        $this->getLogger()->debug("[$duration ms] [" . $statusLine . "] $uriToLog");
//        $this->getLogger()->debug("[$duration ms] [" . $statusLine . "] $uri");
    }
}
