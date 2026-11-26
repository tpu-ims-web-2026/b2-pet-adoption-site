<?php
// =====================================================================
// PetPal - pet.php (Pet Details)                   (Sahibi: Member 1)
// Kullanım: pet.php?id=7
// Hayvan bilgisi pets + pet_categories tablolarından JOIN ile gelir.
// Buton mantığı (imza özelliğindeki M1 payı):
//   available + giriş yapmış  -> "Apply to adopt"
//   available + ziyaretçi     -> "Log in to apply"
//   adopted                   -> "Already adopted" (buton yok)
//   hidden                    -> admin değilse "not found"
// =====================================================================

require 'config.php';                                 // veritabanı bağlantısı ($pdo) - Member 2
require 'includes/auth.php';                          // oturum ve is_logged_in() - Member 2

$base = '';                                           // kök klasördeyiz

// ---------------------------------------------------------------------
// ?id= değerini güvenli şekilde oku: sadece 1 veya daha büyük tam sayı kabul edilir
// ---------------------------------------------------------------------
$petId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],                  // 0, eksi sayı, harf gibi değerler geçersiz
]);                                                   // geçersizse false, hiç yoksa null döner

// ---------------------------------------------------------------------
// Hayvanı veritabanından oku (id geçerliyse)
// Örnek veride denemek için: 1 = available, 9 = adopted, 10 = hidden
// ---------------------------------------------------------------------
$pet = null;                                          // varsayılan: bulunamadı

if ($petId) {                                         // id geçerli bir tam sayıysa sorgula (false/null ise sorguya gerek yok)
    $stmt = $pdo->prepare(                            // prepared statement hazırla
        'SELECT p.pet_id, p.name, p.breed, p.gender, p.age_months, p.size, p.city,
                p.description, p.vaccinated, p.image, p.status,
                c.name AS category_name
           FROM pets p
           JOIN pet_categories c ON c.category_id = p.category_id
          WHERE p.pet_id = ?
          LIMIT 1'                                    // id birincil anahtar, en fazla bir satır
    );
    $stmt->execute([$petId]);                         // id'yi ? yerine bağla ve çalıştır
    $pet = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;    // satır yoksa false yerine null
}

$viewerIsAdmin = (current_user()['role'] ?? '') === 'admin'; // sayfaya bakan kişi admin mi? (rol oturumda saklanır)

if ($pet !== null && $pet['status'] === 'hidden' && !$viewerIsAdmin) { // gizli hayvanı admin olmayan biri açtıysa
    $pet = null;                                      // hiç yokmuş gibi davran
}

if ($pet === null) {                                  // hayvan bulunamadıysa
    http_response_code(404);                          // tarayıcıya 404 Not Found durum kodu gönder (çıktıdan önce olmalı)
    $pageTitle = 'Pet not found';                     // sekme başlığı
} else {
    $pageTitle = $pet['name'];                        // sekme başlığı hayvanın adı

    // Yaşı "1 year 3 months" biçimine çevir (veritabanında ay olarak saklanır)
    $years    = intdiv($pet['age_months'], 12);       // tam yıl sayısı
    $months   = $pet['age_months'] % 12;              // kalan ay sayısı
    $ageParts = [];                                   // yazının parçaları
    if ($years > 0) {                                 // en az 1 yıl varsa
        $ageParts[] = $years . ($years === 1 ? ' year' : ' years');      // "1 year" veya "2 years"
    }
    if ($months > 0 || $years === 0) {                // ay varsa ya da 1 yaşından küçükse
        $ageParts[] = $months . ($months === 1 ? ' month' : ' months');  // "1 month" veya "3 months"
    }
    $ageText = implode(' ', $ageParts);               // parçaları birleştir: "1 year 3 months"

    $petImage = $pet['image'] ? 'uploads/pets/' . $pet['image'] : 'images/no-photo.png'; // fotoğraf yoksa varsayılan görsel

    $loginUrl = 'login.php?next=' . urlencode('apply.php?pet_id=' . $pet['pet_id']); // girişten sonra başvuru formuna dönmek için
}

