<?php
require_once 'config.php';
require_once 'includes/auth.php';

$pageTitle = 'Register';
$flash = get_flash();

require_once 'includes/header.php';
?>
        <section class="form-card" aria-labelledby="register-heading">
            <h1 id="register-heading">Create Account</h1>
            <p class="form-subtitle">Join the PetPal community and track your adoption applications.</p>

            <?php if ($flash !== null) { ?>
                <div class="<?php echo $flash['type'] === 'success' ? 'alert-success' : 'alert-error'; ?>" role="alert">
                    <?php echo e($flash['message'] ?? ''); ?>
                </div>
            <?php } ?>

            <form class="auth-form" action="register.php" method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?php echo old('full_name'); ?>"
                        maxlength="100"
                        required
                    >
                </div>

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

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone (Optional)</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?php echo old('phone'); ?>"
                            maxlength="20"
                        >
                    </div>

                    <div class="form-group">
                        <label for="city">City (Optional)</label>
                        <input
                            type="text"
                            id="city"
                            name="city"
                            value="<?php echo old('city'); ?>"
                            maxlength="60"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input
                        type="password"
                        id="password_confirm"
                        name="password_confirm"
                        minlength="8"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary">Register</button>
            </form>

            <p class="form-footer-link">
                Already have an account? <a href="login.php">Log In</a>
            </p>
        </section>
<?php
require_once 'includes/footer.php';
?>
