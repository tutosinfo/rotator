<?php
// random.php
$dbFile = __DIR__ . '/database.json';
$bufferFile = __DIR__ . '/buffer.txt';

// 1. Lecture rapide (Si le fichier n'existe pas, on arrête)
if (!file_exists($dbFile)) die();

$json = file_get_contents($dbFile);
$data = json_decode($json, true);

if (empty($data)) die();

// 2. Choix aléatoire
$randomId = array_rand($data);
$target = $data[$randomId]['url'];

// 3. Écriture dans le tampon (Rapide, pas de verrouillage complexe)
file_put_contents($bufferFile, $randomId . "\n", FILE_APPEND | LOCK_EX);

// 4. Redirection
header("Location: " . $target, true, 302);
exit;
?>