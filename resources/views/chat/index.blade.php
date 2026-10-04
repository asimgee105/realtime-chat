<!DOCTYPE html>
<html>
<head>
    <title>Real Time Chat</title>

    <style>
        body {
            margin:0;
            font-family:Arial;
            background:#0f172a;
            color:white;
        }

        .page {
            max-width:1000px;
            margin:40px auto;
        }

        .user {
            display:flex;
            justify-content:space-between;
            align-items:center;
            background:#1e293b;
            padding:15px;
            margin:8px 0;
            border-radius:10px;
        }

        button {
            background:#2563eb;
            color:white;
            border:0;
            border-radius:8px;
            padding:9px 14px;
            cursor:pointer;
        }

        a {
            color:#60a5fa;
        }
    </style>
</head>

<body>

<div class="page">

    <div style="display:flex;justify-content:space-between;">
        <h1>Chat Dashboard</h1>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button>Logout</button>
        </form>
    </div>

    <h2>Users</h2>

    @foreach($users as $user)

        <div class="user">

            <span>
                {{ $user->name }}
                <small>
                    {{ $user->email }}
                </small>
            </span>

            <form
                method="POST"
                action="{{ route('chat.start', $user) }}"
            >
                @csrf

                <button>
                    Chat Start
                </button>
            </form>

        </div>

    @endforeach

    <h2>Conversations</h2>

    @foreach($conversations as $conversation)

        @php
            $other = $conversation
                ->users
                ->firstWhere('id', '!=', auth()->id());
        @endphp

        <div class="user">

            <span>
                {{ $other?->name ?? 'Conversation' }}
            </span>

            <a
                href="{{ route('chat.show', $conversation) }}"
            >
                Open Chat
            </a>

        </div>

    @endforeach

</div>

</body>
</html>