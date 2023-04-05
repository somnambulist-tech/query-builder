<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Support;

use function preg_replace;

trait QueryAssertsTrait
{
    /**
     * Assertion for comparing a regex pattern against a query having its identifiers
     * quoted. It accepts queries quoted with the characters `<` and `>`. If the third
     * parameter is set to true, it will alter the pattern to both accept quoted and
     * unquoted queries
     *
     * @param string $pattern
     * @param string $query the result to compare against
     */
    public function assertQueryContains(string $pattern, string $query): void
    {
        $pattern = str_replace('<', '[`"\[]', $pattern);
        $pattern = str_replace('>', '[`"\]]', $pattern);

        $this->assertMatchesRegularExpression('#' . $pattern . '#', $query);
    }

    /**
     * Assert that a string matches SQL with db-specific characters like quotes removed.
     *
     * @param string $expected The expected sql
     * @param string $actual The sql to compare
     * @param string $message The message to display on failure
     *
     * @return void
     */
    public function assertEqualsSql(string $expected, string $actual, string $message = ''): void
    {
        $this->assertEquals($expected, preg_replace('/[`"\[\]]/', '', $actual), $message);
    }

    public function assertQueryStartsWith(string $pattern, string $query): void
    {
        $this->assertStringStartsWith($pattern, $query);
    }
}
