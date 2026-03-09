<?php
require_once 'db.php';
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error   = '';
$success = isset($_GET['registered']) ? 'Account created! You can now log in.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT userID, fullname, password_hash FROM User WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['userID']   = $user['userID'];
            $_SESSION['fullname'] = $user['fullname'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — FinanceDB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1f2e, #2d3748);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .auth-card {
            background: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .brand-icon {
            width: 56px;
            height: 56px;
            background: #2d7dd2;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
        }

        .btn-primary {
            background: #2d7dd2;
            border-color: #2d7dd2;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #2266b8;
            border-color: #2266b8;
        }

        .form-control:focus {
            border-color: #2d7dd2;
            box-shadow: 0 0 0 3px rgba(45, 125, 210, .15);
        }

        .alert-danger {
            background: rgba(214, 48, 49, .1);
            border-color: rgba(214, 48, 49, .3);
            color: #b02a2a;
        }

        .alert-success {
            background: rgba(26, 158, 63, .1);
            border-color: rgba(26, 158, 63, .3);
            color: #156830;
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="brand-icon mb-2"><i class="fas fa-chart-line"></i></div>
            <h2 class="fw-bold mb-1" style="font-size:1.5rem">FinanceDB</h2>
            <p class="text-muted small">Sign in to manage your finances</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-circle-xmark me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-500 text-muted small">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-500 text-muted small">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                Sign In <i class="fas fa-arrow-right ms-1"></i>
            </button>
        </form>

        <hr class="my-3">
        <p class="text-center text-muted small mb-0">
            Don't have an account? <a href="register.php" class="text-decoration-none fw-semibold" style="color:#2d7dd2">Register here</a>
        </p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
