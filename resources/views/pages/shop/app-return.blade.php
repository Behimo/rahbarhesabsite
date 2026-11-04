<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>بازگشت به اپلیکیشن</title>
    <style>
        body { font-family: tahoma, sans-serif; background: #f6f7f9; color: #1f2933; margin: 0; }
        main { max-width: 28rem; margin: 4rem auto; background: #fff; padding: 1.5rem; border-radius: 1rem; }
        a, button { display: inline-block; margin-top: 1rem; background: #0f766e; color: #fff; text-decoration: none; border: 0; border-radius: .5rem; padding: .75rem 1rem; }
    </style>
</head>
<body>
<main>
    <h1>{{ $status === 'success' ? 'پرداخت ثبت شد' : 'پرداخت انجام نشد' }}</h1>
    <p>شماره سفارش: {{ $orderNumber }}</p>
    <p>برای دیدن نتیجه، به اپلیکیشن برگردید.</p>
    @if ($deepLink)
        <a href="{{ $deepLink }}">بازگشت به اپلیکیشن</a>
    @endif
</main>
</body>
</html>
