<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Common ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-header {
            background-color: #0f172a;
            color: white;
            padding: 2.25rem 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2.25rem 2rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <i class="bi bi-boxes fs-1 text-primary mb-2 d-inline-block"></i>
        <h3 class="fw-bold mb-1">Common ERP</h3>
        <p class="text-secondary small mb-0">Multi-Company Modular Enterprise Platform</p>
    </div>

    <div class="login-body">
        @if(session('success'))
            <div class="alert alert-success py-2 small mb-3">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger py-2 small mb-3">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" name="email" id="emailInput" class="form-control border-start-0 ps-0" value="{{ old('email', 'admin@erp.com') }}" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" id="passwordInput" class="form-control border-start-0 ps-0" value="password123" required>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" checked>
                    <label class="form-check-label small" for="rememberMe">Remember me</label>
                </div>
                <span class="small text-muted">Default pwd: password123</span>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                Sign In to ERP <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>

        <hr class="my-4">

        <div>
            <div class="small fw-bold text-muted text-uppercase mb-2">Quick Demo Accounts:</div>
            <div class="d-flex flex-wrap gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small" onclick="fillLogin('admin@erp.com')">Super Admin</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small" onclick="fillLogin('admin@apexindustrial.com')">Company Admin</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small" onclick="fillLogin('accounts@apexindustrial.com')">Accountant</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small" onclick="fillLogin('inventory@apexindustrial.com')">Inventory Mgr</button>
            </div>
        </div>
    </div>
</div>

<script>
    function fillLogin(email) {
        document.getElementById('emailInput').value = email;
        document.getElementById('passwordInput').value = 'password123';
    }
</script>
</body>
</html>

