
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Allow administrators only
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header('Location: ../auth/login.php');
    exit;
}

// Dashboard statistics
$totalUsers = (int)$pdo->query(
    "SELECT COUNT(*) FROM users"
)->fetchColumn();

$totalBooks = (int)$pdo->query(
    "SELECT COUNT(*) FROM books"
)->fetchColumn();

$totalOrders = (int)$pdo->query(
    "SELECT COUNT(*) FROM orders"
)->fetchColumn();

$totalRevenue = (float)$pdo->query(
    "SELECT COALESCE(SUM(total_amount), 0)
     FROM orders
     WHERE status = 'Completed'"
)->fetchColumn();

// Latest five orders
$recentOrders = $pdo->query(
    "SELECT o.id, o.total_amount, o.status, o.created_at, u.name
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.id DESC
     LIMIT 5"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Admin Dashboard | BookNest</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #f5f6fc;
        }

        .admin-layout {
            padding: 115px 0 55px;
        }

        .admin-welcome {
            padding: 32px;
            border-radius: 22px;
            color: white;
            background: linear-gradient(120deg, #10182f, #5540a4);
        }

        .admin-stat {
            background: white;
            border: 1px solid #eceef5;
            border-radius: 18px;
            padding: 25px;
            height: 100%;
            transition: .2s;
        }

        .admin-stat:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px #10182f10;
        }

        .admin-stat .icon {
            font-size: 2rem;
        }

        .admin-stat h2 {
            font-weight: 800;
            margin-top: 12px;
            overflow-wrap: anywhere;
        }

        .admin-panel {
            background: white;
            border: 1px solid #eceef5;
            border-radius: 18px;
            padding: 25px;
        }

        .admin-action {
            display: block;
            text-decoration: none;
            color: #20263a;
            border: 1px solid #e9eaf2;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 12px;
            transition: .2s;
        }

        .admin-action:hover {
            border-color: #7557e8;
            background: #f7f5ff;
            color: #20263a;
        }
    </style>
</head>

<body>

<!-- Navigation -->
<nav class="navbar navbar-dark bookstore-nav fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">
            📚 BookNest Admin
        </a>

        <div class="d-flex gap-2 align-items-center">
            <a class="btn btn-outline-light rounded-pill"
               href="../index.php">
                View Website
            </a>

            <a class="btn btn-warning rounded-pill"
               href="../auth/logout.php">
                Logout
            </a>
        </div>
    </div>
</nav>

<main class="container admin-layout">

    <!-- Welcome section -->
    <section class="admin-welcome mb-4">
        <span class="badge text-bg-warning mb-3">
            ADMIN CONTROL PANEL
        </span>

        <h1 class="h2 fw-bold">
            Welcome,
            <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>!
        </h1>

        <p class="mb-0 text-white-50">
            Manage your bookstore from one place.
        </p>
    </section>

    <!-- Statistics -->
    <div class="row g-3 mb-4">

        <div class="col-sm-6 col-lg-3">
            <div class="admin-stat">
                <div class="icon">👥</div>
                <p class="text-secondary mt-3 mb-0">
                    Registered Users
                </p>
                <h2><?= $totalUsers ?></h2>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="admin-stat">
                <div class="icon">📚</div>
                <p class="text-secondary mt-3 mb-0">
                    Total Books
                </p>
                <h2><?= $totalBooks ?></h2>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="admin-stat">
                <div class="icon">🛍️</div>
                <p class="text-secondary mt-3 mb-0">
                    Total Orders
                </p>
                <h2><?= $totalOrders ?></h2>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="admin-stat">
                <div class="icon">💰</div>
                <p class="text-secondary mt-3 mb-0">
                    Completed Order Revenue
                </p>
                <h2>₹<?= number_format($totalRevenue, 2) ?></h2>
            </div>
        </div>

    </div>

    <!-- Management and recent orders -->
    <div class="row g-4">

        <div class="col-lg-5">
            <section class="admin-panel h-100">

                <h4 class="fw-bold mb-4">Management</h4>

                <a class="admin-action" href="books.php">
                    📚 <strong>Manage Books</strong>
                    <div class="small text-secondary mt-1">
                        Add, edit, and delete books
                    </div>
                </a>

                <a class="admin-action" href="users.php">
                    👥 <strong>Manage Users</strong>
                    <div class="small text-secondary mt-1">
                        View registered accounts
                    </div>
                </a>

                <a class="admin-action" href="orders.php">
                    🛍️ <strong>Manage Orders</strong>
                    <div class="small text-secondary mt-1">
                        Review orders and update statuses
                    </div>
                </a>

                <a class="admin-action" href="../books/index.php">
                    🛒 <strong>View Bookstore Catalog</strong>
                    <div class="small text-secondary mt-1">
                        Browse books available in the store
                    </div>
                </a>

            </section>
        </div>

        <div class="col-lg-7">
            <section class="admin-panel">

                <h4 class="fw-bold mb-4">Recent Orders</h4>

                <div class="table-responsive">
                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (!empty($recentOrders)): ?>

                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>
                                        #<?= (int)$order['id'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($order['name']) ?>
                                    </td>

                                    <td>
                                        ₹<?= number_format(
                                            (float)$order['total_amount'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($order['status']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4"
                                    class="text-center text-secondary">
                                    No orders yet.
                                </td>
                            </tr>

                        <?php endif; ?>
                        </tbody>

                    </table>
                </div>

                <a href="orders.php" class="btn btn-outline-primary">
                    View All Orders
                </a>

            </section>
        </div>

    </div>

    <!-- Back to homepage -->
    <div class="text-center mt-4">
        <a href="../index.php" class="btn btn-outline-dark">
            Back to Homepage
        </a>
    </div>

</main>

</body>
</html>
