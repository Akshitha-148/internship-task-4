
<?php
session_start();
require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query(
    "SELECT b.*, c.name AS category_name
     FROM books b
     LEFT JOIN categories c ON b.category_id = c.id
     ORDER BY b.id DESC
     LIMIT 8"
);
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookNest | Discover Your Next Favourite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top bookstore-nav">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">📚 BookNest</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#books">Books</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light rounded-pill px-4"
                           href="auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-warning rounded-pill px-4 fw-semibold"
                           href="auth/register.php">Join Now</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<header class="hero-section" id="home">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <span class="hero-label">YOUR NEXT CHAPTER STARTS HERE</span>
                <h1>Open a Book.<br>Open a <span>New World.</span></h1>
                <p class="hero-description">
                    Explore inspiring stories, discover new ideas, and find
                    the perfect book for every moment of your life.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="#books" class="btn btn-warning btn-lg rounded-pill px-4">
                        Explore Books →
                    </a>
                    <a href="auth/register.php"
                       class="btn btn-outline-light btn-lg rounded-pill px-4">
                        Get Started
                    </a>
                </div>
                <div class="hero-stats mt-5">
                    <div><strong>Explore</strong><small>New Stories</small></div>
                    <div><strong>Discover</strong><small>Great Authors</small></div>
                    <div><strong>Enjoy</strong><small>Every Chapter</small></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-art">
                    <div class="floating-note note-one">✨ Read. Learn. Grow.</div>
                    <div class="book-stack">
                        <div class="book book-one">THE ART OF<br>THINKING</div>
                        <div class="book book-two">DREAM<br>BIG</div>
                        <div class="book book-three">NEW<br>CHAPTERS</div>
                    </div>
                    <div class="floating-note note-two">📖 Your next favourite awaits</div>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="benefits-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="benefit-card">
                    <span>📚</span>
                    <h5>Explore Books</h5>
                    <p>Browse a growing collection of books across categories.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card">
                    <span>🔎</span>
                    <h5>Find Your Favourite</h5>
                    <p>Discover titles and authors that match your interests.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="benefit-card">
                    <span>🛍️</span>
                    <h5>Manage Your Orders</h5>
                    <p>Keep track of your bookstore orders from your account.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="books-section" id="books">
    <div class="container">
        <div class="section-heading text-center">
            <span class="eyebrow">THE BOOK COLLECTION</span>
            <h2>Stories Worth <span>Discovering</span></h2>
            <p>Find your next great read from our latest collection.</p>
        </div>

        <div class="row g-4">
            <?php if ($books): ?>
                <?php foreach ($books as $book): ?>
                    <div class="col-sm-6 col-lg-3">
                        <div class="book-card h-100">
                            <div class="book-cover">
                                <span>📖</span>
                                <small><?= htmlspecialchars($book['category_name'] ?? 'Featured') ?></small>
                            </div>
                            <div class="book-info">
                                <h5><?= htmlspecialchars($book['title']) ?></h5>
                                <p>by <?= htmlspecialchars($book['author']) ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="book-price">
                                        ₹<?= number_format((float)$book['price'], 2) ?>
                                    </strong>
                                    <?php if ((int)$book['stock'] > 0): ?>
                                        <a class="btn btn-sm btn-dark rounded-pill"
                                           href="books/view.php?id=<?= (int)$book['id'] ?>">
                                            View Book
                                        </a>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Out of stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-books">
                        <span>📚</span>
                        <h4>Your next favourite book is coming soon!</h4>
                        <p>The admin can add books from the management dashboard.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-5">
            <a href="books/index.php" class="btn btn-outline-dark rounded-pill px-4">
                View All Books →
            </a>
        </div>
    </div>
</section>

<section class="about-section" id="about">
    <div class="container">
        <div class="row align-items-center gy-4">
            <div class="col-lg-7">
                <span class="eyebrow">ABOUT BOOKNEST</span>
                <h2>A Little Reading Can Make a <span>Big Difference.</span></h2>
                <p>
                    BookNest is an online bookstore project designed to make
                    book discovery and record management simple. Users can
                    explore books, while administrators manage inventory,
                    customer records, and orders.
                </p>
            </div>
            <div class="col-lg-5">
                <div class="about-highlight">
                    <div>📖</div>
                    <h4>Every book has a story.</h4>
                    <p>Find the one that inspires yours.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="container d-flex flex-wrap justify-content-between gap-3">
        <div><strong>📚 BookNest</strong><p class="mb-0">Discover. Read. Grow.</p></div>
        <p class="mb-0">Internship Task 4 · Real-World Full Stack Project</p>
        <a href="#home">Back to top ↑</a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>