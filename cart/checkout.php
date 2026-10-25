
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$orderId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $cart = $_SESSION['cart'];
        $items = [];
        $total = 0;

        $stmt = $pdo->prepare(
            "SELECT id, title, price, stock FROM books WHERE id = ? FOR UPDATE"
        );

        foreach ($cart as $bookId => $quantity) {
            $bookId = (int)$bookId;
            $quantity = (int)$quantity;

            if ($bookId <= 0 || $quantity <= 0) {
                throw new Exception('Invalid cart item.');
            }

            $stmt->execute([$bookId]);
            $book = $stmt->fetch();

            if (!$book) {
                throw new Exception('A book in your cart no longer exists.');
            }

            if ((int)$book['stock'] < $quantity) {
                throw new Exception(
                    'Not enough stock for ' . $book['title'] . '. Please update your cart.'
                );
            }

            $price = (float)$book['price'];
            $total += $price * $quantity;

            $items[] = [
                'id' => $bookId,
                'quantity' => $quantity,
                'price' => $price
            ];
        }

        if (!$items || $total <= 0) {
            throw new Exception('Your cart has no valid items.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO orders (user_id, total_amount, status)
             VALUES (?, ?, 'Pending')"
        );
        $stmt->execute([(int)$_SESSION['user_id'], $total]);

        $orderId = (int)$pdo->lastInsertId();

        $insertItem = $pdo->prepare(
            "INSERT INTO order_items (order_id, book_id, quantity, unit_price)
             VALUES (?, ?, ?, ?)"
        );

        $updateStock = $pdo->prepare(
            "UPDATE books SET stock = stock - ? WHERE id = ? AND stock >= ?"
        );

        foreach ($items as $item) {
            $insertItem->execute([
                $orderId,
                $item['id'],
                $item['quantity'],
                $item['price']
            ]);

            $updateStock->execute([
                $item['quantity'],
                $item['id'],
                $item['quantity']
            ]);

            if ($updateStock->rowCount() !== 1) {
                throw new Exception('Stock changed. Please try again.');
            }
        }

        $pdo->commit();
        $_SESSION['cart'] = [];
        $message = 'Order placed successfully. Stock updated in the database.';


    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = $e instanceof PDOException
            ? 'Unable to place your order. Please try again.'
            : $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | BookNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">
<nav class="navbar navbar-dark bookstore-nav">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">📚 BookNest</a>
    </div>
</nav>

<main class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:650px">
        <div class="card-body p-4 p-md-5">

            <?php if ($orderId !== null): ?>
                <div class="text-center">
                    <h1 class="text-success">Order Placed!</h1>
                    <p>Thank you for shopping with BookNest.</p>
                    <p>Order number: <strong>#<?= $orderId ?></strong></p>
                    <p>Status: <strong>Pending</strong></p>
                    <a href="../dashboard.php" class="btn btn-primary">View Dashboard</a>
                    <a href="../books/index.php" class="btn btn-outline-secondary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <h1 class="mb-3">Confirm Your Order</h1>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <p>Confirm below to save your order to the BookNest database.</p>

                <form method="POST">
                    <button type="submit" class="btn btn-success w-100">
                        Confirm and Place Order
                    </button>
                </form>

                <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">
                    Return to Cart
                </a>
            <?php endif; ?>

        </div>
    </div>
</main>
</body>
</html>
