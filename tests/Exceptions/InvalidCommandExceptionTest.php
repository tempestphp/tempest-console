<?php

declare(strict_types=1);

namespace Tests\Tempest\Console\Exceptions;

use Tests\Tempest\Console\TestCase;

/**
 * @internal
 * @small
 */
class InvalidCommandExceptionTest extends TestCase
{
    public function test_console_exception_handler(): void
    {
        $this->console
            ->call('complex')
            ->assertContains('Invalid command usage:')
            ->assertContains('complex <a> <b> <c>')
            ->assertContains('Missing arguments: a, b, c');
    }

    public function test_console_exception_handler_with_partial_arguments(): void
    {
        $this->console
            ->call('complex a')
            ->assertContains('Missing arguments: b, c');
    }

    public function test_console_exception_handler_with_all_arguments(): void
    {
        $this->console
            ->call('complex a b c')
            ->assertDoesNotContain('Invalid command usage')
            ->assertDoesNotContain('Missing');
    }

    public function test_it_will_prompt_for_missing_arguments()
    {
        $this->console
            ->call('auth:command')
            ->assertContains('Missing arguments: auth, token');
    }
}
