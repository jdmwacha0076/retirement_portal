<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * This project's Laravel skeleton ships a minimal base Controller (no
 * traits) by default - AuthorizesRequests is added back here so
 * $this->authorize(...) works, since PaymentRequestController relies on
 * it for every policy check (PaymentRequestPolicy) rather than only
 * hiding buttons in Blade. Safe/additive for every other controller too.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
