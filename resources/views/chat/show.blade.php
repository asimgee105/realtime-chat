<!DOCTYPE html>
<html>
<head>

    <meta charset="UTF-8">

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Chat</title>

    @vite(['resources/js/app.js'])

    <style>
        * {
            box-sizing:border-box;
        }

        body {
            margin:0;
            font-family:Arial;
            background:#0f172a;
            color:white;
        }

        .chat {
            max-width:900px;
            height:90vh;
            margin:5vh auto;
            background:#1e293b;
            border-radius:20px;
            display:flex;
            flex-direction:column;
            overflow:hidden;
        }

        .header {
            padding:18px;
            background:#111827;
            display:flex;
            justify-content:space-between;
        }

        #messages {
            flex:1;
            overflow-y:auto;
            padding:20px;
        }

        .message {
            max-width:70%;
            margin:10px 0;
            padding:12px 15px;
            border-radius:14px;
        }

        .mine {
            background:#2563eb;
            margin-left:auto;
        }

        .other {
            background:#334155;
        }

        .meta {
            font-size:11px;
            opacity:.7;
            margin-top:5px;
        }

        .composer {
            display:flex;
            padding:15px;
            gap:10px;
            background:#111827;
        }

        textarea {
            flex:1;
            resize:none;
            padding:12px;
            border-radius:10px;
        }

        button {
            background:#2563eb;
            color:white;
            border:0;
            border-radius:10px;
            padding:12px 20px;
            cursor:pointer;
        }

        #typing {
            padding:0 20px 8px;
            color:#94a3b8;
            font-size:13px;
        }

        #onlineStatus {
            color:#22c55e;
            font-size:13px;
        }
    </style>

</head>

<body>

