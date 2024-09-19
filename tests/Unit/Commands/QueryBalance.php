<?php
namespace Finxp\Flexcube\Tests\Unit\Commands;

use Finxp\Flexcube\Tests\TestCase;

class QueryBalanceTest extends TestCase
{
    /** @test */
    public function itShouldCallTheCommand()
    {
        $this->artisan('Flexcube:query-balance')
            ->expectsOutput('Hello world!')
            ->assertExitCode(0);
    }
}