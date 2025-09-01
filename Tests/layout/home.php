<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
</head>
<body>

    {{ $appName }}
    <!-- {{ $header['title'] }} -->
    
    @section('title', 'Homepage')
    
    <main>
        @yield('content')
    </main>

    @include('tests.layout.partials.footer', ['year2' => $header['year']])

</body>
</html>
