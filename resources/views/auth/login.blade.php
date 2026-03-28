<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ECA Conseils - Connexion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #667eea; font-size: 2em; margin-bottom: 10px; }
        .logo p { color: #666; font-size: 0.9em; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            border-left: 4px solid #c33;
        }
        .error-message.show { display: block; }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>ECA Conseils</h1>
            <p>Système de Gestion de Formation</p>
        </div>
        <div class="error-message" id="errorMessage"></div>
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn" id="submitBtn">Se connecter</button>
        </form>
    </div>
    <script>
        const token = localStorage.getItem('token');
        const userRaw = localStorage.getItem('user');
        if (token && userRaw) {
            try { redirectToDashboard(JSON.parse(userRaw).role); } catch (e) {}
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            const errorMessage = document.getElementById('errorMessage');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span>Connexion...';
            errorMessage.classList.remove('show');
            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    redirectToDashboard(data.user.role);
                } else {
                    errorMessage.textContent = data.error || data.message || 'Erreur de connexion';
                    errorMessage.classList.add('show');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Se connecter';
                }
            } catch (err) {
                errorMessage.textContent = 'Erreur de connexion au serveur';
                errorMessage.classList.add('show');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Se connecter';
            }
        });

        function redirectToDashboard(role) {
            switch (role) {
                case 'admin': window.location.href = '/admin/dashboard'; break;
                case 'trainer': window.location.href = '/trainer/dashboard'; break;
                case 'trainee': window.location.href = '/trainee/dashboard'; break;
                default: console.error('Rôle inconnu:', role);
            }
        }
    </script>
</body>
</html>
