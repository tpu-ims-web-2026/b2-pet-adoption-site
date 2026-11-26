<?php
// =====================================================================
// PetPal - pets.php (Available Pets)               (Sahibi: Member 1)
// Sahiplenilebilir hayvanların kart listesi ve GET filtreleri:
// isim (q), kategori, şehir, cinsiyet, boyut.
// WHERE kısmı koddaki SABİT parçalardan kurulur ("p.city = ?" gibi);
// kullanıcının yazdığı değerler SQL metnine asla eklenmez, sadece
// execute([...]) ile ? yerlerine verilir.
// =====================================================================

require 'config.php';                                 // veritabanı bağlantısı ($pdo) - Member 2
require 'includes/auth.php';                          // oturum ve giriş fonksiyonları - Member 2

$pageTitle = 'Available Pets';                        // sekme başlığı
$base      = '';                                      // kök klasördeyiz

// ---------------------------------------------------------------------
// GET filtrelerini oku (formu yeniden doldurmak için)
// is_string kontrolü: ?q[]=x gibi dizi gönderilirse hata vermesin diye
// ---------------------------------------------------------------------
$filters = [];                                        // seçilen filtreler burada toplanır
foreach (['q', 'category', 'city', 'gender', 'size'] as $key) { // beş filtre alanı
    $filters[$key] = isset($_GET[$key]) && is_string($_GET[$key]) // değer var ve metin mi?
        ? trim($_GET[$key])                           // evetse baştaki/sondaki boşlukları sil
        : '';                                         // yoksa boş metin
}

// ---------------------------------------------------------------------
// Filtre seçenekleri
// ---------------------------------------------------------------------
$stmt = $pdo->prepare(                                // tüm kategoriler, ada göre sıralı
    'SELECT category_id, name FROM pet_categories ORDER BY name'
);
$stmt->execute();                                     // parametre yok, yine de prepare + execute kuralına uyulur
$categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);   // sonuç: [1 => 'Dog', 2 => 'Cat', ...]

$stmt = $pdo->prepare(                                // sahiplenilebilir hayvanların bulunduğu şehirler (tekrarsız)
    'SELECT DISTINCT city FROM pets WHERE status = ? ORDER BY city'
);
$stmt->execute(['available']);                        // sadece listede görünecek hayvanların şehirleri
$cities = $stmt->fetchAll(PDO::FETCH_COLUMN);         // sonuç: ['Ankara', 'Antalya', ...]

$genders = ['male' => 'Male', 'female' => 'Female', 'unknown' => 'Unknown']; // pets.gender ENUM değerleri (sabit)
$sizes   = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];   // pets.size ENUM değerleri (sabit)

// ---------------------------------------------------------------------
// Gelen filtreleri kontrol et: sadece listede olan değerler kabul edilir.
// Geçersiz bir değer (örn. ?gender=abc) sessizce yok sayılır.
// ---------------------------------------------------------------------
if (!isset($categories[$filters['category']])) {      // kategori id'si tabloda yoksa
    $filters['category'] = '';                        // filtreyi kaldır
}
if (!in_array($filters['city'], $cities, true)) {     // şehir listede yoksa (tam eşleşme)
    $filters['city'] = '';
}
if (!isset($genders[$filters['gender']])) {           // cinsiyet ENUM değerlerinden biri değilse
    $filters['gender'] = '';
}
if (!isset($sizes[$filters['size']])) {               // boyut ENUM değerlerinden biri değilse
    $filters['size'] = '';
}
$filters['q'] = mb_substr($filters['q'], 0, 60, 'UTF-8'); // isim araması en fazla 60 karakter (name sütunu kadar)

// ---------------------------------------------------------------------
// WHERE kısmını sabit parçalardan kur, değerleri $params dizisine ekle
// ---------------------------------------------------------------------
$where  = ['p.status = ?'];                           // her zaman: sadece sahiplenilebilir hayvanlar
$params = ['available'];                              // ilk ? için değer

if ($filters['q'] !== '') {                           // isim araması yazıldıysa
    $where[]  = 'p.name LIKE ?';                      // isimde geçen metni ara
    $params[] = '%' . addcslashes($filters['q'], '\\%_') . '%'; // % ve _ karakterlerini kaçır ki joker gibi davranmasın
}
if ($filters['category'] !== '') {                    // kategori seçildiyse
    $where[]  = 'p.category_id = ?';
    $params[] = (int)$filters['category'];            // tam sayı olarak ver
}
if ($filters['city'] !== '') {                        // şehir seçildiyse
    $where[]  = 'p.city = ?';
    $params[] = $filters['city'];
}
if ($filters['gender'] !== '') {                      // cinsiyet seçildiyse
    $where[]  = 'p.gender = ?';
    $params[] = $filters['gender'];
}
if ($filters['size'] !== '') {                        // boyut seçildiyse
    $where[]  = 'p.size = ?';
    $params[] = $filters['size'];
}

$hasFilters = count($where) > 1;                      // kullanıcı en az bir filtre seçti mi? (boş mesaj için)

$stmt = $pdo->prepare(                                // hayvan listesi sorgusu
    'SELECT p.pet_id, p.name, p.category_id, p.gender, p.size, p.city, p.image, p.status,
            c.name AS category_name
       FROM pets p
       JOIN pet_categories c ON c.category_id = p.category_id
      WHERE ' . implode(' AND ', $where) . '
      ORDER BY p.created_at DESC'                     // $where içinde sadece koddaki sabit metinler var, kullanıcı verisi yok
);
$stmt->execute($params);                              // filtre değerleri ? yerlerine sırayla bağlanır
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);            // tüm sonuçları al

