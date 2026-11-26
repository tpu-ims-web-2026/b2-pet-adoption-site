<?php
// =====================================================================
// PetPal - includes/footer.php                     (Sahibi: Member 1)
// Her sayfanın ortak alt kısmı: <main> kapanışı, footer, JS dosyaları.
//
// Kullanım (her sayfanın en sonunda):
//   include 'includes/footer.php';    // admin sayfalarında: include '../includes/footer.php';
//
// header.php'de tanımlanan $base, $isAdminArea ve $currentPage
// değişkenlerini kullanır. Header'dan önce çağrılırsa varsayılanlar devreye girer.
// =====================================================================

$base        = $base ?? '';                           // header tanımlamadıysa kök klasör kabul et
$isAdminArea = $isAdminArea ?? ($base === '../');     // admin klasöründe miyiz?
$currentPage = $currentPage ?? basename($_SERVER['SCRIPT_NAME']); // açık olan dosyanın adı

// ---------------------------------------------------------------------
// Footer'daki küçük istatistik: kaç hayvan yuva bekliyor, kaçı sahiplendi
// (Değişken adları "footer" ile başlar ki sayfanın değişkenleriyle çakışmasın.)
// ---------------------------------------------------------------------
$footerStmt = $pdo->prepare(                          // prepared statement hazırla
    'SELECT status, COUNT(*) AS total
       FROM pets
      WHERE status IN (?, ?)
      GROUP BY status'                                // hidden durumundaki hayvanlar sayılmaz
);
$footerStmt->execute(['available', 'adopted']);       // iki durumu parametre olarak bağla ve çalıştır
$footerCounts = $footerStmt->fetchAll(PDO::FETCH_KEY_PAIR); // sonuç: ['available' => 12, 'adopted' => 5] gibi bir dizi

$footerAvailable = (int)($footerCounts['available'] ?? 0); // yuva bekleyen hayvan sayısı (yoksa 0)
$footerAdopted   = (int)($footerCounts['adopted'] ?? 0);   // sahiplendirilmiş hayvan sayısı (yoksa 0)

$footerYear = date('Y');                              // telif satırı için içinde bulunulan yıl
?>
</main><!-- header.php'de açılan main etiketi burada kapanır -->

<footer class="site-footer"><!-- semantik footer etiketi -->
    <div class="container footer-inner"><!-- içeriği ortalayan kapsayıcı (Flexbox ile sütunlar) -->

        <section class="footer-col"><!-- 1. sütun: site hakkında kısa bilgi -->
            <h2 class="footer-title">PetPal</h2><!-- sütun başlığı -->
            <p>A shelter network that helps animals find a loving home.</p><!-- kısa tanıtım cümlesi -->
            <p class="footer-stats"><!-- veritabanından gelen canlı sayılar -->
                <strong><?= $footerAvailable ?></strong> pets waiting for a home ·
                <strong><?= $footerAdopted ?></strong> already adopted<!-- sayılar (int) olduğu için doğrudan yazdırılabilir -->
            </p>
        </section>

        <section class="footer-col"><!-- 2. sütun: hızlı linkler -->
            <h2 class="footer-title">Explore</h2><!-- sütun başlığı -->
            <ul class="footer-links">
                <li><a href="<?= e($base) ?>index.php">Home</a></li><!-- ana sayfa -->
                <li><a href="<?= e($base) ?>pets.php">Available Pets</a></li><!-- hayvan listesi -->
                <li><a href="<?= e($base) ?>about.php">About Us</a></li><!-- hakkımızda -->
            </ul>
        </section>

        <section class="footer-col"><!-- 3. sütun: iletişim (e-posta yok, form ile) -->
            <h2 class="footer-title">Contact</h2><!-- sütun başlığı -->
            <p>Have a question? Send us a message through our <a href="<?= e($base) ?>about.php#contact">contact form</a>.</p><!-- yönerge gereği e-posta yerine form -->
            <p>PetPal Shelter, 42 Oak Street, Kadikoy, Istanbul</p><!-- uydurma adres (yönerge: örnek veriler uydurma olmalı) -->
        </section>

    </div>

    <div class="footer-bottom"><!-- en alttaki telif satırı -->
        <p>&copy; <?= e($footerYear) ?> PetPal. All names and data on this site are fictional.</p><!-- yıl otomatik güncellenir -->
    </div>
</footer>

<script src="<?= e($base) ?>js/pets.js"></script><!-- mobil menü, canlı filtre, resim önizleme (M1); her sayfada yüklenir -->
<script src="<?= e($base) ?>js/validation.js"></script><!-- kayıt, giriş, başvuru, profil form kontrolleri (M2) -->
<?php if ($isAdminArea || $currentPage === 'about.php'): // admin sayfaları ve iletişim formu olan about.php ?>
<script src="<?= e($base) ?>js/admin.js"></script><!-- onay pencereleri, tablo arama, iletişim formu kontrolü (M3) -->
<?php endif; ?>
</body>
</html>
