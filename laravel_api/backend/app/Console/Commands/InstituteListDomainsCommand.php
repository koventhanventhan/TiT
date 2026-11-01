<?php

namespace App\Console\Commands;

use App\Models\Institute;
use Illuminate\Console\Command;

class InstituteListDomainsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'institutes:list-domains';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered institutes and their assigned domains';

    public function handle(): int
    {
        $institutes = Institute::with('domains')->orderBy('id')->get();

        if ($institutes->isEmpty()) {
            $this->warn('No institutes registered in the system.');
            return self::SUCCESS;
        }

        $headers = ['ID', 'Institute Name', 'Slug', 'Primary Domain', 'Extra Domains', 'Status'];
        $rows = [];

        foreach ($institutes as $institute) {
            $primary = $institute->domains->firstWhere('is_primary', true);
            $extras = $institute->domains->where('is_primary', false)->pluck('domain')->implode(', ');

            $rows[] = [
                $institute->id,
                $institute->name,
                $institute->slug,
                $primary ? $primary->domain : '<none>',
                $extras ?: '<none>',
                $institute->status,
            ];
        }

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}
