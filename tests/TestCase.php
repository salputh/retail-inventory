<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman: RefreshDatabase menjalankan migrate:fresh, jadi test tidak
     * boleh pernah menyentuh database selain database test.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $database = (string) config('database.connections.'.config('database.default').'.database');

        if (! str_ends_with($database, '_test')) {
            throw new RuntimeException(
                "Test ditolak: database '{$database}' bukan database test (harus berakhiran '_test'). Jalankan lewat `make test`."
            );
        }

        return $app;
    }
}
