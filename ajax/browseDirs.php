<?php
$requestedDir = $_GET['dir'] ?? '';
$os = strtoupper(substr(PHP_OS, 0, 3));

// Laufwerke ermitteln (nur Windows)
$drives = [];
if ($os === 'WIN') {
    // Nutzt den Befehl 'wmic' oder 'fsutil' ist oft gesperrt, daher probieren wir Buchstaben
    foreach (range('A', 'Z') as $letter) {
        if (is_dir($letter . ':')) {
            $drives[] = $letter . ':/';
        }
    }
}

// Wenn kein Pfad angegeben oder Pfad ungültig, Root nehmen
if (empty($requestedDir) || !is_dir($requestedDir)) {
    $root = ($os === 'WIN') ? 'C:/' : '/';
} else {
    $root = realpath($requestedDir);
}

echo '<div class="row mb-3">';
if (!empty($drives)) {
    echo '<div class="col-12"><label class="form-label">Laufwerk wechseln:</label><br/>';
    echo '<div class="btn-group w-100" role="group">';
    foreach ($drives as $drive) {
        $active = (str_starts_with(strtoupper($root), strtoupper($drive))) ? 'btn-primary' : 'btn-outline-secondary';
        echo '<a href="#" class="btn btn-sm '.$active.' browse-dir" data-path="'.htmlspecialchars($drive).'">'.$drive.'</a>';
    }
    echo '</div></div>';
}
echo '</div>';

$files = @scandir($root);
echo '<div class="list-group">';

// "Nach oben" Link
$parent = dirname($root);
if ($parent != $root && is_dir($parent)) {
    echo '<a href="#" class="list-group-item list-group-item-action list-group-item-info browse-dir" data-path="'.htmlspecialchars($parent).'">
                <i class="fa fa-level-up-alt me-2"></i> .. (Übergeordneter Ordner)
              </a>';
}

if ($files) {
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $fullPath = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;

        if (@is_dir($fullPath)) {
            echo '<a href="#" class="list-group-item list-group-item-action browse-dir" data-path="'.htmlspecialchars($fullPath).'">
                        <i class="fa fa-folder text-warning me-2"></i> ' . htmlspecialchars($file) . '
                      </a>';
        }
    }
} else {
    echo '<div class="list-group-item text-muted">Keine Unterordner gefunden oder Zugriff verweigert.</div>';
}
echo '</div>';
echo '<input type="hidden" id="current-browsed-path" value="'.htmlspecialchars($root).'">';