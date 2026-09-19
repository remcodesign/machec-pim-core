<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * The only class allowed to bump a generation counter (D14) — pim-core's
 * own local copy of this pattern, backed by this app's dedicated Flex
 * Cache instance (D37). Bumping `cache_gen:{entity}` makes every
 * generation-stamped cache key built from it unreachable without needing
 * to enumerate or delete them.
 */
class CacheGenerationService
{
    public function bump(string $entity): void
    {
        Cache::store('redis')->increment("cache_gen:{$entity}");
    }
}
