<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app=parent::createApplication();
        $driver=config('database.default'); $database=config('database.connections.'.$driver.'.database');
        if (!(($driver==='sqlite' && $database===':memory:') || ($driver==='mysql' && is_string($database) && str_ends_with($database,'_test')))) {
            throw new \RuntimeException('Tests require in-memory SQLite or a MySQL database ending in _test. Refusing to touch a business database.');
        }
        return $app;
    }
}
