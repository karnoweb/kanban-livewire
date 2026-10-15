# مرجع API

- `Kanban::init(): BoardBuilder`
- `BoardBuilder::key/board/pipeline/stages/cards/provider/handler/settings/setting/onMove/onClick/cardView/columnView/listView/build`
- `BoardRegistry::register(string $key, class-string $factory): void`
- `BoardRegistry::resolve(string $key, BoardContext $context): BoardDefinition`
- `DataProvider::stageCards(BoardContext, string $stageId, BoardQuery): PageResult`
- `ActionHandler::execute(ActionCommand, BoardContext): ActionResult`
- `ActionStatus`: success, validation_error, forbidden, not_found, conflict, wip_limit, domain_rejected, rate_limited, retryable_error, unknown_outcome, unsupported

کامپوننت Livewire: `<livewire:kanban-board board-key="..." />`