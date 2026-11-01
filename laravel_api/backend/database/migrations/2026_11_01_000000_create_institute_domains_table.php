<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('institute_domains')) {
            Schema::create('institute_domains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institute_id')->constrained('institutes')->onDelete('cascade');
                $table->string('domain')->unique();
                $table->boolean('is_primary')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->string('verification_token')->nullable();
                $table->timestamps();

                $table->index(['institute_id', 'is_primary']);
            });
        }

        // Backfill Institute 1 primary domain from config('app.url') and titjaffna.lk
        if (Schema::hasTable('institutes')) {
            $institute1 = DB::table('institutes')->where('id', 1)->first();
            if ($institute1) {
                $appHost = strtolower(parse_url(config('app.url', 'https://titjaffna.lk'), PHP_URL_HOST) ?: 'titjaffna.lk');
                $appHost = preg_replace('/:\d+$/', '', $appHost);

                $domainsToSeed = [];
                // Add appHost as primary if not localhost/127.0.0.1
                $primaryDomain = $appHost;
                if (in_array($appHost, ['localhost', '127.0.0.1', '']) || str_ends_with($appHost, '.test')) {
                    $primaryDomain = 'titjaffna.lk';
                }

                $domainsToSeed[] = [
                    'domain' => $primaryDomain,
                    'is_primary' => true,
                ];

                // If primary domain is not www, also add its www alias
                if (!str_starts_with($primaryDomain, 'www.')) {
                    $domainsToSeed[] = [
                        'domain' => 'www.' . $primaryDomain,
                        'is_primary' => false,
                    ];
                }

                // If appHost was different (e.g. titjaffna.lk when primary was something else), ensure titjaffna.lk exists
                if ($primaryDomain !== 'titjaffna.lk') {
                    $domainsToSeed[] = [
                        'domain' => 'titjaffna.lk',
                        'is_primary' => false,
                    ];
                    $domainsToSeed[] = [
                        'domain' => 'www.titjaffna.lk',
                        'is_primary' => false,
                    ];
                }

                $now = now();
                foreach ($domainsToSeed as $d) {
                    $exists = DB::table('institute_domains')->where('domain', $d['domain'])->exists();
                    if (!$exists) {
                        DB::table('institute_domains')->insert([
                            'institute_id' => 1,
                            'domain' => $d['domain'],
                            'is_primary' => $d['is_primary'],
                            'verified_at' => $now,
                            'verification_token' => Str::random(32),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institute_domains');
    }
};

