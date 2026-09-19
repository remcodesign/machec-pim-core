<?php

namespace App\Enums;

/**
 * PIM Core's local list of service-token abilities (D101).
 */
enum ServiceAbility: string
{
    case StockMovementsWrite = 'stock:movements:write';
    case CatalogRead = 'catalog:read';
}
