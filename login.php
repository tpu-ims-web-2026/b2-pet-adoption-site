<?php
require_once 'config.php';
require_once 'includes/auth.php';

$pageTitle = 'Giris Yap - PetPal';
$flash = get_flash();
$hasSharedHeader = file_exists('includes/header.php');

if ($hasSharedHeader) {
    include 'includes/header.php';
} else {
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Ana Menu">
            <a class="site-brand" href="index.php">PetPal</a>
            <ul class="nav-links">
                <li><a href="login.php">Giris Yap</a></li>
                <li><a href="register.php">Kayit Ol</a></li>
            </ul>
        </nav>
    </header>
    <main class="site-main">
<?php
}
?>
        <section class="form-card" aria-labelledby="login-heading">
            <h1 id="login-heading">Giris Yap</h1>
            <p class="form-subtitle">Hesabiniza erismek icin e-posta adresinizi ve sifrenizi girin.</p>

            <?php if ($flash !== null) { ?>
                <div class="<?php echo $flash['type'] === 'success' ? 'alert-success' : 'alert-error'; ?>" role="alert">
                    <?php echo e($flash['message'] ?? ''); ?>
                </div>
            <?php } ?>

            <form class="auth-form" action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">E-posta Adresi</label>
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
                    <label for="password">Sifre</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                    >
                </div>

                <button type="submit" class="btn-primary">Giris Yap</button>
            </form>

            <p class="form-footer-link">
                Henuz hesabiniz yok mu? <a href="register.php">Kayit Ol</a>
            </p>
        </section>
<?php
if (file_exists('includes/footer.php')) {
    include 'includes/footer.php';
} else {
?>
    </main>
    <footer class="site-footer">
        <p>&copy; <?php echo e(date('Y')); ?> PetPal Evcil Hayvan Sahiplendirme Platformu</p>
    </footer>
</body>
</html>
<?php
}
?>
