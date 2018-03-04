<?php

declare(strict_types = 1);

namespace Nova\Payum\P24\Tests;

use Mockery;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }
}