include 'includes/header.php';                        // ortak üst kısım
?>

<?php if ($pet === null): // hayvan bulunamadı ?>
<section class="not-found"><!-- 404 mesajı -->
    <h1 class="page-title">Pet not found</h1>
    <p>This pet does not exist or is no longer listed.</p>
    <p><a href="pets.php" class="btn btn-primary">Back to all pets</a></p><!-- listeye geri dön -->
</section>

<?php else: // hayvan bulundu ?>
<p><a href="pets.php">&larr; Back to all pets</a></p><!-- listeye geri dön linki -->

<article class="pet-detail"><!-- fotoğraf ve bilgiler yan yana (Flexbox) -->

    <div class="pet-detail-photo">
        <img src="<?= e($petImage) ?>" alt="Photo of <?= e($pet['name']) ?>"><!-- büyük fotoğraf -->
    </div>

    <div class="pet-detail-info">
        <h1 class="page-title"><?= e($pet['name']) ?></h1><!-- hayvanın adı -->
        <span class="badge badge-<?= e($pet['status']) ?>"><?= e(ucfirst($pet['status'])) ?></span><!-- durum rozeti: Available / Adopted / Hidden -->

        <dl class="pet-facts"><!-- bilgi kutucukları (tanım listesi) -->
            <div><dt>Category</dt><dd><?= e($pet['category_name']) ?></dd></div><!-- tür -->
            <div><dt>Breed</dt><dd><?= e($pet['breed'] ?? 'Unknown') ?></dd></div><!-- cins; boşsa "Unknown" -->
            <div><dt>Gender</dt><dd><?= e(ucfirst($pet['gender'])) ?></dd></div><!-- cinsiyet -->
            <div><dt>Age</dt><dd><?= e($ageText) ?></dd></div><!-- yaş: "1 year 3 months" -->
            <div><dt>Size</dt><dd><?= e(ucfirst($pet['size'])) ?></dd></div><!-- boyut -->
            <div><dt>City</dt><dd><?= e($pet['city']) ?></dd></div><!-- şehir -->
            <div><dt>Vaccinated</dt><dd><?= $pet['vaccinated'] ? 'Yes' : 'No' ?></dd></div><!-- aşılı mı -->
        </dl>

        <h2>About <?= e($pet['name']) ?></h2><!-- açıklama başlığı -->
        <p><?= nl2br(e($pet['description'])) ?></p><!-- önce e() ile güvenli hale getir, sonra satır sonlarını <br> yap -->

        <div class="pet-detail-actions"><!-- başvuru butonu alanı -->
<?php if ($pet['status'] === 'available' && is_logged_in()): // sahiplenilebilir ve giriş yapmış ?>
            <a href="apply.php?pet_id=<?= (int)$pet['pet_id'] ?>" class="btn btn-primary">Apply to adopt</a><!-- başvuru formuna git (M2) -->
<?php elseif ($pet['status'] === 'available'): // sahiplenilebilir ama ziyaretçi ?>
            <a href="<?= e($loginUrl) ?>" class="btn btn-primary">Log in to apply</a><!-- girişten sonra başvuru formuna döner -->
<?php elseif ($pet['status'] === 'adopted'): // zaten sahiplenilmiş ?>
            <p class="adopted-note">Already adopted. <?= e($pet['name']) ?> has found a home!</p><!-- buton gösterilmez -->
<?php else: // gizli (sadece admin buraya gelebilir) ?>
            <p class="adopted-note">This pet is hidden from visitors.</p><!-- admin için bilgi notu -->
<?php endif; ?>
        </div>
    </div>

</article>
<?php endif; ?>

<?php include 'includes/footer.php'; // ortak alt kısım ?>
