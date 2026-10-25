
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';

// Update or remove cart items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookId = (int)($_POST['book_id'] ?? 0);

    if ($action === 'remove') {
        unset($_SESSION['cart'][$bookId]);
        $message = 'Book removed from cart.';
    } elseif ($action === 'update') {
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($bookId > 0 && isset($_SESSION['cart'][$bookId]) && $quantity > 0) {
            $stmt = $pdo->prepare("SELECT stock FROM books WHERE id = ?");
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();

            if (!$book) {
                unset($_SESSION['cart'][$bookId]);
                $message = 'Book is no longer available.';
            } elseif ($quantity > (int)$book['stock']) {
                $message = 'Requested quantity exceeds available stock.';
            } else {
                $_SESSION['cart'][$bookId] = $quantity;
                $message = 'Cart updated successfully.';
            }
        }
    }
}

// Fetch book details
$cartItems = [];
$grandTotal = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT id, title, author, price, stock
         FROM books WHERE id IN ($placeholders)"
    );
    $stmt->execute($ids);

    foreach ($stmt->fetchAll() as $book) {
        $id = (int)$book['id'];
        $quantity = (int)($_SESSION['cart'][$id] ?? 0);

        if ($quantity <= 0) {
            continue;
        }

        $book['quantity'] = $quantity;
        $book['subtotal'] = (float)$book['price'] * $quantity;
        $grandTotal += $book['subtotal'];
        $cartItems[] = $book;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shopping Cart | BookNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bookstore-nav">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">📚 BookNest</a>
        <a href="../books/index.php" class="btn btn-outline-light">Browse Books</a>
    </div>
</nav>

<main class="container py-5">
    <h1 class="mb-4">🛒 Your Shopping Cart</h1>

    <?php if ($message !== ''): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <h4>Your cart is empty.</h4>
                <p>Browse the catalog and add books to your cart.</p>
                <a href="../books/index.php" class="btn btn-primary">Browse Books</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Book</th>
                                <th>Author</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['title']) ?></td>
                                <td><?= htmlspecialchars($item['author']) ?></td>
                                <td>₹<?= number_format((float)$item['price'], 2) ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="book_id" value="<?= (int)$item['id'] ?>">
                                        <input type="number" name="quantity" min="1"
                                               max="<?= (int)$item['stock'] ?>"
                                               value="<?= (int)$item['quantity'] ?>"
                                               class="form-control" style="width:85px" required>
                                        <button class="btn btn-primary btn-sm">Update</button>
                                    </form>
                                </td>
                                <td>₹<?= number_format($item['subtotal'], 2) ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Remove this book?');">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="book_id" value="<?= (int)$item['id'] ?>">
                                        <button class="btn btn-danger btn-sm">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-end">
                    <h4>Total: ₹<?= number_format($grandTotal, 2) ?></h4>
                    <a href="../books/index.php" class="btn btn-outline-secondary">Continue Shopping</a>
                    <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
