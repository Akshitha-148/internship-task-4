
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT name, email, role FROM users WHERE id = ?"
);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: auth/login.php');
    exit;
}

$_SESSION['role'] = $user['role'];

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM orders WHERE user_id = ?"
);
$stmt->execute([$userId]);
$orderCount = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(total_amount), 0)
     FROM orders
     WHERE user_id = ? AND status != 'Cancelled'"
);
$stmt->execute([$userId]);
$totalSpent = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT id, total_amount, status, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 5"
);
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Dashboard | BookNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #f6f7fc; }
        .dashboard-wrap { padding: 115px 0 60px; min-height: 85vh; }
        .welcome-panel {
            background: linear-gradient(120deg, #10182f, #5540a4);
            color: white; padding: 35px; border-radius: 22px;
            margin-bottom: 28px;
        }
        .welcome-panel p { color: #d8dcf1; }
        .stat-card {
            background: white; border-radius: 18px; padding: 25px;
            height: 100%; border: 1px solid #eceef5;
            box-shadow: 0 5px 20px rgba(25,35,70,.04);
        }
        .stat-icon { font-size: 1.8rem; }
        .stat-card h3 { font-weight: 700; margin-top: 12px; }
        .dashboard-table {
            background: white; border-radius: 18px; padding: 25px;
            border: 1px solid #eceef5; overflow-x: auto;
        }
        .dashboard-table h4 { font-weight: 700; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bookstore-nav fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">📚 BookNest</a>
        <div class="d-flex align-items-center gap-3">
            <a href="books/index.php" class="text-white text-decoration-none">
                Browse Books
            </a>
            <a href="auth/logout.php"
               class="btn btn-warning rounded-pill px-3">Logout</a>
        </div>
    </div>
</nav>

<main class="dashboard-wrap">
    <div class="container">
        <section class="welcome-panel">
            <span class="badge text-bg-warning mb-3">MY ACCOUNT</span>
            <h1 class="h2 fw-bold">
                Welcome, <?= htmlspecialchars($user['name']) ?>! 👋
            </h1>
            <p class="mb-0">
                Discover books and keep track of your bookstore activity.
            </p>
        </section>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-icon">📚</span>
                    <p class="text-secondary mb-0 mt-3">Explore our collection</p>
                    <h3>Books</h3>
                    <a href="books/index.php" class="btn btn-dark rounded-pill mt-2">
                        Browse Books →
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-icon">🛍️</span>
                    <p class="text-secondary mb-0 mt-3">Your total orders</p>
                    <h3><?= $orderCount ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-icon">💳</span>
                    <p class="text-secondary mb-0 mt-3">Order value, excluding cancellations</p>
                    <h3>₹<?= number_format($totalSpent, 2) ?></h3>
                </div>
            </div>
        </div>

        <section class="dashboard-table">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Recent Orders</h4>
                <span class="text-secondary small">
                    <?= htmlspecialchars($user['email']) ?>
                </span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($orders): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= (int) $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['created_at']) ?></td>
                                <td>₹<?= number_format((float)$order['total_amount'], 2) ?></td>
                                <td>
                                    <span class="badge text-bg-<?=
                                        $order['status'] === 'Completed' ? 'success' :
                                        ($order['status'] === 'Cancelled' ? 'danger' : 'warning')
                                    ?>">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                No orders yet. Explore the book collection to get started.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <a href="index.php" class="btn btn-outline-dark rounded-pill mt-3">
                ← Back to Home
            </a>
        </section>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <strong>📚 BookNest</strong>
        <p class="mb-0 mt-2">Your personal reading space.</p>
    </div>
</footer>
</body>
</html>