<div class="chat">

    <div class="header">

        <div>
            <a
                href="{{ route('chat.index') }}"
                style="color:#60a5fa;"
            >
                ← Back
            </a>

            <h3 style="display:inline;margin-left:15px;">
                {{ $partner?->name }}
            </h3>
        </div>

        <span id="onlineStatus">
            Offline
        </span>

    </div>

    <div style="text-align:center;padding:8px;">
        <button id="loadOlder">
            Purane Messages
        </button>
    </div>

    <div id="messages">

        @foreach($messages as $message)

            <div
                class="message {{ $message->sender_id === auth()->id() ? 'mine' : 'other' }}"
                data-id="{{ $message->id }}"
            >

                <div>
                    {{ $message->body }}
                </div>

                <div class="meta">

                    {{ $message->created_at->format('H:i') }}

                    @if($message->sender_id === auth()->id())

                        <span class="seen">

                            {{ $message->read_at ? 'Seen' : 'Sent' }}

                        </span>

                    @endif

                </div>

            </div>

        @endforeach

    </div>

    <div id="typing"></div>

    <form
        id="messageForm"
        class="composer"
    >

        <textarea
            id="messageInput"
            rows="2"
            placeholder="Message likho..."
        ></textarea>

        <button type="submit">
            Send
        </button>

    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const conversationId = {{ $conversation->id }};
    const currentUserId = {{ auth()->id() }};
    const partnerId = {{ $partner?->id ?? 0 }};

    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        .content;

    const messages = document.getElementById('messages');

    const input =
        document.getElementById('messageInput');

    const form =
        document.getElementById('messageForm');

    const typing =
        document.getElementById('typing');

    const onlineStatus =
        document.getElementById('onlineStatus');

    const channel = window.Echo.join(
        `chat.${conversationId}`
    );

    channel
        .here(users => {

            const partnerOnline = users.some(
                user => user.id === partnerId
            );

            onlineStatus.innerText =
                partnerOnline
                    ? 'Online'
                    : 'Offline';
        })

        .joining(user => {

            if (user.id === partnerId) {
                onlineStatus.innerText = 'Online';
            }
        })

        .leaving(user => {

            if (user.id === partnerId) {
                onlineStatus.innerText = 'Offline';
            }
        })

        .listen('.message.sent', event => {

            appendMessage(event);

            markRead();
        })

        .listen('.message.read', event => {

            if (event.readerId !== currentUserId) {

                event.messageIds.forEach(id => {

                    const element =
                        document.querySelector(
                            `[data-id="${id}"] .seen`
                        );

                    if (element) {
                        element.innerText = 'Seen';
                    }
                });
            }
        })

        .listenForWhisper(
            'typing',
            event => {

                if (
                    event.user_id !== currentUserId
                ) {
                    typing.innerText =
                        event.typing
                            ? `${event.name} likh raha hai...`
                            : '';
                }
            }
        );

    let typingTimeout;

    input.addEventListener('input', () => {

        channel.whisper('typing', {
            user_id: currentUserId,
            name: @json(auth()->user()->name),
            typing: true
        });

        clearTimeout(typingTimeout);

        typingTimeout = setTimeout(() => {

            channel.whisper('typing', {
                user_id: currentUserId,
                name: @json(auth()->user()->name),
                typing: false
            });

        }, 1000);
    });

    form.addEventListener('submit', async event => {

        event.preventDefault();

        const body = input.value.trim();

        if (!body) {
            return;
        }

        input.value = '';

        const response = await fetch(
            `/chat/${conversationId}/messages`,
            {
                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/json',

                    'Accept':
                        'application/json',

                    'X-CSRF-TOKEN':
                        csrf
                },

                body: JSON.stringify({
                    body: body
                })
            }
        );

        const data = await response.json();

        if (response.ok) {
            appendMessage(
                data.message
            );
        }
    });

    function appendMessage(message) {

        if (
            document.querySelector(
                `[data-id="${message.id}"]`
            )
        ) {
            return;
        }

        const div = document.createElement('div');

        div.dataset.id = message.id;

        div.className =
            'message ' +
            (
                message.sender_id === currentUserId
                    ? 'mine'
                    : 'other'
            );

        div.innerHTML = `
            <div>${escapeHtml(message.body)}</div>

            <div class="meta">

                ${new Date(
                    message.created_at
                ).toLocaleTimeString(
                    [],
                    {
                        hour:'2-digit',
                        minute:'2-digit'
                    }
                )}

                ${
                    message.sender_id === currentUserId
                    ? '<span class="seen">Sent</span>'
                    : ''
                }

            </div>
        `;

        messages.appendChild(div);

        messages.scrollTop =
            messages.scrollHeight;
    }

    async function markRead() {

        await fetch(
            `/chat/${conversationId}/read`,
            {
                method:'POST',

                headers: {
                    'Accept':
                        'application/json',

                    'X-CSRF-TOKEN':
                        csrf
                }
            }
        );
    }

    markRead();

    messages.scrollTop =
        messages.scrollHeight;

    document
        .getElementById('loadOlder')
        .addEventListener(
            'click',
            async () => {

                const first =
                    messages.querySelector(
                        '.message'
                    );

                if (!first) {
                    return;
                }

                const before =
                    first.dataset.id;

                const response =
                    await fetch(
                        `/chat/${conversationId}/messages?before=${before}`,
                        {
                            headers: {
                                'Accept':
                                    'application/json'
                            }
                        }
                    );

                const data =
                    await response.json();

                data.data
                    .reverse()
                    .forEach(message => {

                        const div =
                            document.createElement(
                                'div'
                            );

                        div.dataset.id =
                            message.id;

                        div.className =
                            'message ' +
                            (
                                message.sender_id === currentUserId
                                    ? 'mine'
                                    : 'other'
                            );

                        div.innerHTML =
                            `<div>${escapeHtml(message.body)}</div>`;

                        messages.prepend(div);
                    });
            }
        );

    function escapeHtml(text) {

        const div =
            document.createElement('div');

        div.textContent = text;

        return div.innerHTML;
    }

});
</script>

</body>
</html>