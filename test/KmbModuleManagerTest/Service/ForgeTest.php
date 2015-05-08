<?php
namespace KmbModuleManagerTest\Service;

use KmbModuleManager\Service\Forge;

class ForgeTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function canPostHook()
    {
        $client = $this->getMock('KmbModuleManager\Service\ForgeClientInterface');
        $client->expects($this->once())->method('post')->with('/gitlab/hook', ['object_kind' => 'push']);
        $forgeService = new Forge();
        $forgeService->setClient($client);

        $forgeService->postHook(['object_kind' => 'push']);
    }
}
