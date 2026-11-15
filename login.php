<?php
session_start();
require_once __DIR__ . '/config/Database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Verify password against bcrypt hash in database
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AgriPulse</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Sky Blue Styling -->
    <style>
        body {
            background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 50%, #7dd3fc 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(2, 132, 199, 0.15);
        }
        .btn-sky {
            background-color: #0284c7;
            border-color: #0284c7;
            color: #ffffff;
            transition: all 0.2s ease-in-out;
        }
        .btn-sky:hover {
            background-color: #0369a1;
            border-color: #0369a1;
            color: #ffffff;
        }
        .form-control:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.25);
        }
        .brand-title {
            color: #0369a1;
            letter-spacing: -0.5px;
        }
    </style>
</head>
<body class="d-flex align-items-center py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card p-4 p-sm-5 bg-white">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold brand-title mb-1">AgriPulse</h2>
                        <p class="text-muted small mb-0">Agriculture Information System</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label for="email" class="form-label text-secondary small fw-semibold">Email Address</label>
                            <input type="email" class="form-control form-control-lg fs-6" id="email" name="email" required placeholder="admin@example.com">
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label text-secondary small fw-semibold">Password</label>
                            <input type="password" class="form-control form-control-lg fs-6" id="password" name="password" required placeholder="••••••••">
                        </div>
                        <button type="submit" class="btn btn-sky btn-lg w-100 fs-6 fw-semibold py-2 shadow-sm">Sign In</button>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 0.8rem;">
                        <p class="mb-1">Demo Admin: <code>admin@example.com</code> / <code>@admin123</code></p>
                        <p class="mb-0">Demo Staff: <code>staff@example.com</code> / <code>@staff123</code></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>