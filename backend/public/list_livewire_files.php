<?php
header('Content-Type: text/plain');
$dir = '../../../api/vendor/livewire';

if (is_dir($dir)) {
    echo "--- LISTING $dir ---\n";
    print_r(scandir($dir));
} else {
    echo "Folder NOT FOUND: $dir";
}
