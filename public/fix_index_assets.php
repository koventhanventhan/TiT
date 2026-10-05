<?php
// Script to fix index.html asset links
$indexPath = '../../../index.html';
$content = file_get_contents($indexPath);

// Replace JS
$content = preg_replace('/index-[a-zA-Z0-9_-]+\.js/', 'index-C1DpLfW1.js', $content);
// Replace CSS
$content = preg_replace('/index-[a-zA-Z0-9_-]+\.css/', 'index-BMCsdN-L.css', $content);

if (file_put_contents($indexPath, $content)) {
    echo "SUCCESS: index.html updated with correct asset links!";
} else {
    echo "ERROR: Could not update index.html";
}
