<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $paymentRequest->displayReference() }} | Voucher</title>
    <link href="{{ asset('assets/css/print.css') }}" rel="stylesheet">
</head>

<body class="voucher-body">
    @include('payment-requests.print._voucher', ['paymentRequest' => $paymentRequest])
</body>

</html>
