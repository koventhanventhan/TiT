<?php

namespace Tests\Feature;

use App\Casts\SafeEncryptedString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EncryptedColumnsGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Portable guard test (runs on SQLite and MariaDB):
     * For every model attribute using SafeEncryptedString or an encrypted cast,
     * assert the column type in the schema is text/longtext/json (not varchar).
     */
    public function test_all_encrypted_and_safe_encrypted_attributes_use_text_or_json_in_schema(): void
    {
        $modelFiles = glob(app_path('Models/*.php'));
        $this->assertNotEmpty($modelFiles, 'No models found in app/Models.');

        $checkedAttributes = [];

        foreach ($modelFiles as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || !$reflection->isSubclassOf(Model::class)) {
                continue;
            }

            /** @var Model $instance */
            $instance = new $class();
            $table = $instance->getTable();

            if (!Schema::hasTable($table)) {
                continue;
            }

            $casts = $instance->getCasts();

            foreach ($casts as $attribute => $castType) {
                if (!$this->isEncryptedCast($castType)) {
                    continue;
                }

                $this->assertTrue(
                    Schema::hasColumn($table, $attribute),
                    "Table '{$table}' is missing column '{$attribute}' which has cast '{$castType}'."
                );

                $columnType = strtolower(Schema::getColumnType($table, $attribute));

                // MariaDB/MySQL reports 'text', 'longtext', 'json', etc.
                // SQLite reports 'text', 'varchar', 'string', etc.
                $this->assertNotContains(
                    $columnType,
                    ['varchar', 'string', 'char'],
                    "Column '{$table}.{$attribute}' is cast as '{$castType}' but declared as '{$columnType}'. Encrypted strings overflow VARCHAR and must use TEXT/LONGTEXT/JSON."
                );

                $this->assertContains(
                    $columnType,
                    ['text', 'mediumtext', 'longtext', 'json'],
                    "Column '{$table}.{$attribute}' has cast '{$castType}' but unexpected type '{$columnType}'."
                );

                $checkedAttributes[] = "{$table}.{$attribute} ({$castType} -> {$columnType})";
            }
        }

        // Verify that known encrypted attributes were actually checked
        $this->assertNotEmpty($checkedAttributes, 'Guard test did not find any encrypted attributes to check.');

        $checkedKeys = array_map(function ($entry) {
            return explode(' ', $entry)[0];
        }, $checkedAttributes);

        $this->assertContains(
            'zoom_accounts.client_secret',
            $checkedKeys,
            "Guard test must verify zoom_accounts.client_secret."
        );
        $this->assertContains(
            'institute_integrations.credentials',
            $checkedKeys,
            "Guard test must verify institute_integrations.credentials."
        );
    }

    /**
     * Determine if a cast type is an encrypted cast.
     */
    private function isEncryptedCast(string $castType): bool
    {
        if ($castType === SafeEncryptedString::class || $castType === '\\' . SafeEncryptedString::class) {
            return true;
        }

        if (str_starts_with($castType, 'encrypted') || str_starts_with($castType, 'encrypted:')) {
            return true;
        }

        if (str_starts_with($castType, 'AsEncrypted')) {
            return true;
        }

        return false;
    }
}

