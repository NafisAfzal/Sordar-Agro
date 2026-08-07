<?php
// One-time password rotation script, run from the Dockerfile CMD.
// Connects using the currently-configured DB_PASSWORD, then rotates
// the MySQL account to DB_NEW_PASSWORD. Removed from the image after use.

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$username = getenv('DB_USERNAME');
$currentPassword = getenv('DB_PASSWORD');
$newPassword = getenv('DB_NEW_PASSWORD');

if (!$newPassword) {
    echo "DB_NEW_PASSWORD not set, skipping rotation.\n";
    exit(0);
}

$pdo = new PDO(
    "mysql:host={$host};port={$port}",
    $username,
    $currentPassword
);

$currentUser = $pdo->query('SELECT CURRENT_USER()')->fetchColumn();
[$user, $userHost] = explode('@', $currentUser);

$quotedPassword = $pdo->quote($newPassword);
$pdo->exec("ALTER USER '{$user}'@'{$userHost}' IDENTIFIED BY {$quotedPassword}");
$pdo->exec('FLUSH PRIVILEGES');

echo "Rotated password for {$currentUser}\n";
