<?php

use App\Http\Livewire\LikeReply;
use App\Http\Livewire\LikeThread;
use App\Mail\MentionEmail;
use App\Models\Reply;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Notifications\MentionNotification;
use App\Notifications\ThreadDeletedNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Feature\BrowserKitTestCase;

uses(BrowserKitTestCase::class);
uses(DatabaseMigrations::class);

test('users can see a list of latest threads', function () {
    Thread::factory()->create(['subject' => 'The first thread']);
    Thread::factory()->create(['subject' => 'The second thread']);

    $this->visit('/forum')
        ->see('The first thread')
        ->see('The second thread');
});

test('users can see when a thread is resolved', function () {
    Thread::factory()->create(['subject' => 'The first thread']);
    $thread = Thread::factory()->create(['subject' => 'The second thread']);
    $reply = Reply::factory()->create();
    $thread->solutionReplyRelation()->associate($reply)->save();

    $this->visit('/forum')
        ->see('The first thread')
        ->see('The second thread')
        ->see('Resolved')
        ->see(route('thread', $thread->slug()).'#'.$thread->solution_reply_id);
});

test('users can see a single thread', function () {
    Thread::factory()->create([
        'subject' => 'The first thread',
        'slug' => 'the-first-thread',
    ]);

    $this->visit('/forum/the-first-thread')
        ->see('The first thread');
});

test('users cannot create a thread when not logged in', function () {
    $this->visit('/forum/create-thread')
        ->seePageIs('/login');
});

test('the thread subject cannot be an url', function () {
    $tag = Tag::factory()->create(['name' => 'Test Tag']);

    $this->login();

    $this->post('/forum/create-thread', [
        'subject' => 'http://example.com Foo title',
        'body' => 'This text explains how to work with Eloquent.',
        'tags' => [$tag->id()],
    ])
        ->assertSessionHasErrors(['subject' => 'The subject field cannot contain an url.']);
});

test('users can create a thread', function () {
    $tag = Tag::factory()->create(['name' => 'Test Tag']);

    $this->login();

    $this->post('/forum/create-thread', [
        'subject' => 'How to work with Eloquent?',
        'body' => 'This text explains how to work with Eloquent.',
        'tags' => [$tag->id()],
    ])
        ->assertRedirectedTo('/forum/how-to-work-with-eloquent')
        ->assertSessionHas('success', 'Thread successfully created!');
});

test('users can edit a thread', function () {
    $user = $this->createUser();
    $tag = Tag::factory()->create(['name' => 'Test Tag']);
    Thread::factory()->create([
        'author_id' => $user->id(),
        'slug' => 'my-first-thread',
    ]);

    $this->loginAs($user);

    $this->put('/forum/my-first-thread', [
        'subject' => 'How to work with Eloquent?',
        'body' => 'This text explains how to work with Eloquent.',
        'tags' => [$tag->id()],
    ])
        ->assertRedirectedTo('/forum/how-to-work-with-eloquent')
        ->assertSessionHas('success', 'Thread successfully updated!');
});

test('users cannot edit a thread they do not own', function () {
    Thread::factory()->create(['slug' => 'my-first-thread']);

    $this->login();

    $this->get('/forum/my-first-thread/edit')
        ->assertForbidden();
});

test('users can delete their own thread', function () {
    $thread = Thread::factory()->create(['slug' => 'my-first-thread']);

    $this->loginAs($thread->author());

    $this->delete('/forum/my-first-thread')
        ->assertRedirectedTo('/forum')
        ->assertSessionHas('success', 'Thread successfully deleted!');
});

test('users cannot delete a thread they do not own', function () {
    Thread::factory()->create(['slug' => 'my-first-thread']);

    $this->login();

    $this->delete('/forum/my-first-thread')
        ->assertForbidden();
});

test('moderators can give a reason when deleting threads', function () {
    $thread = Thread::factory()->create(['slug' => 'my-first-thread']);
    $moderator = User::factory()->moderator()->create();

    $this->loginAs($moderator);

    Notification::fake();

    $this->delete('/forum/my-first-thread', ['reason' => 'Please do not spam.'])
        ->assertRedirectedTo('/forum')
        ->assertSessionHas('success', 'Thread successfully deleted!');

    Notification::assertSentTo($thread->author(), ThreadDeletedNotification::class);
});

test('users cannot create a thread with a subject that is too long', function () {
    $tag = Tag::factory()->create(['name' => 'Test Tag']);

    $this->login();

    $response = $this->post('/forum/create-thread', [
        'subject' => 'How to make Eloquent, Doctrine, Entities and Annotations work together in Laravel?',
        'body' => 'This is a thread with 82 characters in the subject',
        'tags' => [$tag->id()],
    ]);

    $response->assertSessionHas('error', 'Something went wrong. Please review the fields below.');
    $response->assertSessionHasErrors(['subject' => 'The subject must not be greater than 60 characters.']);
});

test('users cannot edit a thread with a subject that is too long', function () {
    $user = $this->createUser();
    $tag = Tag::factory()->create(['name' => 'Test Tag']);
    Thread::factory()->create([
        'author_id' => $user->id(),
        'slug' => 'my-first-thread',
    ]);

    $this->loginAs($user);

    $response = $this->put('/forum/my-first-thread', [
        'subject' => 'How to make Eloquent, Doctrine, Entities and Annotations work together in Laravel?',
        'body' => 'This is a thread with 82 characters in the subject',
        'tags' => [$tag->id()],
    ]);

    $response->assertSessionHas('error', 'Something went wrong. Please review the fields below.');
    $response->assertSessionHasErrors(['subject' => 'The subject must not be greater than 60 characters.']);
});

