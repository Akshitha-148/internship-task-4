
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email address and password.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, name, email, password, role
             FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../dashboard.php');
            }
            exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | BookNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background:#f5f6fc; font-family:Poppins,sans-serif; }
        .auth-wrap { min-height:100vh; display:grid; place-items:center; padding:35px 15px; }
        .auth-card { width:100%; max-width:450px; background:white; padding:35px; border-radius:22px; box-shadow:0 15px 55px #17204415; }
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
            <h1 class="h3">Welcome Back!</h1>
            <p class="text-secondary">Log in to continue your reading journey.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input class="form-control" type="email" name="email"
                    value="<?= htmlspecialchars($email) ?>" required autocomplete="email">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input class="form-control" type="password" name="password"
                    required autocomplete="current-password">
            </div>
            <div class="text-end mb-4">
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
            <button class="btn auth-btn w-100" type="submit">Login</button>
        </form>

        <p class="text-center mt-4 mb-0">
            Don't have an account? <a href="register.php">Register</a>
        </p>
        <p class="text-center mt-3 mb-0">
            <a href="../index.php" class="text-secondary text-decoration-none">← Back to Home</a>
        </p>
    </div>
</div>
</body>
</html>