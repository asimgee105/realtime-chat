<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'chat.{conversationId}',
    function (
        User $user,
        int $conversationId
    ) {
        $allowed = Conversation::query()
            ->whereKey($conversationId)
            ->whereHas(
                'users',
                fn ($query) => $query->where(
                    'users.id',
                    $user->id
                )
            )
            ->exists();

        if (! $allowed) {
            return false;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
);