test('a user can toggle a like on a thread', function () {
    $this->login();

    $thread = Thread::factory()->create();

    Livewire::test(LikeThread::class, ['thread' => $thread])
        ->assertSee("0\n")
        ->call('toggleLike')
        ->assertSee("1\n")
        ->call('toggleLike')
        ->assertSee("0\n");
});

test('a logged out user cannot toggle a like on a thread', function () {
    $thread = Thread::factory()->create();

    Livewire::test(LikeThread::class, ['thread' => $thread])
        ->assertSee("0\n")
        ->call('toggleLike')
        ->assertSee("0\n");
});

test('a user can toggle a like on a reply', function () {
    $this->login();

    $reply = Reply::factory()->create();

    Livewire::test(LikeReply::class, ['reply' => $reply])
        ->assertSee("0\n")
        ->call('toggleLike')
        ->assertSee("1\n")
        ->call('toggleLike')
        ->assertSee("0\n");
});

test('a logged out user cannot toggle a like on a reply', function () {
    $reply = Reply::factory()->create();

    Livewire::test(LikeReply::class, ['reply' => $reply])
        ->assertSee("0\n")
        ->call('toggleLike')
        ->assertSee("0\n");
});

test('user can see standalone links in reply', function () {
    $thread = Thread::factory()->create(['slug' => 'the-first-thread']);
    Reply::factory()->create([
        'body' => 'https://github.com/laravelio/laravel.io check this cool project',
        'replyable_id' => $thread->id(),
    ]);

    $this->visit("/forum/{$thread->slug}")
        ->see('<a href="https://github.com/laravelio/laravel.io" rel="nofollow" target="_blank">https://github.com/laravelio/laravel.io</a>');
});

test('user can see standalone links in thread', function () {
    $thread = Thread::factory()->create([
        'slug' => 'the-first-thread',
        'body' => 'https://github.com/laravelio/laravel.io check this cool project',
    ]);
    Reply::factory()->create(['replyable_id' => $thread->id()]);

    $this->visit("/forum/{$thread->slug()}")
        ->see('<a href=\&quot;https:\/\/github.com\/laravelio\/laravel.io\&quot; rel=\&quot;nofollow\&quot; target=\&quot;_blank\&quot;>https:\/\/github.com\/laravelio\/laravel.io<\/a>');
});

test('an invalid filter defaults to the most recent threads', function () {
    Thread::factory()->create(['subject' => 'The first thread']);
    Thread::factory()->create(['subject' => 'The second thread']);

    $this->visit('/forum?filter=something-invalid')
        ->see('href="http://localhost/forum?filter=recent" aria-current="page"');
});

test('an invalid filter on tag view defaults to the most recent threads', function () {
    $tag = Tag::factory()->create();

    $this->visit("/forum/tags/{$tag->slug}?filter=something-invalid")
        ->see('href="http://localhost/forum/tags/'.$tag->slug.'?filter=recent" aria-current="page"');
});

test('thread activity is set when a new thread is created', function () {
    $this->login();

    $this->post('/forum/create-thread', [
        'subject' => 'How to work with Eloquent?',
        'body' => 'This text explains how to work with Eloquent.',
        'tags' => [],
    ]);

    $this->assertNotNull(Thread::latest('id')->first()->last_activity_at);
});

test('users are notified by email when mentioned in a thread body', function () {
    Notification::fake();
    $user = User::factory()->create(['username' => 'janedoe', 'email' => 'janedoe@example.com']);

    $this->login();

    $this->post('/forum/create-thread', [
        'subject' => 'How to work with Eloquent?',
        'body' => 'Hey @janedoe',
        'tags' => [],
    ]);

    Notification::assertSentTo($user, MentionNotification::class, function ($notification) use ($user) {
        return $notification->toMail($user) instanceof MentionEmail;
    });
});

test('users provided with a UI notification when mentioned in a thread body', function () {
    $user = User::factory()->create(['username' => 'janedoe']);

    $this->login();

    $this->post('/forum/create-thread', [
        'subject' => 'How to work with Eloquent?',
        'body' => 'Hey @janedoe',
        'tags' => [],
    ]);

    $notification = DatabaseNotification::first();
    $this->assertSame($user->id, (int) $notification->notifiable_id);
    $this->assertSame('users', $notification->notifiable_type);
    $this->assertSame('mention', $notification->data['type']);
    $this->assertSame('How to work with Eloquent?', $notification->data['replyable_subject']);
});

test('users are not notified when mentioned in and edited thread', function () {
    Notification::fake();
    $user = $this->createUser();
    $tag = Tag::factory()->create(['name' => 'Test Tag']);
    Thread::factory()->create([
        'author_id' => $user->id(),
        'slug' => 'my-first-thread',
    ]);

    $this->loginAs($user);

    $this->put('/forum/my-first-thread', [
        'subject' => 'How to work with Eloquent?',
        'body' => 'This text explains how to work with Eloquent.',
        'tags' => [$tag->id()],
    ]);

    Notification::assertNothingSent();
});

test('cannot fake a mention', function () {
    $this->login();

    $response = $this->post('/forum/create-thread', [
        'subject' => 'How to work with Eloquent?',
        'body' => 'Hey [@joedixon](https://somethingnasty.com)',
        'tags' => [],
    ]);

    $response->assertSessionHas('error', 'Something went wrong. Please review the fields below.');
    $response->assertSessionHasErrors(['body' => 'The body field contains an invalid mention.']);
});
