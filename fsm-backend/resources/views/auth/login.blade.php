<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ISP FSM Portal</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <!-- Inline Styles for guaranteed loading without asset path issues -->
    <style>
        :root {
            --primary: #0fffe0; /* neon cyan */
            --accent: #ff00ff; /* neon magenta */
            --bg-dark: #0a0a0a;
            --bg-gradient: radial-gradient(circle at 30% 30%, #001f3f, #0a0a0a);
            --card-bg: rgba(255, 255, 255, 0.08);
            --card-blur: 12px;
            --text-primary: #e0e0e0;
            --text-muted: #a0a0a0;
            --border-radius: 12px;
        }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            max-width: 420px;
            width: 100%;
            background: var(--card-bg);
            backdrop-filter: blur(var(--card-blur));
            -webkit-backdrop-filter: blur(var(--card-blur));
            border-radius: var(--border-radius);
            padding: 2.5rem 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.6);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            background: rgba(15, 255, 224, 0.1);
            border: 1px solid var(--primary);
            box-shadow: 0 0 15px rgba(15, 255, 224, 0.4);
            margin-bottom: 1rem;
        }

        .logo-icon-wrapper ion-icon {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .login-header h2 {
            color: var(--primary);
            font-size: 1.75rem;
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 0 10px rgba(15, 255, 224, 0.5);
        }
        .login-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            margin-bottom: 0;
        }

        .login-card h3 {
            color: var(--text-primary);
            font-size: 1.25rem;
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .login-card p {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-top: 0;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            box-sizing: border-box;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: var(--border-radius);
            color: var(--text-primary);
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(15, 255, 224, 0.3);
            background: rgba(0,0,0,0.6);
        }
        
        .form-control::placeholder {
            color: #666;
        }

        .input-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        
        .form-control:focus + .input-icon,
        .form-control:focus ~ .input-icon {
            color: var(--primary);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            margin-top: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            accent-color: var(--primary);
            margin-right: 0.5rem;
            cursor: pointer;
        }
        
        .checkbox-group label {
            font-size: 0.85rem;
            color: var(--text-primary);
            cursor: pointer;
        }

        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), #00b3ff);
            color: #000;
            padding: 0.85rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(15, 255, 224, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 255, 224, 0.6);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }

        .error-box {
            background: rgba(255, 0, 85, 0.1);
            border: 1px solid rgba(255, 0, 85, 0.4);
            color: #ff4d85;
            padding: 0.85rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.8rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            box-shadow: 0 0 10px rgba(255, 0, 85, 0.2);
        }
        
        .error-box ion-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
            margin-top: -0.1rem;
        }
        
        .error-box ul {
            margin: 0.25rem 0 0;
            padding-left: 1.25rem;
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.4);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="logo-icon-wrapper">
                <ion-icon name="wifi"></ion-icon>
            </div>
            <h2>FiberOps</h2>
            <p>Field Service Management Controller</p>
        </div>
        
        <!-- Card -->
        <div class="login-card">
            <h3>Welcome Back</h3>
            <p>Sign in to monitor technicians and manage work orders.</p>
            
            @if($errors->any())
                <div class="error-box">
                    <ion-icon name="alert-circle"></ion-icon>
                    <div>
                        <strong style="display:block; margin-bottom: 0.2rem;">Registration or login failed</strong>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            
            <form action="{{ route('admin.login.post') }}" method="POST">
                @csrf
                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <ion-icon name="mail-outline" class="input-icon"></ion-icon>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control" placeholder="name@company.com">
                    </div>
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                        <input id="password" type="password" name="password" required class="form-control" placeholder="••••••••">
                    </div>
                </div>
                
                <!-- Remember Me -->
                <div class="checkbox-group">
                    <input id="remember" name="remember" type="checkbox" />
                    <label for="remember">Keep me signed in</label>
                </div>
                
                <!-- Submit -->
                <button type="submit" class="btn-primary">Sign In</button>
            </form>
        </div>
        
        <div class="footer">
            <p>© 2026 FiberOps. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
