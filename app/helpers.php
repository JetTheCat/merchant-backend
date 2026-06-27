<?php

declare(strict_types=1);

if (! function_exists('add')) {
    function add(string|float $left, string|float $right, int $scale = 2): string
    {
        return bcadd((string) $left, (string) $right, $scale);
    }
}

if (! function_exists('sub')) {
    function sub(string|float $left, string|float $right, int $scale = 2): string
    {
        return bcsub((string) $left, (string) $right, $scale);
    }
}

if (! function_exists('mul')) {
    function mul(string|float $left, string|float $right, int $scale = 2): string
    {
        return bcmul((string) $left, (string) $right, $scale);
    }
}

if (! function_exists('div')) {
    function div(string|float $left, string|float $right, int $scale = 2): string
    {
        return bcdiv((string) $left, (string) $right, $scale);
    }
}

if (! function_exists('compare')) {
    function compare(string|float $left, string|float $right, int $scale = 2): int
    {
        return bccomp((string) $left, (string) $right, $scale);
    }
}

if (! function_exists('gt')) {
    function gt(string|float $left, string|float $right, int $scale = 2): bool
    {
        return compare($left, $right, $scale) === 1;
    }
}

if (! function_exists('lt')) {
    function lt(string|float $left, string|float $right, int $scale = 2): bool
    {
        return compare($left, $right, $scale) === -1;
    }
}
