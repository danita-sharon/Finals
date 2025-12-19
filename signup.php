<?php
session_start();
require_once 'config.php';
require_once 'util.php';

$error = '';
$success = '';

// This block catches the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Please fill all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        // 1. Check if email already exists
        $stmt = $pdo->prepare('SELECT id FROM individual WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        
        if ($stmt->fetch()) {
            $error = 'An account with that email already exists.';
        } else {
            // 2. Hash the password for security
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // 3. Insert into the database
            try {
                $ins = $pdo->prepare('INSERT INTO individual (name, email, password_hash, role) VALUES (:name, :email, :ph, "user")');
                $ins->execute([
                    'name' => $name,
                    'email' => $email,
                    'ph' => $hash
                ]);
                $success = 'Account created successfully! Redirecting to login...';
                header('Refresh:2; url=login2.php'); // Redirect after 2 seconds
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

generate_head('Join Lash Nouveau');
generate_header();
?>

<div class="container">
    <div class="card" style="max-width:450px; margin: 60px auto; padding: 40px;">
        <h2 style="color:var(--deep-pink-dark); text-align: center; margin-bottom: 8px;">Create Account</h2>
        <p class="note" style="text-align: center; margin-bottom: 30px;">Experience luxury lash services</p>

        <?php if ($error): ?>
            <div style="color: #a10044; background: #fde3ec; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="color: green; background: #e8f5e9; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label class="group-title">Full Name</label>
                <input type="text" name="name" required placeholder="Enter your name" value="<?= htmlspecialchars($name ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="group-title">Email Address</label>
                <input type="email" name="email" required placeholder="email@example.com" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label class="group-title">Password</label>
                <input type="password" name="password" required placeholder="Min. 8 characters">
            </div>

            <button class="btn" type="submit" style="width: 100%; margin-top: 10px;">Sign Up</button>
            
            <p class="small-muted" style="text-align: center; margin-top: 25px;">
                Already a member? <a href="login2.php" style="color: var(--deep-pink); font-weight: 700;">Log in here</a>
            </p>
        </form>
    </div>
</div>

<?php generate_footer(); ?>