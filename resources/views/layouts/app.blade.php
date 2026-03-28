<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ECA Conseils')</title>
    @stack('head')
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; }
        .navbar {
            background: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a { color: white; text-decoration: none; margin-left: 16px; font-weight: 500; }
        .navbar a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <strong>ECA Conseils</strong>
        <div>
            @yield('nav_links')
            <a href="#" onclick="logout(); return false;">Déconnexion</a>
        </div>
    </nav>
    <div class="container">
        @yield('content')
    </div>
    <script>
        function logout() {
            const token = localStorage.getItem('token');
            if (token) {
                fetch('/api/logout', { method: 'POST', headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' } }).catch(() => {});
            }
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/';
        }
    </script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>
