<?php

declare(strict_types=1);

function domQueryCount(string $html, string $expression): int
{
    $document = new DOMDocument;
    @$document->loadHTML($html === '' ? '<html></html>' : $html, LIBXML_NONET);
    $nodes = (new DOMXPath($document))->query($expression);

    return $nodes === false ? 0 : $nodes->length;
}
