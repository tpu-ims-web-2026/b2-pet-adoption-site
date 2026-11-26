<?php
require_once 'config.php';
require_once 'includes/auth.php';

$base = '';
$pageTitle = 'About & Contact - PetPal';
$flash = get_flash();
$hasSharedHeader = file_exists('includes/header.php');

if ($hasSharedHeader) {
    include 'includes/header.php';
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Main Navigation">
            <a class="site-brand" href="index.php">PetPal</a>
            <ul class="nav-links">
                <li><a href="about.php">About &amp; Contact</a></li>
                <li><a href="login.php">Log In</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>
    <main class="site-main">
<?php
}
?>
        <div class="form-card">
            <section aria-labelledby="about-heading">
                <h1 id="about-heading">About PetPal</h1>
                <p class="form-subtitle">Connecting shelter animals with safe, permanent homes.</p>
                <p>
                    PetPal is a community animal shelter network where visitors can browse cats, dogs, birds,
                    rabbits, and other pets waiting for adoption. Registered members can submit adoption
                    applications online and follow every step of the review process.
                </p>
                <p>
                    To keep every adoption fair and transparent, each pet can be adopted by only one member.
                    Once our shelter staff approves an application, the pet is marked as adopted and any other
                    pending applications for that pet are closed automatically.
                </p>
                <ul>
                    <li><strong>Shelter Office:</strong> 42 Oak Street, Kadikoy, Istanbul</li>
                    <li><strong>Phone:</strong> +90 555 000 0000</li>
                    <li><strong>Visiting Hours:</strong> Monday &ndash; Saturday, 10:00 &ndash; 18:00</li>
                </ul>
            </section>

            <section aria-labelledby="contact-heading">
                <h2 id="contact-heading">Contact Us</h2>
                <p class="form-subtitle">Have a question about a pet, adoption, or volunteering? Send us a message.</p>

                <?php if ($flash !== null) { ?>
                    <div class="<?php echo $flash['type'] === 'success' ? 'alert-success' : 'alert-error'; ?>" role="alert">
                        <?php echo e($flash['message'] ?? ''); ?>
                    </div>
                <?php } ?>

                <form class="auth-form" id="contact-form" action="about.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?php echo old('name'); ?>"
                                maxlength="100"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?php echo old('email'); ?>"
                                maxlength="150"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            value="<?php echo old('subject'); ?>"
                            maxlength="120"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="body">Message</label>
                        <textarea
                            id="body"
                            name="body"
                            rows="5"
                            maxlength="1000"
                            required
                        ><?php echo old('body'); ?></textarea>
                        <small id="body-counter" class="form-subtitle">Maximum 1000 characters.</small>
                    </div>

                    <button type="submit" class="btn-primary">Send Message</button>
                </form>
            </section>
        </div>
<?php
if (file_exists('includes/footer.php')) {
    include 'includes/footer.php';
} else {
?>
    </main>
    <footer class="site-footer">
        <p>&copy; <?php echo e(date('Y')); ?> PetPal Pet Adoption Platform</p>
    </footer>
</body>
</html>
<?php
}
?>
