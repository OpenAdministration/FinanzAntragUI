<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public string $baseUrl = 'http://localhost:8000';
}
