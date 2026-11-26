<?php
// =====================================================================
// PetPal - index.php (Home)                        (Sahibi: Member 1)
// Karşılama alanı, hızlı arama ve en yeni 6 sahiplenilebilir hayvan.
// Veriler pets ve pet_categories tablolarından JOIN ile gelir.
// =====================================================================

require 'config.php';                                 // veritabanı bağlantısı ($pdo) - Member 2
require 'includes/auth.php';                          // oturum ve giriş fonksiyonları - Member 2

$pageTitle = 'Home';                                  // sekme başlığı
$base      = '';                                      // kök klasördeyiz, yollar başına bir şey eklenmez

// ---------------------------------------------------------------------
// En yeni 6 sahiplenilebilir hayvan (kategori adı JOIN ile gelir)
// LIMIT 6 sabit bir sayı olduğu için SQL içinde yazılabilir; dışarıdan
// gelen hiçbir değer SQL metnine eklenmez, status bile ? ile verilir.
// ---------------------------------------------------------------------
$stmt = $pdo->prepare(                                // prepared statement hazırla
    'SELECT p.pet_id, p.name, p.gender, p.city, p.image, p.status,
            c.name AS category_name
       FROM pets p
       JOIN pet_categories c ON c.category_id = p.category_id
      WHERE p.status = ?
      ORDER BY p.created_at DESC
      LIMIT 6'                                        // en yeni eklenen 6 hayvan
);
$stmt->execute(['available']);                        // sadece sahiplenilebilir olanlar (adopted ve hidden gösterilmez)
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);            // tüm satırları ilişkisel dizi olarak al (HTML bu diziyi kullanır)

include 'includes/header.php';                        // ortak üst kısım (<head>, logo, menü)
?>

<section class="hero"><!-- karşılama alanı -->
    <div class="hero-text">
        <h1>Find your new best friend</h1><!-- sayfanın tek h1 başlığı -->
        <p>Dogs, cats, rabbits and birds from PetPal shelters are waiting for a loving home. Browse the animals, read their stories and apply to adopt.</p><!-- kısa tanıtım -->

        <form class="quick-search" action="pets.php" method="get" role="search"><!-- hızlı arama: sonuçlar pets.php'de gösterilir (arama GET kullanır) -->
            <label for="quick-q" class="visually-hidden">Search pets by name</label><!-- ekran okuyucu için görünmez etiket -->
            <input type="search" id="quick-q" name="q" placeholder="Search by name, e.g. Luna" maxlength="60"><!-- pets.php'deki 'q' filtresine gider -->
            <button type="submit" class="btn btn-primary">Search</button><!-- buton stili forms.css'te (M2) -->
        </form>
    </div>
</section>

<section aria-labelledby="newest-title"><!-- en yeni hayvanlar bölümü -->
    <div class="section-header">
        <h2 id="newest-title">Newest pets</h2><!-- bölüm başlığı -->
        <a href="pets.php">See all pets &rarr;</a><!-- tüm hayvanlar sayfasına link -->
    </div>

<?php if (count($pets) === 0): // hiç hayvan yoksa ?>
    <p class="empty-state">No pets are waiting right now. Please check back soon.</p><!-- dostça boş mesaj -->
<?php else: // hayvan varsa kartları göster ?>
    <div class="pet-grid"><!-- Flexbox kart ızgarası (style.css) -->
<?php foreach ($pets as $pet): // her hayvan için bir kart ?>
<?php $petImage = $pet['image'] ? 'uploads/pets/' . $pet['image'] : 'images/no-photo.png'; // fotoğraf yoksa varsayılan görsel ?>
        <article class="pet-card"><!-- tek bir hayvan kartı -->
            <div class="pet-card-img">
                <img src="<?= e($petImage) ?>" alt="Photo of <?= e($pet['name']) ?>" loading="lazy"><!-- fotoğraf; lazy = ekrana gelince yüklenir -->
                <span class="badge badge-<?= e($pet['status']) ?>"><?= e(ucfirst($pet['status'])) ?></span><!-- durum rozeti (renkleri forms.css'te) -->
            </div>
            <div class="pet-card-body">
                <h3 class="pet-card-title"><?= e($pet['name']) ?></h3><!-- hayvanın adı -->
                <p class="pet-card-meta"><?= e($pet['category_name']) ?> · <?= e(ucfirst($pet['gender'])) ?> · <?= e($pet['city']) ?></p><!-- tür · cinsiyet · şehir -->
                <a href="pet.php?id=<?= (int)$pet['pet_id'] ?>" class="pet-card-link">View details &rarr;</a><!-- detay sayfasına link; id tam sayıya çevrilir -->
            </div>
        </article>
<?php endforeach; ?>
    </div>
<?php endif; ?>
</section>

<?php include 'includes/footer.php'; // ortak alt kısım (footer ve JS dosyaları) ?>
