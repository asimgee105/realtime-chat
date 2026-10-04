<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>

<body style="background:#0f172a;color:white;font-family:Arial;">

<div style="max-width:400px;margin:100px auto;background:#1e293b;padding:30px;border-radius:15px;">

    <h2>Register</h2>

    <form method="POST" action="{{ route('register.submit') }}">
        @csrf

        <input
            name="name"
            placeholder="Name"
            required
            style="width:100%;padding:12px;margin:7px 0;"
        >

        <input
            name="email"
            type="email"
            placeholder="Email"
            required
            style="width:100%;padding:12px;margin:7px 0;"
        >

        <input
            name="password"
            type="password"
            placeholder="Password"
            required
            style="width:100%;padding:12px;margin:7px 0;"
        >

        <input
            name="password_confirmation"
            type="password"
            placeholder="Confirm Password"
            required
            style="width:100%;padding:12px;margin:7px 0;"
        >

        <button
            style="width:100%;padding:12px;background:#2563eb;color:white;border:0;"
        >
            Register
        </button>

    </form>

</div>

</body>
</html>