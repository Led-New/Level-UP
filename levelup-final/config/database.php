<?php
/**
 * Database Configuration - XAMPP Windows Version
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'levelup_life');
define('DB_USER', 'root');
define('DB_PASS', '8035w2389'); // Vazio para XAMPP padrão
define('DB_CHARSET', 'utf8mb4');

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage() . "<br><br>Verifique se:<br>1. O MySQL está rodando no XAMPP<br>2. O banco 'levelup_life' foi criado<br>3. As credenciais estão corretas");
}
