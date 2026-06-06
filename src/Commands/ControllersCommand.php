<?php

namespace TCG\Voyager\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ControllersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voyager:controllers
                            {--f|force : Overwrite existing controller files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all the controllers from Voyager.';

    /**
     * Filename of stub-file.
     *
     * @var string
     */
    protected $stub = 'controller.stub';

    /**
     * Create a new command instance.
     */
    public function __construct(protected Filesystem $filesystem)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $stub = $this->getStub();
        $files = $this->filesystem->files(base_path('vendor/tcg/voyager/src/Http/Controllers'));
        $namespace = config('voyager.controllers.namespace', 'TCG\\Voyager\\Http\\Controllers');

        $appNamespace = app()->getNamespace();

        if (!Str::startsWith($namespace, $appNamespace)) {
            $this->error('The controllers namespace must start with your application namespace: '.$appNamespace);

            return self::FAILURE;
        }

        $location = str_replace('\\', DIRECTORY_SEPARATOR, substr($namespace, strlen($appNamespace)));

        if (!$this->filesystem->isDirectory(app_path($location))) {
            $this->filesystem->makeDirectory(app_path($location));
        }

        foreach ($files as $file) {
            $parts = explode(DIRECTORY_SEPARATOR, $file);
            $filename = end($parts);

            if ($filename == 'Controller.php') {
                continue;
            }

            $path = app_path($location.DIRECTORY_SEPARATOR.$filename);

            if (!$this->filesystem->exists($path) || $this->option('force')) {
                $class = substr($filename, 0, strpos($filename, '.'));
                $content = $this->generateContent($stub, $class);
                $this->filesystem->put($path, $content);
            }
        }

        $this->info('Published Voyager controllers!');

        return self::SUCCESS;
    }

    /**
     * Get stub content.
     */
    public function getStub(): string
    {
        return $this->filesystem->get(base_path('/vendor/tcg/voyager/stubs/'.$this->stub));
    }

    /**
     * Generate real content from stub.
     */
    protected function generateContent(string $stub, string $class): string
    {
        $namespace = config('voyager.controllers.namespace', 'TCG\\Voyager\\Http\\Controllers');

        return str_replace(
            ['DummyNamespace', 'FullBaseDummyClass', 'BaseDummyClass', 'DummyClass'],
            [$namespace, 'TCG\\Voyager\\Http\\Controllers\\'.$class, 'Base'.$class, $class],
            $stub
        );
    }
}
