<?php
namespace KmbModuleManagerTest\Service;

use KmbModuleManager\Service\Forge;

class ForgeTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function canPostHook()
    {
        $client = $this->getMock('KmbModuleManager\Service\ForgeClientInterface');
        $client->expects($this->once())->method('post')->with('/hook/gitlab', ['ref' => 'refs/heads/master']);
        $forgeService = new Forge();
        $forgeService->setClient($client);

        $forgeService->postHook(['ref' => 'refs/heads/master']);
    }
}
