<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * A machine credential, deliberately separate from the app's human users.
 *
 * @property int $id
 * @property string $label
 * @property Carbon|null $created_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['label'])]
class ServiceClient extends Model
{
    use HasApiTokens, SoftDeletes;

    const UPDATED_AT = null;
}
