<?php
// index.php - Gesturl Lite v1.0
require_once 'data_manager.php';

// --- 1. SYNCHRONISATION (Temps Réel) ---
$bufferFile = __DIR__ . '/buffer.txt';
if (file_exists($bufferFile) && filesize($bufferFile) > 0) {
    $processing = $bufferFile . '.processing';
    if (@rename($bufferFile, $processing)) {
        $hits = file($processing, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!empty($hits)) {
            $counts = array_count_values($hits);
            $data = getUrls();
            $hasChanges = false;
            foreach ($counts as $id => $nb) {
                if (isset($data[$id])) {
                    $data[$id]['hit'] += $nb;
                    $hasChanges = true;
                }
            }
            if ($hasChanges) saveUrls($data);
        }
        @unlink($processing);
    }
}

// --- 2. TRAITEMENT DES ACTIONS ---

if (isset($_POST['submit']) && !empty($_POST['url'])) {
    addUrl($_POST['url']);
    $msg = "URL ajoutée !";
}

if (isset($_POST['import']) && !empty($_POST['urls'])) {
    $lines = explode("\n", $_POST['urls']);
    $count = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if(!empty($line)) {
            addUrl($line);
            $count++;
        }
    }
    $msg = "$count liens importés !";
}

if (isset($_POST['clear_all'])) {
    saveUrls([]);
    $msg = "Toutes les URLs ont été supprimées.";
}

if (isset($_GET['delete'])) {
    $data = getUrls();
    $idToDelete = trim($_GET['delete']);
    if (isset($data[$idToDelete])) {
        unset($data[$idToDelete]);
        saveUrls($data);
    }
    header("Location: index.php");
    exit;
}
if (isset($_GET['reset'])) {
    $data = getUrls();
    if (isset($data[$_GET['reset']])) {
        $data[$_GET['reset']]['hit'] = 0;
        saveUrls($data);
    }
    header("Location: index.php");
    exit;
}

// Lien Random
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$randomLink = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$randomLink = rtrim($randomLink, '/\\') . "/random.php";

$urls = getUrls();

// --- CALCUL DE VÉLOCITÉ ---
$velocityFile = __DIR__ . '/velocity_log.json';
$velocityCount = 0;
$velocityLevel = 'Faible';
$velocityColor = '#888';

if (file_exists($velocityFile)) {
    $velocityLog = json_decode(file_get_contents($velocityFile), true) ?: [];
    $now = time();
    $cutoff = $now - 20; // 20 dernières secondes

    // Compter les hits des 20 dernières secondes
    $velocityCount = count(array_filter($velocityLog, function($t) use ($cutoff) {
        return $t >= $cutoff;
    }));

    // Déterminer le niveau de vélocité
    if ($velocityCount >= 100) {
        $velocityLevel = 'Très élevé';
        $velocityColor = '#e74c3c'; // Rouge
    } elseif ($velocityCount >= 50) {
        $velocityLevel = 'Élevé';
        $velocityColor = '#f39c12'; // Orange
    } elseif ($velocityCount >= 30) {
        $velocityLevel = 'Moyen';
        $velocityColor = '#3498db'; // Bleu
    } else {
        $velocityLevel = 'Faible';
        $velocityColor = '#27ae60'; // Vert
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gesturl Lite</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 20px; padding-bottom: 50px; }
        h1 { margin-bottom: 20px; }
        form { margin-bottom: 15px; }
        label { font-size: 1.1em; }
        textarea { width: 600px; height: 150px; display: block; margin: 5px auto; }
        
        /* Table styles */
        table { margin: 30px auto; border-collapse: collapse; width: 95%; max-width: 1400px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        
        /* Boutons et Messages */
        .msg { color: green; font-weight: bold; margin-bottom: 20px; }
        .random-box { margin-bottom: 20px; font-size: 0.9em; color: #555; background: #f9f9f9; padding: 10px; display: inline-block; border: 1px solid #eee;}
        
        .btn-refresh {
            background-color: #008CBA; 
            color: white; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 4px; 
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }
        .btn-refresh:hover { background-color: #007B9A; }

        /* Footer Style */
        .footer {
            margin-top: 60px;
            color: #aaa;
            font-size: 0.8em;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
</head>
<body>

    <div style="position: absolute; top: 20px; right: 20px;">
        <a href="index.php" class="btn-refresh">🔄 Actualiser les Hits</a>
    </div>

    <div class="random-box">
        Lien de rotation : <b><?php echo $randomLink; ?></b> 
        <button onclick="navigator.clipboard.writeText('<?php echo $randomLink; ?>');alert('Copié !')">Copier</button>
    </div>

    <?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>

    <h1>Gesturl Lite</h1>

    <form action="index.php" method="post">
        <label for="url">URL :</label>
        <input type="text" name="url" id="url" required style="width: 300px;">
        <input type="submit" name="submit" value="Ajouter">
    </form>

    <form action="index.php" method="post">
        <label for="urls">Collez vos liens ici (un par ligne ou un lien /feed/) :</label><br>
        <textarea id="urls" name="urls"></textarea>
        <input type="submit" name="import" value="Importer">
    </form>

    <form action="index.php" method="post">
        <input type="submit" name="clear_all" value="Vider toutes les URLs" onclick="return confirm('Êtes-vous sûr ?');">
    </form>

    <?php if (!empty($urls)): ?>
    <table>
        <tr>
            <th style="width: 100px;">ID</th>
            <th>URL</th>
            <th style="width: 60px;">Hits</th>
            <th style="width: 150px;">Indexation Google</th>
            <th style="width: 150px;">Actions</th>
        </tr>
        <?php foreach ($urls as $id => $row): ?>
        <?php 
            $googleLink = "https://www.google.fr/search?q=site:" . urlencode($row['url']);
        ?>
        <tr>
            <td style="font-size:0.85em; color:#666; font-family:monospace;"><?php echo $id; ?></td>
            
            <td>
                <a href="<?php echo htmlspecialchars($row['url']); ?>" target="_blank">
                    <?php echo htmlspecialchars(substr($row['url'], 0, 60)) . (strlen($row['url'])>60 ? '...' : ''); ?>
                </a>
            </td>
            
            <td style="font-size: 1.2em; text-align:center;"><b><?php echo $row['hit']; ?></b></td>
            
            <td>
                <a href="<?php echo $googleLink; ?>" target="_blank" style="text-decoration:none; color:#4285F4; font-weight:bold;">
                    🔍 Voir indexation
                </a>
            </td>
            
            <td>
                <a href="index.php?reset=<?php echo $id; ?>">Reset</a> | 
                <a href="index.php?delete=<?php echo $id; ?>" style="color:red;">Supprimer</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Affichage de la Vélocité -->
    <div class="velocity-box" style="margin: 20px auto; padding: 15px; background: #f5f5f5; border: 2px solid <?php echo $velocityColor; ?>; border-radius: 8px; display: inline-block; min-width: 300px;">
        <span style="font-size: 1.1em;">Vélocité de la campagne :</span>
        <strong style="color: <?php echo $velocityColor; ?>; font-size: 1.3em; margin-left: 10px;">
            <?php echo $velocityLevel; ?>
        </strong>
        <div style="font-size: 0.85em; color: #666; margin-top: 5px;">
            (<?php echo $velocityCount; ?> hit<?php echo $velocityCount > 1 ? 's' : ''; ?> dans les 20 dernières secondes)
        </div>
    </div>
    <?php endif; ?>

    <div class="footer">
        Gesturl Lite Version 1.0 - décembre 2025
    </div>

</body>
</html>