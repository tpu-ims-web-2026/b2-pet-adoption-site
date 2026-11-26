<?php
/**
 * PetPal - Ortak Yardimci Fonksiyonlar (Member 2)
 */

/**
 * XSS korumasi icin metni HTML ozel karakterlerinden kacirir.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Kullaniciyi belirtilen goreceli yola yonlendirir ve betigi sonlandirir.
 */
function redirect(string $path): void
{
    header("Location: $path");
    exit;
}

/**
 * Oturum tabanli tek kullanimlik bildirim (flash) mesaji kaydeder.
 *
 * @param string $type    'success' veya 'error'
 * @param string $message Kullaniciya gosterilecek mesaj
 */
function set_flash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Kayitli flash mesajini dondurur ve oturumdan temizler.
 */
function get_flash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * Hatali form gonderiminde kullanicinin girdigi POST degerini guvenli sekilde geri dondurur.
 */
function old(string $key, string $default = ''): string
{
    if (isset($_POST[$key]) && is_scalar($_POST[$key])) {
        return e((string) $_POST[$key]);
    }

    return e($default);
}
