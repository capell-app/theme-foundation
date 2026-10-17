<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Symfony\Component\Process\Process;

function assertThemeCountdownOutcomes(string $theme): void
{
    View::addNamespace('capell-theme-' . $theme, dirname(__DIR__, 3) . '/theme-' . $theme . '/resources/views');
    $view = dirname(__DIR__, 3) . '/theme-' . $theme . '/resources/views/partials/countdown-script.blade.php';
    $process = new Process(['node', dirname(__DIR__, 4) . '/tests/Fixtures/BrowserScripts/countdown.cjs']);
    $process->setInput(View::file($view)->render())->setTimeout(10)->mustRun();
    $outcomes = capell_json_array($process->getOutput());

    expect(data_get($outcomes, 'observed.state'))->toBe([
        'Server clock', '00:00:05', '00:00:04', '00:00:04',
        '00:00:03', '00:00:03', '00:00:02', '00:00:02',
    ])->and(data_get($outcomes, 'observed.pending'))->toBe(0)
        ->and(data_get($outcomes, 'fallback.state'))->toBe([
            '00:00:05', '00:00:05', '00:00:04', '00:00:04', '00:00:03', '00:00:03',
        ])->and(data_get($outcomes, 'fallback.pending'))->toBe(0);
}
