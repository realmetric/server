<?php

namespace App\Tests;

use App\Library\EventSaver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TrackAndReadTest extends KernelTestCase
{
    public function test()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        /** @var EventSaver $eventSaver */
        $eventSaver = $container->get(EventSaver::class);
        $eventSaver->save('test', 1, time(), ['sort' => 'first']);
        $this->assertEquals(1, 1);
    }
}
