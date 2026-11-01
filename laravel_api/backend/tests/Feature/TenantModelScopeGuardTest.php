<?php

namespace Tests\Feature;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

class TenantModelScopeGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Static architectural test:
     * Reflects every Eloquent model in app/Models. If the underlying table
     * has an `institute_id` column, it MUST either:
     * 1. Use the `App\Traits\BelongsToInstitute` trait, OR
     * 2. Be explicitly documented in `config('tenant.unscoped_models')`.
     */
    public function test_all_models_with_institute_id_column_use_belongs_to_institute_trait(): void
    {
        $modelFiles = glob(app_path('Models/*.php'));
        $this->assertNotEmpty($modelFiles, 'app/Models must contain model files.');

        $unscopedConfig = config('tenant.unscoped_models', []);
        $unscopedList = array_map(function ($key, $val) {
            return is_string($key) && !is_numeric($key) ? $key : $val;
        }, array_keys($unscopedConfig), array_values($unscopedConfig));

        $violations = [];
        $auditedCount = 0;

        foreach ($modelFiles as $file) {
            $className = 'App\\Models\\' . basename($file, '.php');
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if (!$reflection->isSubclassOf(Model::class) || $reflection->isAbstract()) {
                continue;
            }

            /** @var Model $instance */
            $instance = new $className;
            $table = $instance->getTable();

            if (!Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'institute_id')) {
                $auditedCount++;
                $usesTrait = in_array(BelongsToInstitute::class, class_uses_recursive($className), true);
                $isAllowedUnscoped = in_array($className, $unscopedList, true);

                if (!$usesTrait && !$isAllowedUnscoped) {
                    $violations[] = "{$className} (table: {$table})";
                }
            }
        }

        $this->assertGreaterThan(5, $auditedCount, 'Guard test must audit multiple tenant-scoped models.');

        $this->assertEmpty(
            $violations,
            "The following models have an 'institute_id' column but do NOT use BelongsToInstitute trait " .
            "and are not documented in config('tenant.unscoped_models'):\n" .
            implode("\n", $violations)
        );
    }
}

