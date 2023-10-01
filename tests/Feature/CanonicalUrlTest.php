<?php

use App\Models\Article;
use App\Models\Tag;
use App\Models\Thread;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\App;
use Tests\Feature\BrowserKitTestCase;

uses(BrowserKitTestCase::class);
uses(DatabaseMigrations::class);

function inProduction()
{
    App::detectEnvironment(fn () => 'production');
}

afterEach(fn () => App::detectEnvironment(fn () => 'testing'));

test('pages without a canonical url explicitly set fall back to the current url', function () {
    $this->get('/register')
        ->see('<link rel="canonical" href="http://localhost/register" />');
});

test('pages with a canonical url are rendered correctly', function () {
    $thread = Thread::factory()->create(['subject' => 'The first thread']);

    $this->get("forum/{$thread->slug()}")
        ->see('<link rel="canonical" href="http://localhost/forum/'.$thread->slug().'" />');
});

test('first page of paginated list removes page=1 from canonical url', function () {
    $this->get('forum?page=1')
        ->see('<link rel="canonical" href="http://localhost/forum?filter=recent" />');
});

test('subsequent pages of paginated list sets the full url as canonical', function () {
    $this->get('forum?page=2')
        ->see('<link rel="canonical" href="http://localhost/forum?filter=recent&amp;page=2" />');
});

test('allowed params are included in the canonical url', function () {
    $this->get('forum?filter=recent&page=1')
        ->see('<link rel="canonical" href="http://localhost/forum?filter=recent" />');
});

test('non allowed params are not included in the canonical url', function () {
    $this->get('forum?filter=recent&utm_source=twitter&utm_medium=social&utm_term=abc123&page=2')
        ->see('<link rel="canonical" href="http://localhost/forum?filter=recent&amp;page=2" />');
});

test('query_params_are_always_in_the_same_order', function () {
    Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

    $this->get('articles?utm_source=twitter&utm_medium=social&utm_term=abc123&filter=trending&page=2&tag=laravel')
        ->see('<link rel="canonical" href="http://localhost/articles?filter=trending&amp;page=2&amp;tag=laravel" />');
});

test('standard pages always remove query params from canonical url', function () {
    $this->get('?utm_source=twitter&utm_medium=social&utm_term=abc123')
        ->see('<link rel="canonical" href="http://localhost" />');
});

test('canonical tracking is turned off when using external url', function () {
    Article::factory()->create(['slug' => 'my-first-article', 'submitted_at' => now(), 'approved_at' => now(), 'original_url' => 'https://example.com/external-path']);

    $this->get('/articles/my-first-article')
        ->see('data-canonical="false"');
})->inProduction();

test('canonical tracking is turned on when using external url', function () {
    App::detectEnvironment(fn () => 'production');

    Article::factory()->create(['slug' => 'my-first-article', 'submitted_at' => now(), 'approved_at' => now()]);

    $this->get('/articles/my-first-article')
        ->dontSee('data-canonical="false"');
})->inProduction();
