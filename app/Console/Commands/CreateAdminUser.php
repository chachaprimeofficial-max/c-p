<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';
    protected $description = 'Interactively create the first administrator account or promote an existing account.';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->ask('Administrator email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');
            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();
        if ($existing && $existing->is_admin) {
            $this->error('An administrator with this email already exists. Use the normal account recovery process.');
            return self::FAILURE;
        }
        if ($existing && !$this->confirm('This email already belongs to a customer account. Promote it to administrator?', false)) {
            $this->info('No changes made.');
            return self::SUCCESS;
        }

        $password = (string) $this->secret('New administrator password (minimum 14 characters)');
        if (strlen($password) < 14) {
            $this->error('Use a password with at least 14 characters.');
            return self::FAILURE;
        }
        $confirmation = (string) $this->secret('Confirm administrator password');
        if (!hash_equals($password, $confirmation)) {
            $this->error('The passwords did not match. No changes made.');
            return self::FAILURE;
        }

        $user = $existing ?: new User();
        $user->name = $existing?->name ?: 'ChachaPrime Administrator';
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->is_admin = true;
        $user->email_verified_at = now();
        $user->save();

        $this->info('Administrator account saved. Sign in at /admin/login. The password was not displayed or logged.');
        return self::SUCCESS;
    }
}