$petCount = count($pets);                             // bulunan hayvan sayısı

include 'includes/header.php';                        // ortak üst kısım
?>

<h1 class="page-title">Available Pets</h1><!-- sayfa başlığı -->
<p class="page-intro">Every animal below is looking for a home. Use the filters to find the right match.</p><!-- kısa açıklama -->

<form class="filter-bar" action="pets.php" method="get" role="search"><!-- filtre formu: arama olduğu için GET -->

    <div class="filter-field">
        <label for="filter-q">Name</label><!-- isim arama etiketi -->
        <input type="search" id="filter-q" name="q" value="<?= e($filters['q']) ?>" maxlength="60" placeholder="e.g. Luna"><!-- yazılan değer e() ile geri doldurulur; js/pets.js bu kutuyla anlık filtre yapar -->
    </div>

    <div class="filter-field">
        <label for="filter-category">Category</label><!-- tür etiketi -->
        <select id="filter-category" name="category">
            <option value="">All</option><!-- boş değer = filtre yok -->
<?php foreach ($categories as $id => $name): // her kategori için bir seçenek ?>
            <option value="<?= (int)$id ?>"<?= $filters['category'] === (string)$id ? ' selected' : '' ?>><?= e($name) ?></option><!-- seçili olan işaretlenir -->
<?php endforeach; ?>
        </select>
    </div>

    <div class="filter-field">
        <label for="filter-city">City</label><!-- şehir etiketi -->
        <select id="filter-city" name="city">
            <option value="">All</option>
<?php foreach ($cities as $city): // her şehir için bir seçenek ?>
            <option value="<?= e($city) ?>"<?= $filters['city'] === $city ? ' selected' : '' ?>><?= e($city) ?></option>
<?php endforeach; ?>
        </select>
    </div>

    <div class="filter-field">
        <label for="filter-gender">Gender</label><!-- cinsiyet etiketi -->
        <select id="filter-gender" name="gender">
            <option value="">All</option>
<?php foreach ($genders as $value => $label): // her cinsiyet için bir seçenek ?>
            <option value="<?= e($value) ?>"<?= $filters['gender'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
        </select>
    </div>

    <div class="filter-field">
        <label for="filter-size">Size</label><!-- boyut etiketi -->
        <select id="filter-size" name="size">
            <option value="">All</option>
<?php foreach ($sizes as $value => $label): // her boyut için bir seçenek ?>
            <option value="<?= e($value) ?>"<?= $filters['size'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Filter</button><!-- filtreleri uygula (sunucu tarafı) -->
    <a href="pets.php" class="btn btn-secondary">Clear</a><!-- tüm filtreleri sıfırla -->
</form>

<p class="result-count" id="result-count"><?= $petCount ?> <?= $petCount === 1 ? 'pet' : 'pets' ?> found</p><!-- sonuç sayısı; js/pets.js anlık filtrede günceller -->

<?php if ($petCount === 0 && $hasFilters): // filtre seçili ama uyan hayvan yok ?>
<p class="empty-state">No pets match these filters. Try removing one of them or <a href="pets.php">see all pets</a>.</p><!-- dostça boş sonuç mesajı -->
<?php elseif ($petCount === 0): // filtre yok ve hiç sahiplenilebilir hayvan yok ?>
<p class="empty-state">No pets are waiting for a home right now. Please check back soon.</p><!-- liste tamamen boş -->
<?php else: // sonuç varsa kartları göster ?>
<div class="pet-grid" id="pet-grid"><!-- kart ızgarası; js/pets.js anlık filtre için bu id'yi kullanır -->
<?php foreach ($pets as $pet): // her hayvan için bir kart ?>
<?php $petImage = $pet['image'] ? 'uploads/pets/' . $pet['image'] : 'images/no-photo.png'; // fotoğraf yoksa varsayılan görsel ?>
    <article class="pet-card"
             data-name="<?= e(mb_strtolower($pet['name'], 'UTF-8')) ?>"
             data-category="<?= (int)$pet['category_id'] ?>"
             data-city="<?= e($pet['city']) ?>"
             data-gender="<?= e($pet['gender']) ?>"
             data-size="<?= e($pet['size']) ?>"><!-- data-* değerleri JS anlık filtresi için (isim küçük harfle) -->
        <div class="pet-card-img">
            <img src="<?= e($petImage) ?>" alt="Photo of <?= e($pet['name']) ?>" loading="lazy"><!-- hayvanın fotoğrafı -->
            <span class="badge badge-<?= e($pet['status']) ?>"><?= e(ucfirst($pet['status'])) ?></span><!-- durum rozeti -->
        </div>
        <div class="pet-card-body">
            <h2 class="pet-card-title"><?= e($pet['name']) ?></h2><!-- hayvanın adı (sayfada h1'den sonra h2) -->
            <p class="pet-card-meta"><?= e($pet['category_name']) ?> · <?= e(ucfirst($pet['gender'])) ?> · <?= e(ucfirst($pet['size'])) ?> · <?= e($pet['city']) ?></p><!-- tür · cinsiyet · boyut · şehir -->
            <a href="pet.php?id=<?= (int)$pet['pet_id'] ?>" class="pet-card-link">View details &rarr;</a><!-- detay sayfasına link -->
        </div>
    </article>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; // ortak alt kısım ?>
