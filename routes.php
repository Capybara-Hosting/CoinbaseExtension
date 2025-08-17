<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Gateways\CoinbaseCommerce\CoinbaseCommerce;

Route::post('/extensions/coinbasecommerce/webhook', [CoinbaseCommerce::class, 'webhook'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('extensions.gateways.coinbasecommerce.webhook');
