<?php

namespace TCG\Voyager\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use TCG\Voyager\Providers\VoyagerDummyServiceProvider;
use TCG\Voyager\VoyagerServiceProvider;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voyager:install
                            {--force : Force the operation to run when in production}
                            {--with-dummy : Install with dummy data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Voyager Admin package';

    /**
     * Create a new command instance.
     */
    public function __construct(protected Composer $composer)
    {
        parent::__construct();

        $this->composer->setWorkingPath(base_path());
    }

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $filesystem): int
    {
        $this->info('Publishing the Voyager assets, database, and config files');

        // Publish only relevant resources on install
        $tags = ['seeders'];

        $this->call('vendor:publish', ['--provider' => VoyagerServiceProvider::class, '--tag' => $tags]);

        $this->info('Migrating the database tables into your application');
        $this->call('migrate', ['--force' => $this->option('force')]);

        $this->info('Attempting to set Voyager User model as parent to App\User');
        if (file_exists(app_path('User.php')) || file_exists(app_path('Models/User.php'))) {
            $userPath = file_exists(app_path('User.php')) ? app_path('User.php') : app_path('Models/User.php');

            $str = file_get_contents($userPath);

            if ($str !== false) {
                $str = str_replace('extends Authenticatable', "extends \TCG\Voyager\Models\User", $str);

                file_put_contents($userPath, $str);
            }
        } else {
            $this->warn('Unable to locate "User.php" in app or app/Models.  Did you move this file?');
            $this->warn('You will need to update this manually.  Change "extends Authenticatable" to "extends \TCG\Voyager\Models\User" in your User model');
        }

        $this->info('Adding Voyager routes to routes/web.php');
        $routes_contents = $filesystem->get(base_path('routes/web.php'));
        if (!str_contains($routes_contents, 'Voyager::routes()')) {
            $filesystem->append(
                base_path('routes/web.php'),
                PHP_EOL.PHP_EOL."Route::group(['prefix' => 'admin'], function () {".PHP_EOL."    Voyager::routes();".PHP_EOL."});".PHP_EOL
            );
        }

        if ($this->option('with-dummy')) {
            $this->info('Publishing dummy content');
            $tags = ['dummy_seeders', 'dummy_content', 'dummy_config', 'dummy_migrations'];
            $this->call('vendor:publish', ['--provider' => VoyagerDummyServiceProvider::class, '--tag' => $tags]);
        } else {
            $this->call('vendor:publish', ['--provider' => VoyagerServiceProvider::class, '--tag' => ['config', 'voyager_avatar']]);
        }

        $this->info('Dumping the autoloaded files and reloading all new files');
        $this->composer->dumpAutoloads();

        $this->info('Seeding data into the database');
        $this->call('db:seed', ['--class' => 'VoyagerDatabaseSeeder', '--force' => $this->option('force')]);

        if ($this->option('with-dummy')) {
            $this->info('Migrating dummy tables');
            $this->call('migrate');

            $this->info('Seeding dummy data');
            $this->call('db:seed', ['--class' => 'VoyagerDummyDatabaseSeeder', '--force' => $this->option('force')]);
        }

        $this->info('Adding the storage symlink to your public folder');
        $this->call('storage:link');

        $this->info('Successfully installed Voyager! Enjoy');

        return self::SUCCESS;
    }
}
