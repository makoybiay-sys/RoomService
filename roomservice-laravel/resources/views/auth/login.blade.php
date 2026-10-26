<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Login — BLULANE</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body class="login-body" style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
    <div class="login-card" style="background:var(--card);padding:40px;border-radius:20px;width:100%;max-width:400px;text-align:center;">
        <h2>Staff Login</h2>
        @if($errors->any())
            <div style="color:red; margin-bottom:10px;">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="/login" style="text-align:left;">
            @csrf
            <div style="margin-bottom:15px;">
                <label>Username</label>
                <input type="text" name="username" required style="width:100%;padding:10px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Password</label>
                <input type="password" name="password" required style="width:100%;padding:10px;">
            </div>
            <button type="submit" style="width:100%;padding:12px;background:var(--primary);color:#fff;border:none;border-radius:8px;">Sign In</button>
        </form>
    </div>
</body>
</html>