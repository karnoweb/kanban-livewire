<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Unit;

use Karnoweb\Kanban\Contracts\ActionHandler;
use Karnoweb\Kanban\Contracts\DataProvider;
use Karnoweb\Kanban\Data\ActionCommand;
use Karnoweb\Kanban\Data\ActionResult;
use Karnoweb\Kanban\Data\BoardContext;
use Karnoweb\Kanban\Data\BoardData;
use Karnoweb\Kanban\Data\BoardQuery;
use Karnoweb\Kanban\Data\CardData;
use Karnoweb\Kanban\Data\CountResult;
use Karnoweb\Kanban\Data\PageResult;
use Karnoweb\Kanban\Exceptions\InvalidBoardDefinition;
use Karnoweb\Kanban\Facades\Kanban;
use Karnoweb\Kanban\Support\BoardBuilder;
use Karnoweb\Kanban\Tests\TestCase;

final class BuilderContractTest extends TestCase
{
    public function test_each_init_returns_a_new_builder(): void
    {
        $this->assertNotSame(Kanban::init(), Kanban::init());
    }

    public function test_pipeline_and_setting_aliases_match(): void
    {
        $board = new BoardData('crm', 'CRM', 'tenant-a');
        $left = (new BoardBuilder)->pipeline($board)->setting(['interaction' => ['transition_policy' => 'allow']]);
        $right = (new BoardBuilder)->board($board)->settings(['interaction' => ['transition_policy' => 'allow']]);
        $builtLeft = $left->provider(ReadProvider::class)->handler(NoHandler::class)->key('crm')->build();
        $builtRight = $right->provider(ReadProvider::class)->handler(NoHandler::class)->key('crm')->build();

        $this->assertSame($builtLeft->board->title, $builtRight->board->title);
        $this->assertSame($builtLeft->settings->interaction->transitionPolicy, $builtRight->settings->interaction->transitionPolicy);
    }

    public function test_incomplete_definition_fails_closed(): void
    {
        $this->expectException(InvalidBoardDefinition::class);
        (new BoardBuilder)->key('crm')->build();
    }

    public function test_closure_is_rejected(): void
    {
        $this->expectException(InvalidBoardDefinition::class);
        (new BoardBuilder)->key('crm')->onMove(static fn (): null => null);
    }

    public function test_definition_is_immutable_after_build(): void
    {
        $builder = (new BoardBuilder)
            ->key('crm')
            ->board(new BoardData('crm', 'CRM', 'tenant-a'))
            ->provider(ReadProvider::class)
            ->handler(NoHandler::class);
        $definition = $builder->build();
        $builder->key('other');

        $this->assertSame('crm', $definition->key);
    }
}

final class ReadProvider implements DataProvider
{
    public function board(BoardContext $context): BoardData
    {
        return new BoardData('crm', 'CRM', $context->scopeKey);
    }

    public function stages(BoardContext $context, BoardQuery $query): array
    {
        return [];
    }

    public function stageCards(BoardContext $context, string $stageId, BoardQuery $query): PageResult
    {
        return new PageResult([], null, false, 0, true, $query->hash());
    }

    public function listCards(BoardContext $context, BoardQuery $query): PageResult
    {
        return new PageResult([], null, false, 0, true, $query->hash());
    }

    public function counts(BoardContext $context, BoardQuery $query): CountResult
    {
        return new CountResult([]);
    }

    public function card(BoardContext $context, string $cardId): ?CardData
    {
        return null;
    }

    public function archives(BoardContext $context, BoardQuery $query): PageResult
    {
        return new PageResult([], null, false, 0);
    }

    public function capabilities(BoardContext $context): array
    {
        return [];
    }

    public function operationStatus(BoardContext $context, string $operationId): ?ActionResult
    {
        return null;
    }
}

final class NoHandler implements ActionHandler
{
    public function execute(ActionCommand $command, BoardContext $context): ActionResult
    {
        return ActionResult::unsupported();
    }
}
