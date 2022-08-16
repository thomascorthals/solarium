<?php

namespace Solarium\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Test OPcache compilation.
 *
 * @group integration
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class OpcacheCompileTest extends TestCase
{
    public function testOpcacheCompile()
    {
        $this->assertTrue(opcache_compile_file(__DIR__.'/../../src/Client.php'));
    }
}
