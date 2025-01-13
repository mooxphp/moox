<form action="{{ route('language.switch') }}" method="post">
    @csrf
    <select name="locale" onchange="this.form.submit()">
        @foreach ($locales as $locale)
            <option value="{{ $locale->locale }}">{{ $locale->name }}</option>
        @endforeach
    </select>
    <button type="submit">Switch Language</button>
</form>
