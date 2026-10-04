<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->whereKeyNot($request->user()->id)
            ->orderBy('name')
            ->get();

        $conversations = $request->user()
            ->conversations()
            ->with([
                'users:id,name,email',
                'messages' => fn ($query) => $query->latest('id')->limit(1),
            ])
            ->latest('conversations.updated_at')
            ->get();

        return view(
            'chat.index',
            compact(
                'users',
                'conversations'
            )
        );
    }

    public function start(
        Request $request,
        User $user
    ): RedirectResponse {
        abort_if(
            $request->user()->id === $user->id,
            422
        );

        $ids = [
            $request->user()->id,
            $user->id,
        ];

        sort($ids);

        $directKey = implode(':', $ids);

        $conversation = DB::transaction(
            function () use (
                $directKey,
                $request,
                $user
            ) {
                $conversation = Conversation::firstOrCreate(
                    [
                        'direct_key' => $directKey,
                    ],
                    [
                        'type' => 'direct',
                    ]
                );

                $conversation->users()
                    ->syncWithoutDetaching([
                        $request->user()->id,
                        $user->id,
                    ]);

                return $conversation;
            }
        );

        return redirect()->route(
            'chat.show',
            $conversation
        );
    }

    public function show(
        Request $request,
        Conversation $conversation
    ): View {
        Gate::authorize(
            'view',
            $conversation
        );

        $conversation->load(
            'users:id,name,email'
        );

        $messages = $conversation
            ->messages()
            ->with('sender:id,name')
            ->latest('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $partner = $conversation
            ->users
            ->firstWhere(
                'id',
                '!=',
                $request->user()->id
            );

        return view(
            'chat.show',
            compact(
                'conversation',
                'messages',
                'partner'
            )
        );
    }

    public function send(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        Gate::authorize(
            'view',
            $conversation
        );

        $data = $request->validate([
            'body' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        $message = $conversation
            ->messages()
            ->create([
                'sender_id' => $request->user()->id,

                'body' => $data['body'],
            ]);

        $conversation->touch();

        $message->load(
            'sender:id,name'
        );

        broadcast(
            new MessageSent($message)
        )->toOthers();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name,
                'read_at' => null,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }

    public function read(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        Gate::authorize(
            'view',
            $conversation
        );

        $messageIds = $conversation
            ->messages()
            ->where(
                'sender_id',
                '!=',
                $request->user()->id
            )
            ->whereNull('read_at')
            ->pluck('id');

        if ($messageIds->isNotEmpty()) {
            Message::query()
                ->whereIn('id', $messageIds)
                ->update([
                    'read_at' => now(),
                ]);

            broadcast(
                new MessageRead(
                    $conversation->id,
                    $request->user()->id,
                    $messageIds->all()
                )
            )->toOthers();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function older(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        Gate::authorize(
            'view',
            $conversation
        );

        $data = $request->validate([
            'before' => [
                'required',
                'integer',
            ],
        ]);

        $messages = $conversation
            ->messages()
            ->with('sender:id,name')
            ->where(
                'id',
                '<',
                $data['before']
            )
            ->latest('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'data' => $messages,
        ]);
    }
}
