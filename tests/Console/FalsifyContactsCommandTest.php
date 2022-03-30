<?php

namespace Tests\Console;

use App\Contact;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test command 'falsify:contacts'.
 *
 * @see FalsifyContactsCommand
 */
class FalsifyContactsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const FACTORY_COUNT = 10;

    public function testExitWithSuccessCode()
    {
        Contact::factory(self::FACTORY_COUNT)->create();

        $appEnv = app()->environment();

        $this
            ->artisan('falsify:contacts')
            ->expectsQuestion("Do you wish to continue in {$appEnv} mode?", 'yes')
            ->assertExitCode(Command::SUCCESS);
    }
}
