<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name', 'Sistem Peminjaman Mobil') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            box-sizing: border-box;
        }
        .container {
            max-width: 28rem;
            width: 100%;
            text-align: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .code {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.75rem;
            color: #0f172a;
        }
        p {
            font-size: 0.95rem;
            color: #64748b;
            line-height: 1.5;
            margin: 0 0 1.75rem;
        }
        .btn {
            display: inline-block;
            background-color: #004c8f;
            color: #ffffff;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            text-decoration: none;
            transition: background-color 0.15s;
        }
        .btn:hover {
            background-color: #003768;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">Kode @yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="/" class="btn">Kembali ke Beranda</a>
    </div>
</body>
</html>
