<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>The wall</title>
    <link rel="stylesheet" href="/wall.css">
</head>
<body>
    <h1>The wall</h1>

    @if (session('held'))
        <p class="notice">Thanks. Your comment is waiting for a moderator.</p>
    @endif

    <form method="post" action="{{ route('comments.store') }}">
        @csrf
        <label>Name <input name="name" value="{{ old('name') }}" required></label>
        <label>Comment <textarea name="body" rows="3" required>{{ old('body') }}</textarea></label>
        @error('body') <p class="error">{{ $message }}</p> @enderror
        @error('name') <p class="error">{{ $message }}</p> @enderror
        <button type="submit">Post</button>
    </form>

    @foreach ($comments as $comment)
        <article>
            <strong>{{ $comment['name'] }}</strong>
            <p>{{ $comment['body'] }}</p>
        </article>
    @endforeach
</body>
</html>
