<?php
/**
 * PetPal - Oturum ve Kimlik Dogrulama Yardimcilari (Member 2)
 */

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Oturum acmis kullanicinin bilgilerini dondurur, giris yapilmadiysa null doner.
 */
function current_user(): ?array
{
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    return null;
}

/**
 * Kullanicinin giris yapip yapmadigini kontrol eder.
 */
function is_logged_in(): bool
{
    $user = current_user();
    return $user !== null && isset($user['user_id']);
}

/**
 * Giris yapilmamissa flash hata mesaji ekleyip login.php sayfasina yonlendirir.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

// TODO (Member 3): Yonetici yetkilendirmesi icin require_admin() fonksiyonu bu alana eklenecektir.
