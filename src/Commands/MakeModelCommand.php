<?php

namespace TCG\Voyager\Commands;

use Illuminate\Foundation\Console\ModelMakeCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeModelCommand extends ModelMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'voyager:make:model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Voyager model class';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/model.stub';
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     */
    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->addSoftDelete($stub)->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Add SoftDelete to the given stub.
     *
     * @param string $stub
     *
     * @return $this
     */
    protected function addSoftDelete(&$stub): static
    {
        $traitIncl = $trait = '';

        if ($this->option('softdelete')) {
            $traitIncl = 'use Illuminate\Database\Eloquent\SoftDeletes;';
            $trait = 'use SoftDeletes;';
        }

        $stub = str_replace('//DummySDTraitInclude', $traitIncl, $stub);
        $stub = str_replace('//DummySDTrait', $trait, $stub);

        return $this;
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        $options = [
            ['softdelete', 'd', InputOption::VALUE_NONE, 'Add soft-delete field to Model'],
        ];

        return array_merge($options, parent::getOptions());
    }
}
