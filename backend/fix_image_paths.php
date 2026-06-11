<?php
// Quick script to fix existing corrupted image paths in classes_types
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$setting = App\Models\SiteSetting::where('key', 'classes_types')->first();

if ($setting) {
    $data = json_decode($setting->value, true);
    echo "Before:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    
    $changed = false;
    foreach ($data as &$item) {
        if (!empty($item['image']) && str_contains($item['image'], 'uploads/settings/')) {
            $parts = explode('uploads/settings/', $item['image']);
            $newPath = 'uploads/settings/' . end($parts);
            if ($newPath !== $item['image']) {
                echo "Fixing: {$item['image']} -> {$newPath}\n";
                $item['image'] = $newPath;
                $changed = true;
            }
        }
    }
    unset($item);
    
    if ($changed) {
        $setting->value = json_encode($data);
        $setting->save();
        echo "\nAfter:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        echo "\nDone! Image paths fixed.\n";
    } else {
        echo "All image paths are already relative. No changes needed.\n";
    }
} else {
    echo "classes_types setting not found.\n";
}
