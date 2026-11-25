<?php
/**
 * PetPal - Veritabani ve Ortam Yapilandirmasi (Member 2)
 * Local (XAMPP) ve canli (InfinityFree) sunucu ayirimi.
 */

date_default_timezone_set('Europe/Istanbul');

$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
$isLocal = ($serverName === 'localhost' || $serverName === '127.0.0.1');

if ($isLocal) {
    $dbHost = 'localhost';
    $dbName = 'petpal';
    $dbUser = 'root';
    $dbPass = '';
} else {
    // Canli sunucu (InfinityFree) ayarlari - GitHub public repo oldugu icin sifre *** olarak birakildi
    $dbHost = 'sqlXXX.infinityfree.com';
    $dbName = 'if0_12345678_petpal';
    $dbUser = 'if0_12345678';
    $dbPass = '***';
}

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Veritabani baglantisi kurulamadi. Lutfen daha sonra tekrar deneyin.');
}
