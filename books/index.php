
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$search = trim($_GET['search'] ?? '');
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 8;

$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name"
)->fetchAll();

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(b.title LIKE ? OR b.author LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryId && $categoryId > 0) {
    $where[] = "b.category_id = ?";
    $params[] = $categoryId;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM books b $whereSql"
);
$countStmt->execute($params);
$totalBooks = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalBooks / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare(
    "SELECT b.*, c.name AS category_name
     FROM books b
     LEFT JOIN categories c ON b.category_id = c.id
     $whereSql
     ORDER BY b.id DESC
     LIMIT $limit OFFSET $offset"
);
$stmt->execute($params);
$books = $stmt->fetchAll();

/* Add books to the session cart */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['add_to_cart'])) {

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit;
    }

    $bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);

    if ($bookId && $bookId > 0) {
        $check = $pdo->prepare(
            "SELECT id, stock FROM books WHERE id = ?"
        );
        $check->execute([$bookId]);
        $item = $check->fetch();

        if ($item && (int)$item['stock'] > 0) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            $currentQty = (int)($_SESSION['cart'][$bookId] ?? 0);

            if ($currentQty < (int)$item['stock']) {
                $_SESSION['cart'][$bookId] = $currentQty + 1;
                $_SESSION['cart_message'] = 'Book added to your cart!';
            } else {
                $_SESSION['cart_message'] = 'Stock limit reached.';
            }
        } else {
            $_SESSION['cart_message'] = 'This book is out of stock.';
        }
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$message = $_SESSION['cart_message'] ?? '';
unset($_SESSION['cart_message']);

function bookUrl(array $changes = []): string {
    $query = array_merge($_GET, $changes);
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }
    return '?' . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Explore Books | BookNest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
body { background:#f7f8fc; }
.catalog-header {
    padding:120px 0 45px;
    color:white;
    background:linear-gradient(125deg,#10182f,#5540a4);
}
.catalog-header h1 {font-weight:800;}
.catalog-tools,.catalog-card {
    background:white;
    border:1px solid #e9eaf2;
    border-radius:18px;
}
.catalog-tools {
    padding:20px;
    margin-top:-20px;
    position:relative;
}
.catalog-card {
    overflow:hidden;
    height:100%;
    transition:.25s;
}
.catalog-card:hover {
    transform:translateY(-5px);
    box-shadow:0 14px 30px #20295015;
}
.catalog-cover {
    height:245px;
    padding:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#eeeaff,#dce7ff);
}
.catalog-cover img {
    width:100%;
    height:100%;
    object-fit:contain;
}
.catalog-info {padding:20px;}
.catalog-info h5 {
    font-weight:700;
    overflow-wrap:anywhere;
}
.catalog-info p {color:#747b8b;}
.catalog-price {
    color:#6348d8;
    font-weight:700;
    font-size:1.1rem;
}
.cart-link {
    color:white;
    text-decoration:none;
    font-weight:600;
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bookstore-nav fixed-top">
<div class="container">
    <a class="navbar-brand fw-bold" href="../index.php">📚 BookNest</a>
    <button class="navbar-toggler" data-bs-toggle="collapse"
            data-bs-target="#catalogNav" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="catalogNav">
        <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            <a class="nav-link" href="../index.php">Home</a>
            <a class="nav-link" href="index.php">Books</a>
            <a class="nav-link" href="../cart/index.php">🛒 My Cart</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="nav-link" href="../dashboard.php">My Dashboard</a>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a class="btn btn-warning rounded-pill px-3"
                       href="../admin/dashboard.php">Admin Panel</a>
                <?php endif; ?>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            <?php else: ?>
                <a class="nav-link" href="../auth/login.php">Login</a>
                <a class="btn btn-warning rounded-pill px-3"
                   href="../auth/register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</nav>

<header class="catalog-header">
<div class="container">
    <span class="text-warning fw-semibold">THE BOOKNEST COLLECTION</span>
    <h1 class="display-5 mt-2">Find Your Next Great Read 📚</h1>
    <p class="mb-0 text-white-50">
        School, college, programming and competitive exam books.
    </p>
</div>
</header>

<main class="container pb-5">

<?php if ($message !== ''): ?>
    <div class="alert alert-info alert-dismissible fade show mt-4">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close"
                data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form class="catalog-tools row g-3 align-items-end" method="GET">
    <div class="col-md-6">
        <label for="search" class="form-label fw-semibold">Search books</label>
        <input class="form-control" id="search" name="search"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Enter a book title or author">
    </div>
    <div class="col-md-4">
        <label for="category" class="form-label fw-semibold">Category</label>
        <select class="form-select" id="category" name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int)$category['id'] ?>"
                    <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-dark flex-grow-1" type="submit">Search</button>
        <a href="index.php" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="d-flex flex-wrap justify-content-between align-items-center my-4 gap-2">
    <h2 class="h4 fw-bold mb-0">Our Books</h2>
    <span class="text-secondary"><?= $totalBooks ?> book(s) found</span>
</div>

<div class="row g-4">
<?php if ($books): ?>
    <?php foreach ($books as $book): ?>
        <div class="col-sm-6 col-lg-3">
            <article class="catalog-card">

                <div class="catalog-cover">
                    <?php if (!empty($book['image_url'])): ?>
                        <img
                            src="<?= htmlspecialchars($book['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="Cover of <?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='block';">
                        <span style="display:none;font-size:4rem" aria-hidden="true">📖</span>
                    <?php else: ?>
                        <span style="font-size:4rem" aria-hidden="true">📖</span>
                    <?php endif; ?>
                </div>

                <div class="catalog-info">
                    <span class="badge text-bg-light mb-2">
                        <?= htmlspecialchars($book['category_name'] ?? 'Uncategorized') ?>
                    </span>

                    <h5><?= htmlspecialchars($book['title']) ?></h5>
                    <p class="small mb-3">
                        by <?= htmlspecialchars($book['author']) ?>
                    </p>

                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <span class="catalog-price">
                            ₹<?= number_format((float)$book['price'], 2) ?>
                        </span>
                        <a class="btn btn-sm btn-outline-dark rounded-pill"
                           href="view.php?id=<?= (int)$book['id'] ?>">
                            Details
                        </a>
                    </div>

                    <p class="small mt-3 mb-3">
                        <?php if ((int)$book['stock'] > 0): ?>
                            <span class="text-success">
                                In stock: <?= (int)$book['stock'] ?>
                            </span>
                        <?php else: ?>
                            <span class="text-danger">Out of stock</span>
                        <?php endif; ?>
                    </p>

                    <form method="POST" action="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="book_id"
                               value="<?= (int)$book['id'] ?>">
                        <button type="submit" name="add_to_cart"
                                value="1"
                                class="btn btn-primary w-100 rounded-pill"
                                <?= (int)$book['stock'] < 1 ? 'disabled' : '' ?>>
                            🛒 Add to Cart
                        </button>
                    </form>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="col-12">
        <div class="bg-white text-center rounded-4 p-5">
            <div class="display-4">🔎</div>
            <h4 class="mt-3">No books found</h4>
            <p class="text-secondary">Try another title or category.</p>
            <a href="index.php" class="btn btn-dark rounded-pill">Show All Books</a>
        </div>
    </div>
<?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-5" aria-label="Book pages">
    <ul class="pagination justify-content-center flex-wrap">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link"
                   href="<?= htmlspecialchars(bookUrl(['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

</main>

<footer class="site-footer">
<div class="container">
    <strong>📚 BookNest</strong>
    <p class="mb-0 mt-2">Discover. Read. Grow.</p>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
