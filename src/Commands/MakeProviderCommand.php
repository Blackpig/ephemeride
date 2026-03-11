<?php

namespace BlackpigCreatif\Ephemeride\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeProviderCommand extends Command
{
    protected $signature = 'ephemeride:make-provider {name? : The name of the provider class}';

    protected $description = 'Create a new Éphéméride event provider';

    public function handle(): int
    {
        $name = $this->argument('name') ?: $this->ask('What should the provider be called? (e.g., Festivals, Events, Retreats)');

        if (empty($name)) {
            $this->error('Provider name is required.');

            return self::FAILURE;
        }

        $className = $this->parseClassName($name);

        $directory = app_path('BlackpigCreatif/Ephemeride');
        $filePath  = $directory.'/'.$className.'.php';

        if (File::exists($filePath)) {
            $this->error("Provider [{$className}] already exists.");

            return self::FAILURE;
        }

        File::ensureDirectoryExists($directory);

        File::put($filePath, $this->buildStub($className));

        $this->info("Provider [{$className}] created successfully.");
        $this->line("Location: {$filePath}");
        $this->newLine();
        $this->comment('Next steps:');
        $this->comment('1. Implement getEphemerides() to return your events');
        $this->comment('2. Mount the component:');
        $this->line("   <livewire:ephemeride-calendar :provider=\"App\\BlackpigCreatif\\Ephemeride\\{$className}::class\" />");

        return self::SUCCESS;
    }

    protected function parseClassName(string $name): string
    {
        // Strip any trailing "provider" (case-insensitive) before studly-casing
        $base = preg_replace('/provider$/i', '', $name);

        return Str::studly($base).'Provider';
    }

    protected function buildStub(string $className): string
    {
        return <<<PHP
<?php

namespace App\BlackpigCreatif\Ephemeride;

use BlackpigCreatif\Ephemeride\Contracts\ProvidesEphemerides;
use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class {$className} implements ProvidesEphemerides
{
    public function getEphemerides(Carbon \$from, Carbon \$to): Collection
    {
        // \$from and \$to cover the full visible grid window, including leading
        // and trailing days from adjacent months. Query against this range directly.

        return collect([
            // EphemerisEvent::make(
            //     id:       '1',
            //     title:    'Example Event',
            //     startsAt: Carbon::parse('...'),
            //     endsAt:   Carbon::parse('...'),
            // ),
        ]);
    }
}

PHP;
    }
}
