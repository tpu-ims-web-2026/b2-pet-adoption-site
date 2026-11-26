<?php
// =====================================================================
// PetPal - includes/header.php                     (Sahibi: Member 1)
// Her sayfanın ortak üst kısmı: <head>, logo, role göre değişen menü.
//
// Kullanım (her sayfanın en üstünde, yönerge bölüm 4'teki sıra):
//   require 'config.php';            // $pdo bağlantısını oluşturur
//   require 'includes/auth.php';     // session_start() ve giriş fonksiyonları
//   $pageTitle = 'Available Pets';   // tarayıcı sekmesindeki başlık
//   $base = '';                      // admin/ klasöründeki sayfalarda '../'
//   include 'includes/header.php';   // bu dosya
// =====================================================================

require_once __DIR__ . '/functions.php';              // e() fonksiyonu için; sayfa zaten yüklediyse tekrar yüklenmez

if (session_status() === PHP_SESSION_NONE) {          // auth.php oturumu henüz başlatmadıysa (yedek önlem)
    session_start();                                  // oturumu başlat
}

$base      = $base ?? '';                             // sayfa $base tanımlamadıysa kök klasör kabul et
$pageTitle = $pageTitle ?? 'Home';                    // sayfa başlık vermediyse varsayılan 'Home'

$isAdminArea = ($base === '../');                     // $base '../' ise admin/ klasöründeyiz demektir
$currentPage = ($isAdminArea ? 'admin/' : '')         // admin sayfalarında başa 'admin/' ekle
             . basename($_SERVER['SCRIPT_NAME']);     // açık olan dosyanın adı, örn. 'pets.php' veya 'admin/pets.php'

// ---------------------------------------------------------------------
// Giriş yapmış kullanıcıyı veritabanından oku
// (Rol, oturumdan değil veritabanından alınır: admin yetkisi alınan biri
//  bir sonraki sayfada admin menüsünü artık görmez.)
// Değişken adları "header" ile başlar ki sayfanın kendi $stmt, $user
// gibi değişkenlerinin üzerine yazılmasın.
// ---------------------------------------------------------------------
$headerUser = null;                                   // varsayılan: ziyaretçi (giriş yapılmamış)

$sessionUser = current_user();                        // Kağan'ın auth.php fonksiyonu: $_SESSION['user'] dizisini döndürür, giriş yoksa null

if ($sessionUser !== null && !empty($sessionUser['user_id'])) { // oturumda kullanıcı varsa
    $headerStmt = $pdo->prepare(                      // prepared statement hazırla (SQL injection'a karşı)
        'SELECT user_id, full_name, role
           FROM users
          WHERE user_id = ? AND is_active = 1
          LIMIT 1'                                    // sadece aktif hesaplar; pasif hesap ziyaretçi gibi görünür
    );
    $headerStmt->execute([(int)$sessionUser['user_id']]); // ? yerine ID'yi tam sayı olarak bağla ve çalıştır
    $headerUser = $headerStmt->fetch(PDO::FETCH_ASSOC) ?: null; // satır yoksa false yerine null kullan
}

$isLoggedIn = ($headerUser !== null);                 // kullanıcı giriş yapmış mı?
$isAdmin    = $isLoggedIn && $headerUser['role'] === 'admin'; // giriş yapmış ve rolü admin mi?

// ---------------------------------------------------------------------
// Admin menüsündeki sayı rozetleri (bekleyen başvuru, okunmamış mesaj)
// ---------------------------------------------------------------------
$headerPending = 0;                                   // bekleyen başvuru sayısı (varsayılan 0)
$headerUnread  = 0;                                   // okunmamış mesaj sayısı (varsayılan 0)

if ($isAdmin) {                                       // bu sorgular sadece admin için çalışır
    $headerStmt = $pdo->prepare(                      // bekleyen başvuruları sayan sorgu
        'SELECT COUNT(*) FROM adoption_applications WHERE status = ?'
    );
    $headerStmt->execute(['pending']);                // durum değerini parametre olarak ver
    $headerPending = (int)$headerStmt->fetchColumn(); // tek sütunluk sonucu tam sayıya çevir

    $headerStmt = $pdo->prepare(                      // okunmamış iletişim mesajlarını sayan sorgu
        'SELECT COUNT(*) FROM contact_messages WHERE is_read = ?'
    );
    $headerStmt->execute([0]);                        // is_read = 0 olanlar okunmamış mesajlardır
    $headerUnread = (int)$headerStmt->fetchColumn();  // sonucu tam sayıya çevir
}

// ---------------------------------------------------------------------
// Menü öğeleri: 'dosya yolu' => 'görünen ad'
// Herkesin gördüğü linkler önce, sonra role göre eklenenler
// ---------------------------------------------------------------------
$headerMenu = [
    'index.php' => 'Home',                            // ana sayfa (M1)
    'pets.php'  => 'Available Pets',                  // hayvan listesi (M1)
    'about.php' => 'About & Contact',                 // hakkımızda ve iletişim formu (M3)
];

