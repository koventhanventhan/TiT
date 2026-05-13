<?php
// AUTO ASSET SYNC
$assetsDir = '../../../assets';
$indexPath = '../../../index.html';

$files = scandir($assetsDir);
$jsFile = '';
$cssFile = '';

foreach ($files as $file) {
    if (strpos($file, 'index-') === 0 && str_ends_with($file, '.js')) {
        $jsFile = $file;
    }
    if (strpos($file, 'index-') === 0 && str_ends_with($file, '.css')) {
        $cssFile = $file;
    }
}

if ($jsFile && $cssFile) {
    $content = file_get_contents($indexPath);
    
    // Replace JS
    $content = preg_replace('/src="\/assets\/index-.*?\.js"/', 'src="/assets/' . $jsFile . '"', $content);
    // Replace CSS
    $content = preg_replace('/href="\/assets\/index-.*?\.css"/', 'href="/assets/' . $cssFile . '"', $content);
    
    if (file_put_contents($indexPath, $content)) {
        echo "SUCCESS: Auto-synced index.html with JS: $jsFile and CSS: $cssFile";
    } else {
        echo "ERROR: Could not write to index.html";
    }
} else {
    echo "ERROR: Could not find index-*.js or index-*.css in $assetsDir";
}
