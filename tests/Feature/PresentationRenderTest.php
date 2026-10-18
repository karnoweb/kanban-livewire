<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Tests\Feature;

use Karnoweb\Kanban\Tests\TestCase;

final class PresentationRenderTest extends TestCase
{
    private function card(array $overrides = []): array
    {
        return array_replace(['id' => 'one', 'stageId' => 'todo', 'title' => 'A task', 'metadata' => []], $overrides);
    }

    private function renderCard(array $card): string
    {
        return view('kanban::components.card', [
            'card' => $card, 'selectedIds' => [], 'instanceId' => 'test', 'boardKey' => 'tasks',
            'features' => (object) ['bulk' => true, 'dragDrop' => false, 'customActions' => false, 'details' => false, 'updateCard' => false, 'archiveCard' => false],
            'reorderAllowed' => false, 'columns' => [],
        ])->render();
    }

    public function test_minimal_card_has_no_empty_metadata_sections(): void
    {
        $html = $this->renderCard($this->card());
        self::assertStringContainsString('A task', $html);
        foreach (['card-description', 'card-badges', 'card-attachments', 'card-footer'] as $part) {
            self::assertStringNotContainsString('data-kb-part="'.$part.'"', $html);
        }
    }

    public function test_untrusted_title_and_description_are_escaped(): void
    {
        $html = $this->renderCard($this->card(['title' => '<img src=x onerror="alert(1)">', 'description' => '<script>alert(2)</script>']));
        self::assertStringNotContainsString('<img src=x', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;img', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_overflow_labels_remain_accessible_and_progress_is_bounded(): void
    {
        $html = $this->renderCard($this->card([
            'badges' => array_map(fn ($n) => ['text' => 'Label '.$n], range(1, 5)),
            'metadata' => ['progress' => 175, 'points' => 0, 'checklistTotal' => 3, 'checklistDone' => 3],
        ]));
        self::assertSame(3, substr_count($html, 'data-kb-part="card-badge"'));
        self::assertStringContainsString('+2', $html);
        self::assertStringContainsString('Label 4, Label 5', $html);
        self::assertStringContainsString('aria-valuenow="100"', $html);
        self::assertStringContainsString('0 pt', $html);
        self::assertStringContainsString('is-complete', $html);
    }
    public function test_card_uses_the_first_image_attachment_as_cover(): void
    {
        $html = $this->renderCard($this->card(['attachments' => [
            ['id' => 'pdf', 'name' => 'Notes', 'mime' => 'application/pdf', 'url' => '/notes.pdf'],
            ['id' => 'photo', 'name' => 'Preview', 'mime' => 'image/png', 'url' => '/first.png'],
            ['id' => 'other', 'name' => 'Other', 'mime' => 'image/jpeg', 'url' => '/second.jpg'],
        ]]));
        self::assertSame(1, substr_count($html, 'data-kb-part="card-cover"'));
        self::assertStringContainsString('src="/first.png"', $html);
        self::assertStringNotContainsString('src="/second.jpg"', $html);
    }

    public function test_generic_upload_mime_uses_image_extension_and_empty_thumbnail_falls_back(): void
    {
        $html = $this->renderCard($this->card(['attachments' => [
            ['id' => 'file', 'name' => 'Upload', 'mime' => 'application/octet-stream', 'url' => '/uploads/photo.PNG?token=example', 'thumbnailUrl' => ''],
        ]]));
        self::assertStringContainsString('data-kb-part="card-cover"', $html);
        self::assertStringContainsString('src="/uploads/photo.PNG?token=example"', $html);
    }

    public function test_member_overflow_keeps_three_avatars_and_escapes_remaining_names(): void
    {
        $html = view('kanban::components.card-assignees', ['card' => $this->card(['assignees' => [
            ['name' => 'Alice'], ['name' => 'Bob'], ['name' => 'Sara'], ['name' => '<img src=x>'], ['name' => 'Mina'],
        ]])])->render();
        self::assertSame(3, substr_count($html, 'data-kb-part="card-assignee"'));
        self::assertStringContainsString('+2', $html);
        self::assertStringContainsString('assignee-overflow-panel', $html);
        self::assertStringContainsString('&lt;img src=x&gt;', $html);
        self::assertStringNotContainsString('<img src=x>', $html);
    }

    public function test_detail_shows_available_properties_tasks_comments_and_files_safely(): void
    {
        $detail = $this->card([
            'description' => 'Description', 'dueAt' => '2026-12-01',
            'attachments' => [['name' => 'Notes', 'url' => '/notes.pdf']],
            'checklist' => [['title' => '<script>bad</script>', 'done' => true]], 'comments' => [['body' => '<img src=x>', 'author' => 'Alice']],
        ]);
        $html = view('kanban::components.card-detail-content', ['detail' => $detail, 'columns' => [['id' => 'todo', 'title' => 'To do']]])->render();
        self::assertStringContainsString('2026-12-01', $html);
        self::assertStringContainsString('details-checklist', $html);
        self::assertStringContainsString('details-comments', $html);
        self::assertStringContainsString('Notes', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;img src=x&gt;', $html);
    }

}