if ($isAdmin) {                                       // yönetici menüsü
    $headerMenu['admin/index.php']        = 'Dashboard';     // istatistik paneli (M3)
    $headerMenu['admin/pets.php']         = 'Manage Pets';   // hayvan yönetimi (M1)
    $headerMenu['admin/applications.php'] = 'Applications';  // başvuru yönetimi (M3)
    $headerMenu['admin/users.php']        = 'Users';         // kullanıcı yönetimi (M3)
    $headerMenu['admin/messages.php']     = 'Messages';      // iletişim mesajları (M3)
    $headerMenu['profile.php']            = 'Profile';       // kendi profili (M2)
} elseif ($isLoggedIn) {                              // normal üye menüsü (role = user)
    $headerMenu['my_applications.php'] = 'My Applications';  // kendi başvuruları (M2)
    $headerMenu['profile.php']         = 'Profile';          // profil düzenleme (M2)
} else {                                              // ziyaretçi menüsü
    $headerMenu['login.php']    = 'Log in';           // giriş sayfası (M2)
    $headerMenu['register.php'] = 'Register';         // kayıt sayfası (M2)
}

$headerBadges = [                                     // hangi menü öğesinin yanında sayı rozeti çıkacak
    'admin/applications.php' => $headerPending,       // bekleyen başvuru sayısı
    'admin/messages.php'     => $headerUnread,        // okunmamış mesaj sayısı
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><!-- Türkçe karakterler dahil tüm karakterler için UTF-8 -->
    <meta name="viewport" content="width=device-width, initial-scale=1"><!-- telefonda doğru ölçekleme -->
    <title><?= e($pageTitle) ?> | PetPal</title><!-- sekme başlığı, e() ile güvenli yazdırılır -->
    <script>document.documentElement.classList.add('js');</script><!-- JS açıksa <html>'e "js" sınıfı ekler; mobil menü CSS'i buna bakar -->
    <link rel="icon" href="<?= e($base) ?>images/logo.png"><!-- sekme ikonu (kendi logomuz) -->
    <link rel="stylesheet" href="<?= e($base) ?>css/style.css"><!-- ana tasarım (M1) -->
    <link rel="stylesheet" href="<?= e($base) ?>css/forms.css"><!-- formlar ve durum rozetleri (M2) -->
<?php if ($isAdminArea): // admin klasöründeki sayfalarda ?>
    <link rel="stylesheet" href="<?= e($base) ?>css/admin.css"><!-- admin tabloları ve kartları (M3) -->
<?php endif; ?>
</head>
<body>

<header class="site-header"><!-- semantik header etiketi -->
    <div class="container header-inner"><!-- içeriği ortalayan kapsayıcı -->

        <a href="<?= e($base) ?>index.php" class="logo"><!-- logoya tıklayınca ana sayfaya git -->
            <img src="<?= e($base) ?>images/logo.png" alt="PetPal logo" width="40" height="40"><!-- logo görseli -->
            <span class="logo-text">PetPal</span><!-- site adı -->
        </a>

        <button type="button" class="menu-toggle" id="menu-toggle"
                aria-controls="main-nav" aria-expanded="false" aria-label="Open menu">&#9776;</button><!-- mobil menü butonu; js/pets.js açıp kapatır -->

        <nav id="main-nav" class="main-nav" aria-label="Main menu"><!-- semantik nav etiketi -->
            <ul>
<?php foreach ($headerMenu as $path => $label): // her menü öğesi için bir <li> ?>
<?php $isActive = ($currentPage === $path); // bu link şu an açık olan sayfa mı? ?>
                <li>
                    <a href="<?= e($base . $path) ?>"<?= $isActive ? ' class="active" aria-current="page"' : '' ?>><!-- aktif sayfa vurgulanır -->
                        <?= e($label) ?><!-- menü öğesinin adı -->
<?php if (!empty($headerBadges[$path])): // bu öğe için rozet sayısı 0'dan büyükse ?>
                        <span class="nav-badge"><?= (int)$headerBadges[$path] ?></span><!-- sayı rozeti -->
<?php endif; ?>
                    </a>
                </li>
<?php endforeach; ?>

<?php if ($isLoggedIn): // giriş yapmış kullanıcı için selamlama ve çıkış ?>
                <li class="nav-user">
                    Hi, <?= e($headerUser['full_name']) ?><!-- kullanıcının adı (XSS'e karşı e() ile) -->
<?php if ($isAdmin): ?>
                    <span class="role-badge">Admin</span><!-- admin olduğunu gösteren etiket -->
<?php endif; ?>
                </li>
                <li>
                    <a href="<?= e($base) ?>logout.php" class="nav-logout">Log out</a><!-- oturumu kapatır (M2) -->
                </li>
<?php endif; ?>
            </ul>
        </nav>

    </div>
</header>

<main class="container"><!-- sayfanın asıl içeriği burada başlar; includes/footer.php içinde kapanır -->
