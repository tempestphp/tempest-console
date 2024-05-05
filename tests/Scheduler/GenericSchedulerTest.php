<?php

declare(strict_types=1);

namespace Tests\Tempest\Console\Scheduler;

use DateTime;
use ReflectionMethod;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\Scheduler\Every;
use Tempest\Console\Scheduler\GenericScheduler;
use Tempest\Console\Scheduler\NullInvocationExecutor;
use Tempest\Console\Scheduler\Schedule;
use Tempest\Console\Scheduler\SchedulerConfig;
use Tests\Tempest\Console\TestCase;

/**
 * @internal
 * @small
 */
final class GenericSchedulerTest extends TestCase
{
    private int $pid = 1;

    protected function setUp(): void
    {
        parent::setUp();

        // @todo: clean this up once file system is mockable
        if (file_exists(GenericScheduler::CACHE_PATH)) {
            unlink(GenericScheduler::CACHE_PATH);
        }
    }

    public function test_scheduler_executes_handlers()
    {
        $at = new DateTime('2024-05-01 00:00:59');

        $executor = $this->createMock(NullInvocationExecutor::class);

        $executor->expects($this->once())
            ->method('execute')
            ->with($this->equalTo('(php tempest scheduler:invoke Tests\\\Tempest\\\Console\\\Scheduler\\\GenericSchedulerTest::handler) >> /dev/null &'));

        $config = new SchedulerConfig();
        $config->addHandlerInvocation(
            new ReflectionMethod($this, 'handler'),
            new Schedule(Every::MINUTE)
        );

        $scheduler = new GenericScheduler($config, $executor, $this->pid);
        $scheduler->run($at);
    }

    public function test_scheduler_executes_commands()
    {
        $at = new DateTime("2024-05-01 00:00:59");

        $executor = $this->createMock(NullInvocationExecutor::class);

        $executor->expects($this->once())
            ->method('execute')
            ->with($this->equalTo('(php tempest command) >> /dev/null &'));

        $config = new SchedulerConfig();
        $config->addCommandInvocation(
            new ReflectionMethod($this, 'command'),
            new ConsoleCommand('command'),
            new Schedule(Every::MINUTE)
        );

        $scheduler = new GenericScheduler($config, $executor, $this->pid);
        $scheduler->run($at);
    }

    /**
     * @slow
     */
    public function test_scheduler_only_dispatches_the_command_in_desired_times()
    {
        $at = new DateTime('2024-05-01 00:00:59');

        $executor = $this->createMock(NullInvocationExecutor::class);

        $executor->expects($this->once())
            ->method('execute')
            ->with($this->equalTo('(php tempest scheduler:invoke Tests\\\Tempest\\\Console\\\Scheduler\\\GenericSchedulerTest::handler) >> /dev/null &'));

        $config = new SchedulerConfig();
        $config->addHandlerInvocation(
            new ReflectionMethod($this, 'handler'),
            new Schedule(Every::MINUTE)
        );

        $scheduler = new GenericScheduler($config, $executor, $this->pid);
        $scheduler->run($at);

        // command won't run twice in a row
        $scheduler->run($at);

        $newAt = $at->modify('+60 seconds');

        $executor = $this->createMock(NullInvocationExecutor::class);

        $executor->expects($this->once())
            ->method('execute')
            ->with($this->equalTo('(php tempest scheduler:invoke Tests\\\Tempest\\\Console\\\Scheduler\\\GenericSchedulerTest::handler) >> /dev/null &'));

        $scheduler = new GenericScheduler($config, $executor, $this->pid + 1);
        $scheduler->run($newAt);
    }

    /**
     * @slow
     */
    public function test_sub_minute_scheduler_will_only_run_to_the_next_minute_with_a_long_sleep()
    {
        $at = new DateTime('2024-05-01 00:00:57');

        $executor = $this->createMock(NullInvocationExecutor::class);

        $executor->expects($this->exactly(3))
            ->method('execute')
            ->with($this->equalTo('(php tempest scheduler:invoke Tests\\\Tempest\\\Console\\\Scheduler\\\GenericSchedulerTest::handler) >> /dev/null &'));

        $config = new SchedulerConfig();
        $config->addHandlerInvocation(
            new ReflectionMethod($this, 'handler'),
            new Schedule(Every::SECOND)
        );

        $scheduler = new GenericScheduler($config, $executor, $this->pid);

        $scheduler->run($at);
    }

    // dummy handler for testing
    public function handler(): void
    {
    }

    // dummy command for testing
    public function command(): void
    {
    }
}
