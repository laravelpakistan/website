@component('mail::message')

Great news! Your article: **{{ $article->title() }}** has been approved and is now live on Laravel Pakistan.

@component('mail::button', ['url' => route('articles.show', $article->slug())])
View Article
@endcomponent

@endcomponent
