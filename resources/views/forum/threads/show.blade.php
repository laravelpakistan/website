@title($thread->subject())
@canonical(route('thread', $thread->slug()))

@extends('layouts.default', ['hasShadow' => true])

@section('subnav')
    <section class="container flex justify-between mx-auto bg-white pb-4 px-4 lg:pb-10">
        <h1 class="flex items-center gap-x-3.5 text-xl font-semibold lg:text-3xl">
            <a href="{{ route('forum') }}" class="text-gray-400 hover:underline">Forum</a>
            <x-heroicon-o-chevron-right class="w-6 h-6" />
            <span class="break-all">{{ $title }}</span>

            @if ($thread->isLocked())
                <x-heroicon-o-lock-closed class="w-5 h-5" />
            @endif
        </h1>
    </section>
@endsection

@section('content')
    <section class="pt-5 pb-10 px-4 container mx-auto flex flex-col gap-x-12 lg:flex-row lg:pt-10 lg:pb-0">
        <div class="w-full lg:w-3/4">
            @auth
                @if (! $thread->isSolved() && $thread->isAuthoredBy(Auth::user()))
                    <x-primary-info-panel icon="heroicon-o-check-badge">
                        Please make sure to mark the correct reply as the solution when your question gets answered.
                    </x-primary-info-panel>
                @endif
            @endauth


            <div class="relative">
                <div class="relative flex flex-col gap-y-4 z-20">
                    <x-ads.top-text />

                    <x-threads.thread :thread="$thread" />

                    @foreach ($thread->repliesWithTrashed() as $reply)
                        <x-threads.reply :thread="$thread" :reply="$reply" />
                    @endforeach
                </div>

                <div class="absolute h-full border-l border-lio-500 ml-8 z-10 inset-y-0 left-0 lg:ml-16"></div>
            </div>

            @if ($thread->isLocked())
                <x-info-panel class="flex justify-between gap-x-16">
                    <p class="text-sm">
                        @php($lockedBy = $thread->lockedBy())

                        This thread was locked by

                        <a
                            class="text-lio-500 hover:text-lio-600"
                            href="{{ route('profile', $lockedBy->username()) }}"
                        >{{ $lockedBy->name() }}</a>.
                    </p>
                </x-info-panel>
            @endif

            @can(App\Policies\ReplyPolicy::CREATE, App\Models\Reply::class)
                @if ($thread->isUnlocked() || Auth::user()->isModerator() || Auth::user()->isAdmin())
                    @if ($thread->isConversationOld())
                        <x-info-panel class="flex justify-between gap-x-16">
                            <p>The last reply to this thread was more than six months ago. Please consider opening a new thread if you have a similar question.</p>

                            <x-buttons.arrow-button href="{{ route('threads.create') }}" class="shrink-0">
                                Create thread
                            </x-buttons.arrow-button>
                        </x-info-panel>
                    @else
                        <div class="my-8">
                            <form
                                action="{{ route('replies.store') }}"
                                method="POST"
                                @submitted="$event.currentTarget.submit()"
                            >
                                @csrf

                                <livewire:editor
                                    hasMentions
                                    hasButton
                                    buttonLabel="Reply"
                                    buttonIcon="send"
                                    label="Write a reply"
                                    :participants="$thread->participants()"
                                />

                                @error('body')

                                <input type="hidden" name="replyable_id" value="{{ $thread->id() }}" />

                                <input type="hidden" name="replyable_type" value="threads" />

                                <div class="flex justify-between items-start mt-4 gap-x-8 lg:items-center">
                                    <p>
                                        Please make sure you've read our <a href="{{ route('rules') }}" class="text-lio-500 border-b-2 pb-0.5 border-lio-100 hover:text-lio-600">rules</a> before replying to this thread.
                                    </p>
                                </div>
                            </form>
                        </div>
                    @endif
                @endif
            @else
                @guest
                    <p class="text-center py-8">
                        <a href="{{ route('login') }}" class="text-lio-500 border-b-2 pb-0.5 border-lio-100 hover:text-lio-600">Sign in</a> to participate in this thread!
                    </p>
                @else
                    <x-info-panel>
                        <p>You'll need to verify your account before participating in this thread.</p>

                        <form action="{{ route('verification.resend') }}" method="POST" class="block">
                            @csrf
                            <x-buttons.arrow-button type="submit" class="shrink-0">
                                Click here to resend the verification link.
                            </x-buttons.arrow-button>
                        </form>
                    </x-info-panel>
                @endguest
            @endcan
        </div>

        <div class="w-full lg:w-1/4">
            @include('layouts._ads._forum_sidebar')

            <div class="mt-6">
                <x-users.profile-block :user="$thread->author()" />
            </div>

            @auth
                <div class="mt-6">
                    <x-threads.subscribe :thread="$thread" />
                </div>
            @endauth

            <div class="my-6">
                <x-moderators :moderators="$moderators" />
            </div>
        </div>
    </section>
@endsection
