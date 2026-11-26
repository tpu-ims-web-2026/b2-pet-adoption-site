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
 * Acik yonlendirme (open-redirect) saldirilarini onlemek icin yalnizca guvenli
 * yerel yonlendirme yollarini kabul eder; guvensizse null dondurur.
 */
function sanitize_next_url(?string $next): ?string
{
    if ($next === null) {
        return null;
    }

    $next = trim($next);
    if ($next === '') {
        return null;
    }

    // Kontrol karakterleri, satir sonlari ve ters egik cizgi (\) reddedilir
    if (preg_match('/[\x00-\x1F\x7F\\\\]/', $next) === 1) {
        return null;
    }

    // Protokol-goreceli (//example.com) URL'ler reddedilir
    if (str_starts_with($next, '//')) {
        return null;
    }

    $parts = parse_url($next);
    if ($parts === false) {
        return null;
    }

    // Harici sema, alan adi, kullanici bilgisi veya port iceren URL'ler reddedilir
    if (
        isset($parts['scheme'])
        || isset($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['port'])
    ) {
        return null;
    }

    $path = $parts['path'] ?? '';
    if ($path === '' || str_starts_with($path, '//') || str_contains($path, ':')) {
        return null;
    }

    return $next;
}

/**
 * Giris yapilmamissa flash hata mesaji ekleyip mevcut sayfa bilgisiyle login.php'ye yonlendirir.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $safeNext = sanitize_next_url(is_string($requestUri) ? $requestUri : null);

        if ($safeNext !== null) {
            redirect('login.php?next=' . rawurlencode($safeNext));
        }

        redirect('login.php');
    }
}

// TODO (Member 3): Yonetici yetkilendirmesi icin require_admin() fonksiyonu bu alana eklenecektir.
