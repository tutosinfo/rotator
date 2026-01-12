<?php
// data_manager.php
$dbFile = __DIR__ . '/database.json';

// Lit les URLs
function getUrls() {
    global $dbFile;
    if (!file_exists($dbFile)) return [];
    $json = file_get_contents($dbFile);
    return json_decode($json, true) ?: [];
}

// Sauvegarde les URLs (avec verrouillage pour éviter les bugs)
function saveUrls($data) {
    global $dbFile;
    file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

// Ajoute une URL
function addUrl($url) {
    $data = getUrls();
    $id = uniqid(); 
    $data[$id] = [
        'id' => $id,
        'url' => trim($url),
        'hit' => 0
    ];
    saveUrls($data);
}
?>