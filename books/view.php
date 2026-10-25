
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid book ID.');
}

$stmt = $pdo->prepare(
    "SELECT b.*, c.name AS category_name
     FROM books b
     LEFT JOIN categories c ON b.category_id = c.id
     WHERE b.id = ?"
);
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    exit('Book not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($book['title']) ?> | BookNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {background:#f7f8fc;}
        .details-wrap {padding:130px 0 70px;}
        .details-card {
            background:white;border-radius:24px;overflow:hidden;
            box-shadow:0 12px 40px #10182f10;
        }
        .details-cover {
            min-height:350px;height:100%;display:grid;place-items:center;
            background:linear-gradient(135deg,#eeeaff,#dce7ff);
            font-size:8rem;
        }
        .details-content {padding:clamp(25px,5vw,55px);}
        .details-content h1 {font-weight:800;overflow-wrap:anywhere;}
        .details-price {color:#6548d8;font-size:2rem;font-weight:800;}
    </style>
</head>
<body>
<nav class="navbar navbar-dark bookstore-nav fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">📚 BookNest</a>
        <a href="index.php" class="btn btn-warning rounded-pill px-3">All Books</a>
    </div>
</nav>

<main class="container details-wrap">
    <div class="details-card">
        <div class="row g-0">
            <div class="col-md-5">
                <div class="details-cover">📖</div>
            </div>
            <div class="col-md-7">
                <div class="details-content">
                    <span class="badge text-bg-light mb-3">
                        <?= htmlspecialchars($book['category_name'] ?? 'Uncategorized') ?>
                    </span>
                    <h1 class="display-6">
                        <?= htmlspecialchars($book['title']) ?>
                    </h1>
                    <p class="text-secondary">
                        Written by <?= htmlspecialchars($book['author']) ?>
                    </p>
                    <div class="details-price mb-3">
                        ₹<?= number_format((float)$book['price'], 2) ?>
                    </div>
                    <p>
                        <?= nl2br(htmlspecialchars(
                            $book['description'] ?: 'Discover this book and explore its contents.'
                        )) ?>
                    </p>
                    <p>
                        <strong>Available stock:</strong>
                        <?= (int)$book['stock'] ?>
                    </p>

                    <?php if ((int)$book['stock'] > 0): ?>
                        <div class="alert alert-success">
                            This book is available in the collection.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">Currently out of stock.</div>
                    <?php endif; ?>

                    <a href="index.php" class="btn btn-dark rounded-pill px-4">
                        ← Back to Books
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="../dashboard.php"
                           class="btn btn-outline-dark rounded-pill px-4">
                            My Dashboard
                        </a>
                    <?php else: ?>
                        <a href="../auth/login.php"
                           class="btn btn-outline-dark rounded-pill px-4">
                            Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>