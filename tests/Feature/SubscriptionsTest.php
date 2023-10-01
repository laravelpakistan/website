<?php

use App\Jobs\CreateReply;
use App\Jobs\CreateThread;
use App\Models\Subscription;
use App\Models\Thread;
use App\Models\User;
use App\Notifications\NewReplyNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Feature\BrowserKitTestCase;

uses(BrowserKitTestCase::class);
uses(DatabaseMigrations::class);
uses(WithFaker::class);

test('users receive notifications for new replies to threads where they are subscribed to', function () {
    Notification::fake();

    $thread = Thread::factory()->create();
    [$author, $userOne, $userTwo] = User::factory()->times(3)->create();
    Subscription::factory()->create(['user_id' => $userOne->id(), 'subscriptionable_id' => $thread->id()]);
    Subscription::factory()->create(['user_id' => $userTwo->id(), 'subscriptionable_id' => $thread->id()]);

    $this->dispatch(new CreateReply(Str::uuid(), $this->faker->text(), $author, $thread));

    Notification::assertNotSentTo($author, NewReplyNotification::class);
    Notification::assertSentTo([$userOne, $userTwo], NewReplyNotification::class);
});

test('users are automatically subscribed to a thread after creating it', function () {
    $user = $this->createUser();

    $uuid = Str::uuid();

    $this->dispatch(new CreateThread($uuid, $this->faker->sentence(), $this->faker->text(), $user));

    $thread = Thread::findByUuidOrFail($uuid);

    expect($thread->hasSubscriber($user))->toBeTrue();
});

test('thread authors do not receive a notification for a thread they create', function () {
    Notification::fake();

    $author = $this->createUser();

    $this->dispatch(new CreateThread(Str::uuid(), $this->faker->sentence(), $this->faker->text(), $author));

    Notification::assertNotSentTo($author, NewReplyNotification::class);
});

test('reply authors do not receive a notification for a thread they are subscribed to', function () {
    Notification::fake();

    $thread = Thread::factory()->create();
    $author = User::factory()->create();
    Subscription::factory()->create(['user_id' => $author->id(), 'subscriptionable_id' => $thread->id()]);

    $this->dispatch(new CreateReply(Str::uuid(), $this->faker->text(), $author, $thread));

    Notification::assertNotSentTo($author, NewReplyNotification::class);
});

test('users are automatically subscribed to a thread after replying to it', function () {
    $user = $this->createUser();
    $thread = Thread::factory()->create();

    $this->dispatch(new CreateReply(Str::uuid(), $this->faker->text(), $user, $thread));

    expect($thread->hasSubscriber($user))->toBeTrue();
});

test('users can manually subscribe to threads', function () {
    Thread::factory()->create(['slug' => $slug = $this->faker->slug()]);

    $this->login();

    $this->visit("/forum/$slug")
        ->press('Subscribe')
        ->seePageIs("/forum/$slug")
        ->see("You're now subscribed to this thread.");
});

test('users can unsubscribe from threads', function () {
    $user = $this->createUser();
    $thread = Thread::factory()->create(['slug' => $slug = $this->faker->slug()]);
    Subscription::factory()->create(['user_id' => $user->id(), 'subscriptionable_id' => $thread->id()]);

    $this->loginAs($user);

    $this->visit("/forum/$slug")
        ->press('Unsubscribe')
        ->seePageIs("/forum/$slug")
        ->see("You're now unsubscribed from this thread.");
});

test('users can unsubscribe through a token link', function () {
    $subscription = Subscription::factory()->create();
    $thread = $subscription->subscriptionAble();

    $this->visit("/subscriptions/{$subscription->uuid()}/unsubscribe")
        ->seePageIs("/forum/{$thread->slug()}")
        ->see("You're now unsubscribed from this thread.");

    $this->notSeeInDatabase('subscriptions', ['uuid' => $subscription->uuid()]);
});

test('users are subscribed to a thread when mentioned', function () {
    $user = User::factory()->create(['username' => 'janedoe', 'email' => 'janedoe@example.com']);

    $this->login();

    $this->post('/forum/create-thread', [
        'subject' => 'How to work with Eloquent?',
        'body' => 'Hey @janedoe',
        'tags' => [],
    ]);

    $this->seeInDatabase('subscriptions', ['user_id' => $user->id()]);
});

test('users are subscribed to a thread when mentioned in a reply', function () {
    $user = User::factory()->create(['username' => 'janedoe', 'email' => 'janedoe@example.com']);
    $thread = Thread::factory()->create(['subject' => 'The first thread', 'slug' => 'the-first-thread']);

    $this->login();

    $this->post('/replies', [
        'body' => 'Hey @janedoe',
        'replyable_id' => $thread->id,
        'replyable_type' => Thread::TABLE,
    ]);

    $this->seeInDatabase('subscriptions', ['user_id' => $user->id()]);
});
