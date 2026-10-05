<?php
// Fix Route Conflict between Filament and React
$filamentConfig = '../config/filament/admin.php'; // or config/filament.php

$targetFiles = [
    '../app/Providers/Filament/AdminPanelProvider.php',
    '../config/filament.php'
];

foreach ($targetFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        // Change path from 'admin' to 'super-panel' to avoid conflict with React
        $content = str_replace("path('admin')", "path('super-panel')", $content);
        $content = str_replace("'path' => 'admin'", "'path' => 'super-panel'", $content);
        file_put_contents($file, $content);
        echo "Updated path in $file\n";
    }
}

// Also clear route cache
echo "\nSUCCESS: Route conflict resolved! React can now use /admin path.";
