<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Console\Commands;

use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Illuminate\Console\Command;

class GenerateTailwindAssetsCommand extends Command
{
    protected $signature = 'capell:frontend-tailwind-assets {--report : Print the aggregated assets report instead of writing files} {--output-path= : Project-local absolute path or directory for the generated frontend CSS entrypoint}';

    protected $description = 'Generate the Tailwind CSS directive file for Capell frontend.';

    public function handle(TailwindAssetsGenerator $generator): int
    {
        if ($this->option('report')) {
            $registry = $generator->collect();

            $report = $registry->toReport();

            $this->line('Tailwind assets report:');
            $encodedReport = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $this->line($encodedReport === false ? '{}' : $encodedReport);

            return self::SUCCESS;
        }

        $overridePath = $this->option('output-path');
        $baseTargetPath = is_string($overridePath) && $overridePath !== '' ? $overridePath : null;

        $generatedPaths = $generator->generate($baseTargetPath);

        foreach ($generatedPaths as $generatedPath) {
            $this->info(sprintf('Generated Tailwind assets at %s', $generatedPath));
        }

        return self::SUCCESS;
    }
}
