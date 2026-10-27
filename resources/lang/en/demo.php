<?php

declare(strict_types=1);

return [
    'foundation_help' => 'Use --site=Disposable to choose a reviewed demo target, --language=en for each requested language and --base-url=https://demo.test for its domain. Existing site configuration is preserved unless --force is supplied. With an explicit target, --force selects Foundation, enables the site and replaces its requested site translations and domains. Scoped setup reseeds demo pages, widgets and demo layouts, including on repeats; use a disposable site or private theme Preview for existing content.',
    'target_required' => 'Demo setup needs an explicit target when sites already exist: --sites (Foundation uses --site). Use private theme Preview for existing content, or name a disposable demo site. Existing site configuration is preserved unless you deliberately combine an explicit target with --force.',
];
