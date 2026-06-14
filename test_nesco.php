<?php

require 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel to use HTTP client
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$client = Http::withOptions([
    'cookies' => new \GuzzleHttp\Cookie\CookieJar(),
    'verify' => false, // bypass SSL issues if any
]);

// 1. GET page to get session cookies and CSRF token
$response = $client->get('https://customer.nesco.gov.bd/pre/panel');
$html = $response->body();

// Extract CSRF token
preg_match('/meta name="csrf-token" content="([^"]+)"/', $html, $matches);
$csrfToken = $matches[1] ?? null;

echo "CSRF Token: " . ($csrfToken ?: "Not Found") . "\n";

if ($csrfToken) {
    // 2. Send POST request with dummy cust_no
    $postResponse = $client->asForm()->post('https://customer.nesco.gov.bd/pre/panel', [
        '_token' => $csrfToken,
        'cust_no' => '12345678',
        'submit' => 'রিচার্জ হিস্ট্রি', // Recharge History button value
    ]);

    echo "POST Response Status: " . $postResponse->status() . "\n";
    file_put_contents('nesco_response.html', $postResponse->body());
    echo "Response saved to nesco_response.html\n";
}
