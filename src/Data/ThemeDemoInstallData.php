<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class ThemeDemoInstallData extends Data
{
    /** @var array<int, string> */
    public readonly array $siteNames;

    /** @var array<int, string> */
    public readonly array $languageCodes;

    public readonly string $baseUrl;

    /**
     * @param  array<int, string>  $siteNames
     * @param  array<int, string>  $languageCodes
     */
    public function __construct(
        array $siteNames,
        array $languageCodes,
        string $baseUrl,
        public readonly bool $force = false,
    ) {
        $this->siteNames = $this->normalizeStrings($siteNames);
        $this->languageCodes = array_map(
            strtolower(...),
            $this->normalizeStrings($languageCodes),
        );
        $this->baseUrl = rtrim(trim($baseUrl), '/');
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    private function normalizeStrings(array $values): array
    {
        return array_values(array_filter(
            array_map(
                trim(...),
                $values,
            ),
            static fn (string $value): bool => $value !== '',
        ));
    }
}
