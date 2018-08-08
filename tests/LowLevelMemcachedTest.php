<?php namespace Tests;

use Vacuum\LowLevelMemcached;

class LowLevelMemcachedTest extends Test
{
    public function testAsyncGetWorks()
    {
        $key = 'async-get.test';
        $value = "tricky async value to fetch later\r\n".
            "don't be confused with an END\r\n".
            "it's totally ok to find it that early, but please don't stop there\r\n".
            "and search for the real end.";
        $memcached = new LowLevelMemcached;

        $this->assertTrue($memcached->set($key, $value));
        $this->assertGreaterThan(0, $memcached->getLater($key));
        $this->assertEquals($value, $memcached->fetch());
    }

    public function testDeleteExistentKey()
    {
        $key = 'existent-key.delete.test';
        $value = 'some value to delete right away';
        $memcached = new LowLevelMemcached;

        $this->assertTrue($memcached->set($key, $value));
        $this->assertTrue($memcached->delete($key));
    }

    public function testDeleteNonexistentKey()
    {
        $key = 'nonexistent-key.delete.test';
        $memcached = new LowLevelMemcached;

        $this->assertFalse($memcached->delete($key));
    }

    public function testGetExistentKey()
    {
        $key = 'get.test';
        $value = 'get this value';
        $memcached = new LowLevelMemcached;

        $this->assertTrue($memcached->set($key, $value));
        $this->assertEquals($value, $memcached->get($key));
    }

    public function testGetNonexistentKey()
    {
        $key = 'nonexistent-key.get.test';
        $memcached = new LowLevelMemcached;

        $this->assertNull($memcached->get($key));
    }

    public function testSetWorks()
    {
        $key = 'set.test';
        $value = 'my value';
        $memcached = new LowLevelMemcached;

        $this->assertTrue($memcached->set($key, $value));
    }
}
