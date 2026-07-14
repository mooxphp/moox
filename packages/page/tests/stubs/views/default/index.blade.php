<!DOCTYPE html>
<html>
<body>
<ul>
@foreach ($pages as $page)
    <li>{{ $page->translations->first()?->slug }}</li>
@endforeach
</ul>
</body>
</html>
