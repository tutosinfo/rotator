<?php
// random.php
$dbFile = __DIR__ . '/database.json';
$bufferFile = __DIR__ . '/buffer.txt';
$velocityFile = __DIR__ . '/velocity_log.json';

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

// 4. Enregistrement du timestamp pour le calcul de vélocité
$now = time();
$velocityLog = [];
if (file_exists($velocityFile)) {
    $velocityLog = json_decode(file_get_contents($velocityFile), true) ?: [];
}
// Ajouter le timestamp actuel
$velocityLog[] = $now;
// Garder uniquement les 60 dernières secondes (nettoyage)
$cutoff = $now - 60;
$velocityLog = array_values(array_filter($velocityLog, function($t) use ($cutoff) {
    return $t >= $cutoff;
}));
file_put_contents($velocityFile, json_encode($velocityLog), LOCK_EX);

// 5. Redirection
header("Location: " . $target, true, 302);
exit;
?>