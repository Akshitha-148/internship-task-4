
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!preg_match("/^[\p{L}\s.'-]{2,100}$/u", $name)) {
        $error = 'Please enter a valid name (at least 2 characters).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'This email is already registered. Please log in.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, 'user')"
            );
            $stmt->execute([$name, $email, $hash]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['name'] = $name;
            $_SESSION['role'] = 'user';

            header('Location: ../dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account | BookNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background:#f5f6fc; font-family:Poppins,sans-serif; }
        .auth-wrap { min-height:100vh; display:grid; place-items:center; padding:35px 15px; }
        .auth-card { width:100%; max-width:480px; background:white; padding:35px; border-radius:22px; box-shadow:0 15px 55px #17204415; }
        .auth-logo { font-size:2.4rem; }
        .auth-card h1 { font-weight:700; }
        .form-control { padding:12px 14px; border-radius:10px; }
        .auth-btn { background:#7557e8; color:white; border:0; padding:13px; border-radius:10px; font-weight:600; }
        .auth-btn:hover { background:#5d42c5; color:white; }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="auth-logo">📚</div>
            <h1 class="h3">Join BookNest</h1>
            <p class="text-secondary">Create your account and discover new books.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input class="form-control" type="text" name="name"
                    value="<?= htmlspecialchars($name) ?>" required maxlength="100">
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input class="form-control" type="email" name="email"
                    value="<?= htmlspecialchars($email) ?>" required maxlength="150">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input class="form-control" type="password" name="password"
                    minlength="8" autocomplete="new-password" required>
                <small class="text-secondary">Use at least 8 characters.</small>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <input class="form-control" type="password" name="confirm_password"
                    minlength="8" autocomplete="new-password" required>
            </div>
            <button class="btn auth-btn w-100" type="submit">Create Account</button>
        </form>

        <p class="text-center mt-4 mb-0">
            Already have an account?
            <a href="login.php">Login</a>
        </p>
        <p class="text-center mt-3 mb-0">
            <a href="../index.php" class="text-secondary text-decoration-none">← Back to Home</a>
        </p>
    </div>
</div>
</body>
</html>