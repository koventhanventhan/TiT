<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class MakeSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:super-admin {email? : Email address of the super administrator} {--name= : Name of the super administrator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely create or reset a platform super-administrator account (institute_id = NULL)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = trim((string) ($this->argument('email') ?: $this->ask('Enter super-admin email address:')));

        $emailValidator = Validator::make(['email' => $email], [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($emailValidator->fails()) {
            $this->error('Invalid email address provided: ' . $emailValidator->errors()->first('email'));
            return 1;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Enter super-admin full name:', 'Super Admin')));
        if ($name === '') {
            $name = 'Super Admin';
        }

        $password = $this->secret('Enter super-admin password (hidden input, min 12 characters):');
        if (strlen((string) $password) < 12) {
            $this->error('Password must be at least 12 characters long for super-admin accounts.');
            return 1;
        }

        $confirmPassword = $this->secret('Confirm password:');
        if ($password !== $confirmPassword) {
            $this->error('Passwords do not match. Aborting.');
            return 1;
        }

        $user = User::withoutGlobalScopes()->where('email', $email)->first();
        $isNew = ($user === null);

        if ($isNew) {
            $user = new User();
            $user->email = $email;
        }

        $user->name = $name;
        $user->role = 'super_admin';
        $user->institute_id = null; // Super admins are platform-wide
        $user->deactivated_at = null;
        $user->email_verified_at = $user->email_verified_at ?: now();

        // IMPORTANT: The User model casts 'password' to 'hashed'.
        // DO NOT use Hash::make() here as it causes a double-hashing defect.
        $user->password = $password;

        $user->save();

        ActivityLogService::log(
            'super_admin_provisioned',
            $isNew ? "Created new super-admin account for {$email}" : "Reset password for super-admin account {$email}",
            ['email' => $email, 'institute_id' => null],
            null
        );

        $action = $isNew ? 'created' : 'updated';
        $this->info("Super administrator {$email} successfully {$action}.");
        $this->line("Institute context: NULL (Platform-wide)");
        $this->line("Role: super_admin");

        return 0;
    }
}
