
<?php
session_start();
require_once "../config/database.php";

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";

// Add or update a book
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "save") {
        $id = (int)($_POST["id"] ?? 0);
        $title = trim($_POST["title"] ?? "");
        $author = trim($_POST["author"] ?? "");
        $price = (float)($_POST["price"] ?? 0);
        $stock = (int)($_POST["stock"] ?? 0);
        $category_id = ($_POST["category_id"] ?? "") !== ""
            ? (int)$_POST["category_id"]
            : null;
        $description = trim($_POST["description"] ?? "");

        if ($title === "" || $author === "" || $price < 0 || $stock < 0) {
            $message = "Please enter valid book details.";
        } else {
            if ($id > 0) {
                $sql = "UPDATE books
                        SET title=?, author=?, price=?, stock=?,
                            category_id=?, description=?
                        WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $title, $author, $price, $stock,
                    $category_id, $description, $id
                ]);
                $message = "Book updated successfully.";
            } else {
                $sql = "INSERT INTO books
                        (title, author, price, stock, category_id, description)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $title, $author, $price, $stock,
                    $category_id, $description
                ]);
                $message = "Book added successfully.";
            }
        }
    }

    if ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Book deleted successfully.";
        }
    }
}

// Load book for editing
$editBook = null;
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([(int)$_GET["edit"]]);
    $editBook = $stmt->fetch();
}

// Load categories and books
$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name"
)->fetchAll();

$books = $pdo->query(
    "SELECT b.*, c.name AS category_name
     FROM books b
     LEFT JOIN categories c ON b.category_id = c.id
     ORDER BY b.id DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Books | BookNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h2>📚 Manage Books</h2>
        <a href="dashboard.php" class="btn btn-dark">Admin Dashboard</a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-5">
        <div class="card-body p-4">
            <h4><?= $editBook ? "Edit Book" : "Add New Book" ?></h4>

            <form method="POST">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id"
                       value="<?= (int)($editBook["id"] ?? 0) ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Book Title</label>
                        <input type="text" name="title" class="form-control"
                               required maxlength="200"
                               value="<?= htmlspecialchars($editBook["title"] ?? "") ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" class="form-control"
                               required maxlength="150"
                               value="<?= htmlspecialchars($editBook["author"] ?? "") ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Price (₹)</label>
                        <input type="number" name="price" class="form-control"
                               min="0" step="0.01" required
                               value="<?= htmlspecialchars((string)($editBook["price"] ?? "")) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock" class="form-control"
                               min="0" required
                               value="<?= htmlspecialchars((string)($editBook["stock"] ?? "0")) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">No category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)$category["id"] ?>"
                                    <?= (isset($editBook["category_id"]) &&
                                        (int)$editBook["category_id"] === (int)$category["id"])
                                        ? "selected" : "" ?>>
                                    <?= htmlspecialchars($category["name"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"
                                  rows="3"><?= htmlspecialchars($editBook["description"] ?? "") ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">
                    <?= $editBook ? "Update Book" : "Add Book" ?>
                </button>

                <?php if ($editBook): ?>
                    <a href="books.php" class="btn btn-secondary mt-3">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="mb-3">All Books (<?= count($books) ?>)</h4>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$books): ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                No books added yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?= (int)$book["id"] ?></td>
                                <td><?= htmlspecialchars($book["title"]) ?></td>
                                <td><?= htmlspecialchars($book["author"]) ?></td>
                                <td><?= htmlspecialchars($book["category_name"] ?? "None") ?></td>
                                <td>₹<?= number_format((float)$book["price"], 2) ?></td>
                                <td><?= (int)$book["stock"] ?></td>
                                <td>
                                    <a class="btn btn-sm btn-warning mb-1"
                                       href="books.php?edit=<?= (int)$book["id"] ?>">
                                        Edit
                                    </a>

                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this book?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id"
                                               value="<?= (int)$book["id"] ?>">
                                        <button type="submit"
                                                class="btn btn-sm btn-danger mb-1">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>