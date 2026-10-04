<!DOCTYPE html>
<html>
<head>
    <title>Chat Login</title>

    <style>
        body {
            margin:0;
            font-family:Arial;
            background:#0f172a;
            color:white;
            display:flex;
            align-items:center;
            justify-content:center;
            min-height:100vh;
        }

        .card {
            width:380px;
            background:#1e293b;
            padding:32px;
            border-radius:18px;
        }

        input {
            width:100%;
            padding:13px;
            margin:8px 0;
            box-sizing:border-box;
            border-radius:9px;
            border:1px solid #475569;
            background:#0f172a;
            color:white;
        }

        button {
            width:100%;
            padding:13px;
            margin-top:12px;
            border:0;
            border-radius:9px;
            background:#2563eb;
            color:white;
            cursor:pointer;
        }

        a {
            color:#60a5fa;
        }

        .error {
            color:#f87171;
        }
    </style>
</head>

<body>

<div class="card">

    <h2>Login</h2>

    <form method="POST" action="{{ route('login.submit') }}">

        @csrf

        <input
            type="email"
            name="email"
            placeholder="Email"
            value="{{ old('email') }}"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <button type="submit">
            Login
        </button>

    </form>

    @error('email')
        <p class="error">{{ $message }}</p>
    @enderror

    <p>
        Account nahi?
        <a href="{{ route('register') }}">
            Register
        </a>
    </p>

</div>

</body>
</html>