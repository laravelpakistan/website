<?php

use App\Jobs\UpdateArticle;
use App\Models\Article;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

uses(TestCase::class);
uses(DatabaseMigrations::class);

test('we can update an article', function () {
    $user = $this->createUser();
    $article = Article::factory()->create(['author_id' => $user->id()]);

    $this->dispatch(new UpdateArticle($article, 'Title', 'Body', false));

    $article = $article->fresh();

    expect($article->title())->toEqual('Title');
    expect($article->body())->toEqual('Body');
});

test('we can submit an existing article for approval', function () {
    $user = $this->createUser();
    $article = Article::factory()->create(['author_id' => $user->id()]);

    $this->dispatch(new UpdateArticle($article, 'Title', 'Body', true));

    $article = $article->fresh();

    $this->assertNotNull($article->submittedAt());
});

test('we cannot update the submission time when saving changes', function () {
    $user = $this->createUser();
    $article = Article::factory()->create(['author_id' => $user->id(), 'submitted_at' => '2020-06-20 00:00:00']);

    $this->dispatch(new UpdateArticle($article, 'Title', 'Body', false));

    $article = $article->fresh();

    expect($article->submittedAt()->format('Y-m-d H:i:s'))->toBe('2020-06-20 00:00:00');
    expect($article->isNotPublished())->toBeTrue();
});
