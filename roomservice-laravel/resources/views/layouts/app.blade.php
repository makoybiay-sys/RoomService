<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BLULANE')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/uploads/image-logo/BLULANE.png') }}">
    
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/staff.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    
    @yield('styles')
</head>
<body class="@yield('body-class')">

    @yield('content')

    <script src="{{ asset('assets/js/theme.js') }}"></script>
    @yield('scripts')
</body>
</html>