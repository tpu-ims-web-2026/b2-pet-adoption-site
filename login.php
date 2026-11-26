<?php
require_once 'config.php';
require_once 'includes/auth.php';

$rawNext = $_POST['next'] ?? $_GET['next'] ?? null;
$next = sanitize_next_url(is_scalar($rawNext) ? (string) $rawNext : null);

if (is_logged_in()) {
    redirect($next ?? 'index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Please enter a valid email address and password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT user_id, full_name, email, password_hash, role, is_active
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $error = 'Invalid email address or password.';
        } elseif (empty($user['is_active'])) {
            $error = 'Your account is currently inactive. Please contact support.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'user_id' => (int) $user['user_id'],
                'role' => (string) $user['role'],
                'full_name' => (string) $user['full_name'],
            ];

            set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect($next ?? 'index.php');
        }
    }
}

$pageTitle = 'Login';
$flash = get_flash();

require_once 'includes/header.php';
?>
        <section class="form-card" aria-labelledby="login-heading">
            <h1 id="login-heading">Log In</h1>
            <p class="form-subtitle">Enter your email address and password to access your account.</p>

            <?php if ($flash !== null) { ?>
                <div class="<?php echo $flash['type'] === 'success' ? 'alert-success' : 'alert-error'; ?>" role="alert">
                    <?php echo e($flash['message'] ?? ''); ?>
                </div>
            <?php } ?>

            <?php if ($error !== '') { ?>
                <div class="alert-error" role="alert">
                    <?php echo e($error); ?>
                </div>
            <?php } ?>

            <form class="auth-form" action="login.php" method="POST">
                <?php if ($next !== null) { ?>
                    <input type="hidden" name="next" value="<?php echo e($next); ?>">
                <?php } ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo old('email'); ?>"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary">Log In</button>
            </form>

            <p class="form-footer-link">
                Don't have an account yet? <a href="register.php">Register</a>
            </p>
        </section>
<?php
require_once 'includes/footer.php';
?>
