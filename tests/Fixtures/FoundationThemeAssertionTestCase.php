<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\FoundationTheme\Testing\AssertsPublicThemeOutputSafety;
use Capell\FoundationTheme\Testing\AssertsThemeDemoContentScaffolding;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/** @extends TestCase<MockObject> */
abstract class FoundationThemeAssertionTestCase extends TestCase
{
    use AssertsPublicThemeOutputSafety;
    use AssertsThemeDemoContentScaffolding;
}
