<?php
require_once 'db.php';
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $contact  = trim($_POST['contact_number'] ?? '');
    $bizName  = trim($_POST['business_name'] ?? '');

    if (empty($fullname) || empty($email) || empty($password) || empty($bizName)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $conn = getConnection();

        $stmt = $conn->prepare("SELECT userID FROM User WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $error = 'An account with this email already exists.';
            $conn->close();
        } else {
            // Ensure Owner role exists
            $stmt = $conn->prepare("SELECT roleID FROM Role WHERE role_name='Owner' LIMIT 1");
            $stmt->execute();
            $role = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$role) {
                $conn->query("INSERT INTO Role (role_name, description) VALUES ('Owner','Business owner')");
                $roleID = $conn->insert_id;
            } else {
                $roleID = $role['roleID'];
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO User (roleID, fullname, email, password_hash, contact_number) VALUES (?,?,?,?,?)");
            $stmt->bind_param("issss", $roleID, $fullname, $email, $hash, $contact);

            if ($stmt->execute()) {
                $userID = $conn->insert_id;
                $stmt->close();
                $stmt = $conn->prepare("INSERT INTO Business (userID, business_name) VALUES (?,?)");
                $stmt->bind_param("is", $userID, $bizName);
                $stmt->execute();
                $stmt->close();
                $conn->close();
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed: ' . $conn->error;
                $conn->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — FinanceDB</title>
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
            padding: 1.5rem 1rem;
        }

        .auth-card {
            background: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 480px;
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

        .required {
            color: #d63031;
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="brand-icon mb-2"><i class="fas fa-chart-line"></i></div>
            <h2 class="fw-bold mb-1" style="font-size:1.5rem">Create Account</h2>
            <p class="text-muted small">Set up your business finance tracker</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-circle-xmark me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label text-muted small">Full Name <span class="required">*</span></label>
                    <input type="text" name="fullname" class="form-control" placeholder="Juan Dela Cruz"
                        value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted small">Business Name <span class="required">*</span></label>
                    <input type="text" name="business_name" class="form-control" placeholder="Juan Mini Store"
                        value="<?= htmlspecialchars($_POST['business_name'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted small">Email Address <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted small">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" placeholder="09xxxxxxxxx"
                        value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small">Password <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small">Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 mt-4">
                Create Account <i class="fas fa-user-plus ms-1"></i>
            </button>
        </form>

        <hr class="my-3">
        <p class="text-center text-muted small mb-0">
            Already have an account? <a href="login.php" class="text-decoration-none fw-semibold" style="color:#2d7dd2">Sign in</a>
        </p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
