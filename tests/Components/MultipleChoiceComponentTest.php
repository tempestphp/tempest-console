<?php

declare(strict_types=1);

namespace Tests\Tempest\Console\Components;

use PHPUnit\Framework\TestCase;
use Tempest\Console\Components\MultipleChoiceComponent;

/**
 * @internal
 * @small
 */
class MultipleChoiceComponentTest extends TestCase
{
    public function test_multiple_choice_component(): void
    {
        $component = new MultipleChoiceComponent('Label', ['a', 'b', 'c']);

        $this->assertSame(
            <<<'TXT'
            <question>Label</question>
            > [ ]<em> a</em>
              [ ] b
              [ ] c
            
            Press <em>space</em> to select, press <em>enter</em> to confirm, press <em>ctrl+c</em> to cancel
            
            TXT,
            $component->render(),
        );

        $component->down();
        $this->assertStringContainsString('> [ ]<em> b</em>', $component->render());
        $this->assertStringContainsString('[ ] a', $component->render());

        $component->toggleSelected();
        $this->assertStringContainsString('> [x]<em> b</em>', $component->render());

        $component->toggleSelected();
        $this->assertStringContainsString('> [ ]<em> b</em>', $component->render());

        $component->up();
        $component->toggleSelected();
        $this->assertStringContainsString('> [x]<em> a</em>', $component->render());

        $component->down();
        $component->toggleSelected();

        $component->up();
        $component->up();
        $this->assertStringContainsString('> [ ]<em> c</em>', $component->render());

        $component->down();
        $this->assertStringContainsString('> [x]<em> a</em>', $component->render());

        $this->assertSame(['a', 'b'],  $component->enter());
    }
}