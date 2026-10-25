
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

$message = '';
$allowedStatuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    if ($orderId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare(
            "UPDATE orders SET status = ? WHERE id = ?"
        );
        $stmt->execute([$newStatus, $orderId]);

        $message = $stmt->rowCount() > 0
            ? 'Order status updated successfully.'
            : 'No change made, or the order does not exist.';
    } else {
        $message = 'Please select a valid order status.';
    }
}

// Get all orders with customer details
$stmt = $pdo->query(
    "SELECT o.id, o.user_id, o.total_amount, o.status,
            o.created_at, u.name, u.email
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.id DESC"
);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Manage Orders | BookNest</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #f5f6fc;
        }

        .orders-container {
            padding-top: 100px;
            padding-bottom: 50px;
        }

        .orders-panel {
            background: white;
            border: 1px solid #eceef5;
            border-radius: 18px;
            padding: 24px;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-dark bookstore-nav fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">
            📚 BookNest Admin
        </a>

        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-light">
                Dashboard
            </a>

            <a href="../auth/logout.php" class="btn btn-warning">
                Logout
            </a>
        </div>
    </div>
</nav>

<main class="container orders-container">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h2>🛍️ Manage Orders</h2>

        <a href="dashboard.php" class="btn btn-dark">
            Back to Dashboard
        </a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="orders-panel">

        <h4 class="mb-3">
            Customer Orders (<?= count($orders) ?>)
        </h4>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">

                <thead class="table-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Update Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($orders)): ?>

                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            No customer orders found yet.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= (int)$order['id'] ?></td>

                            <td>
                                <?= htmlspecialchars($order['name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($order['email']) ?>
                            </td>

                            <td>
                                ₹<?= number_format(
                                    (float)$order['total_amount'],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($order['created_at']) ?>
                            </td>

                            <td>
                                <span class="badge text-bg-secondary">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                            </td>

                            <td>
                                <form method="POST"
                                      class="d-flex gap-2 flex-wrap">

                                    <input type="hidden"
                                           name="order_id"
                                           value="<?= (int)$order['id'] ?>">

                                    <select name="status"
                                            class="form-select form-select-sm"
                                            required>
                                        <?php foreach ($allowedStatuses as $status): ?>
                                            <option
                                                value="<?= htmlspecialchars($status) ?>"
                                                <?= $order['status'] === $status
                                                    ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit"
                                            class="btn btn-primary btn-sm">
                                        Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>
                </tbody>

            </table>
        </div>

    </section>

</main>

</body>
</html>
