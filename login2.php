<?php
session_start();
require_once 'config.php';
require_once 'util.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        // Fetch user based on email
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM individual WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Verify password hash
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } else {
            // Successful login sessions
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on user role
            if ($user['role'] === 'admin') {
                header('Location: admin_services.php');
                exit;
            } else {
                header('Location: services.php');
                exit;
            }
        }
    }
}

generate_head('Log in');
generate_header();
?>

<div class="container">
    <div class="card" style="max-width:420px; margin: 60px auto; padding: 40px; text-align: center;">
        <h2 style="color:var(--deep-pink-dark); margin-bottom: 8px;">Welcome Back</h2>
        <p class="note" style="margin-bottom: 35px;">Hey girly! Log in to manage your appointments</p>
        
        <?php if ($error): ?>
            <div style="color:var(--deep-pink-dark); background:var(--pastel-1); padding:12px; border-radius:12px; margin-bottom:25px; font-size: 14px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label class="group-title">Email Address</label>
                <input type="email" name="email" required placeholder="name@example.com" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="group-title">Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button class="btn" type="submit" style="width: 100%; margin-top: 10px; margin-bottom: 20px;">Log In</button>
            
            <div class="small-muted" style="text-align: center;">
                Don't have an account? <a href="signup.php" style="color: var(--deep-pink); font-weight: 700;">Sign up</a>
            </div>
        </form>
    </div>
</div>
