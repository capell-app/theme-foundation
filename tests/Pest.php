<?php

declare(strict_types=1);

use Capell\Tests\Packages\PackagesTestCase;

pest()->extend(PackagesTestCase::class)->group('theme-foundation')->in('.');

if (! function_exists('foundationThemeJsonObject')) {
    /** @return array<string, mixed> */
    function foundationThemeJsonObject(mixed $value): array
    {
        if (! is_array($value) || array_is_list($value)) {
            throw new RuntimeException('Expected a JSON object.');
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (! is_string($key)) {
                throw new RuntimeException('Expected string JSON object keys.');
            }

            $object[$key] = $item;
        }

        return $object;
    }
}

if (! function_exists('foundationThemeJsonList')) {
    /** @return list<mixed> */
    function foundationThemeJsonList(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new RuntimeException('Expected a JSON list.');
        }

        $list = [];
        foreach ($value as $item) {
            $list[] = $item;
        }

        return $list;
    }
}

if (! function_exists('foundationThemeJsonObjectDocument')) {
    /** @return array<string, mixed> */
    function foundationThemeJsonObjectDocument(string $json): array
    {
        return foundationThemeJsonObject(json_decode($json, true, 64, JSON_THROW_ON_ERROR));
    }
}
