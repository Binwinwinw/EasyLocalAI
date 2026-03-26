<?php

namespace EasyLocalAI\Tests;

use PHPUnit\Framework\TestCase;
use EasyLocalAI\Core\Security;

class SecurityTest extends TestCase {
    public function testSanitize() {
        $dirty = "<script>alert('xss')</script> Hello";
        $clean = Security::sanitize($dirty);
        $this->assertStringNotContainsString("<script>", $clean);
        $this->assertStringContainsString("&lt;script&gt;", $clean);
    }

    public function testCsrfToken() {
        $token1 = Security::getCsrfToken();
        $this->assertNotEmpty($token1);
        $this->assertTrue(Security::checkCsrf($token1));
        $this->assertFalse(Security::checkCsrf("invalid_token"));
    }
}
