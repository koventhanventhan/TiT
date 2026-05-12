<?php
header('Content-Type: text/plain');
echo "Current directory: " . getcwd() . "\n";
echo "Symlink check for ../../../api: " . (is_link('../../../api') ? 'YES' : 'NO') . "\n";
if (is_link('../../../api')) {
    echo "Points to: " . readlink('../../../api') . "\n";
}

echo "\nListing files in current directory (backend/public):\n";
print_r(scandir('.'));

echo "\nListing files in vendor folder:\n";
if (is_dir('vendor')) {
    print_r(scandir('vendor'));
} else {
    echo "vendor folder NOT FOUND";
}
