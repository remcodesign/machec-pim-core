<?php

namespace App\Concerns;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait WritesAuditLog
{
    /**
     * @param  User  $actor  The user the action is attributed to.
     * @param  Model|null  $subject  The record the action was performed on.
     *                               Defaults to the actor themselves (login/register).
     */
    private function recordAuditLog(Request $request, User $actor, string $action, ?Model $subject = null): void
    {
        // If no specific subject is provided, default to the actor themselves.
        $subject ??= $actor;

        AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'ip_address' => $request->ip(),
        ]);
    }
}
