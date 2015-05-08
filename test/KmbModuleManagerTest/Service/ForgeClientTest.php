<?php
namespace KmbModuleManagerTest\Service;

use KmbModuleManager\Options\ModuleOptions;
use KmbModuleManager\Service\ForgeClient;
use KmbModuleManagerTest\Bootstrap;
use Zend\Log\Logger;

class ForgeClientTest extends \PHPUnit_Framework_TestCase
{
    /** @var ForgeClient */
    protected $forgeClient;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $httpClient;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $httpResponse;

    protected function setUp()
    {
        /** @var Logger $logger */
        $logger = Bootstrap::getServiceManager()->get('Logger');
        $this->httpClient = $this->getMock('Zend\Http\Client');
        $this->httpResponse = $this->getMock('Zend\Http\Response');
        $this->httpClient->expects($this->any())
            ->method('send')
            ->will($this->returnValue($this->httpResponse));
        $this->forgeClient = new ForgeClient();
        $this->forgeClient->setOptions(new ModuleOptions());
        $this->forgeClient->setLogger($logger);
        $this->forgeClient->setHttpClient($this->httpClient);
    }

    /**
     * @test
     * @expectedException \KmbModuleManager\Exception\RuntimeException
     * @expectedExceptionMessage Post error
     */
    public function cannotPostWhenRequestFails()
    {
        $this->httpResponse->expects($this->any())
            ->method('isSuccess')
            ->will($this->returnValue(false));
        $this->httpResponse->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue('{"message":"Post error"}'));

        $this->forgeClient->put('/gitlab/hook', ['object_kind' => 'push']);
    }

    /** @test */
    public function canPost()
    {
        $this->httpResponse->expects($this->once())
            ->method('isSuccess')
            ->will($this->returnValue(true));

        $this->forgeClient->put('/gitlab/hook', ['object_kind' => 'push']);
    }
}
