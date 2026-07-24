<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions\DesignSpec;

use Capell\FoundationTheme\Data\DesignSpec\DesignSpecColorModeData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecData;
use LogicException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static string run(DesignSpecData $specification) */
final class RenderFoundationDesignTokensAction
{
    use AsFake;
    use AsObject;

    public function handle(DesignSpecData $specification): string
    {
        return ":root{\n" . $this->colorTokens($specification->palette->light)
            . "  --capell-container:{$specification->layout->container};\n"
            . "  --capell-density:{$specification->layout->density};\n"
            . "  --capell-spacing:{$specification->layout->spacing};\n"
            . "  --capell-radius:{$specification->layout->radius};\n"
            . "}\n.dark{\n" . $this->colorTokens($specification->palette->dark) . "}\n";
    }

    private function colorTokens(DesignSpecColorModeData $colors): string
    {
        $tokens = get_object_vars($colors);
        ksort($tokens, SORT_STRING);

        $output = '';
        foreach ($tokens as $name => $value) {
            if (! is_string($name) || ! is_string($value)) {
                throw new LogicException('DesignSpec color tokens must be strings.');
            }

            $output .= "  --capell-color-{$name}:{$value};\n";
        }

        return $output;
    }
}
