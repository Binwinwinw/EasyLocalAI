<?php

namespace EasyLocalAI\Tests;

use PHPUnit\Framework\TestCase;
use EasyLocalAI\Core\Container;

class ContainerTest extends TestCase {
    public function testRegisterAndGet() {
        Container::register('test_service', function() {
            return "ok";
        });
        $this->assertEquals("ok", Container::get('test_service'));
    }

    public function testSingletonBehavior() {
        Container::register('random', function() {
            return uniqid();
        });
        $first = Container::get('random');
        $second = Container::get('random');
        $this->assertEquals($first, $second);
    }
}
