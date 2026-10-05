<?php
// ROLLBACK SCRIPT - Remove the bad .htaccess
$htaccessPath = '../../../.htaccess'; 

if (file_exists($htaccessPath)) {
    if (unlink($htaccessPath)) {
        echo "SUCCESS: .htaccess removed. Site should be back now.";
    } else {
        echo "ERROR: Could not delete .htaccess. Please delete it manually via cPanel File Manager.";
    }
} else {
    echo "NO .htaccess found in root.";
}
