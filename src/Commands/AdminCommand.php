<?php

namespace TCG\Voyager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class AdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voyager:admin
                            {email? : The email of the user}
                            {--create : Create an admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make sure there is a user with the admin role that has all of the necessary permissions.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get or create user
        $user = $this->getUser(
            $this->option('create')
        );

        // the user not returned
        if (!$user) {
            return self::FAILURE;
        }

        // Get or create role
        $role = $this->getAdministratorRole();

        // Get all permissions
        $permissions = Voyager::model('Permission')->all();

        // Assign all permissions to the admin role
        $role->permissions()->sync(
            $permissions->pluck('id')->all()
        );

        // Ensure that the user is admin
        $user->role_id = $role->id;
        $user->save();

        $this->info('The user now has full access to your site.');

        return self::SUCCESS;
    }

    /**
     * Get the administrator role, create it if it does not exists.
     */
    protected function getAdministratorRole(): mixed
    {
        $role = Voyager::model('Role')->firstOrNew([
            'name' => 'admin',
        ]);

        if (!$role->exists) {
            $role->fill([
                'display_name' => 'Administrator',
            ])->save();
        }

        return $role;
    }

    /**
     * Get or create user.
     */
    protected function getUser(bool $create = false): mixed
    {
        $email = $this->argument('email');

        $model = Auth::guard(app('VoyagerGuard'))->getProvider()->getModel();
        $model = Str::start($model, '\\');

        // If we need to create a new user go ahead and create it
        if ($create) {
            $name = $this->ask('Enter the admin name');
            $password = $this->secret('Enter admin password');
            $confirmPassword = $this->secret('Confirm Password');

            // Ask for email if there wasnt set one
            if (!$email) {
                $email = $this->ask('Enter the admin email');
            }

            // check if user with given email exists
            if ($model::query()->where('email', $email)->exists()) {
                $this->info("Can't create user. User with the email ".$email.' exists already.');

                return null;
            }

            // Passwords don't match
            if ($password != $confirmPassword) {
                $this->info("Passwords don't match");

                return null;
            }

            $this->info('Creating admin account');

            return $model::forceCreate([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($password),
            ]);
        }

        return $model::query()->where('email', $email)->firstOrFail();
    }
}
