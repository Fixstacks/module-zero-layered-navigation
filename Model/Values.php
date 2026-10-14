<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\LayeredNavigation\Model;

/**
 * A filter's chosen values in the URL: "49,50" (or color[]=49&color[]=50).
 */
class Values
{
    /**
     * @return string[]
     */
    public static function parse(mixed $param): array
    {
        $values = is_array($param) ? $param : explode(',', (string) $param);
        $values = array_filter(array_map(static fn ($v) => trim((string) $v), $values), static fn ($v) => $v !== '');

        return array_values(array_unique($values));
    }

    /**
     * The chosen values with $value added, or taken out if chosen already;
     * null when none is left (the parameter goes away).
     */
    public static function toggle(array $chosen, string $value): ?string
    {
        $next = in_array($value, $chosen, true)
            ? array_values(array_diff($chosen, [$value]))
            : [...$chosen, $value];

        return $next ? implode(',', $next) : null;
    }
}
