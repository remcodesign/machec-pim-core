<?php

namespace App\Enums;

/**
 * pim-core's own local, tiny role enum (D53) — never the shared
 * machec-contracts `RoleName` enum. Currently a single case since this
 * app only has one admin-capable role; the seeded/UI-managed role name
 * string (`pim_admin`) still matches the plain-string literal already
 * used by `RoleMiddleware::using('pim_admin')` and `DatabaseSeeder`.
 */
enum PimRole: string
{
    case PimAdmin = 'pim_admin';
}
