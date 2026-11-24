<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateSuperAdmin extends Command
{
    protected $signature = 'ministry:create-super-admin {--name= : Full name} {--email= : Email address}';

    protected $description = 'Create a verified super-admin account';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Name', required: true);
        $email = $this->option('email') ?: text('Email', required: true);
        $password = password('Password', required: true);
        $confirmation = password('Confirm password', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        Role::findOrCreate('super-admin', 'web');

        $user = new User(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
        $user->email_verified_at = now();
        $user->save();
        $user->assignRole('super-admin');

        $this->info("Super-admin {$email} created.");

        return self::SUCCESS;
    }
}
