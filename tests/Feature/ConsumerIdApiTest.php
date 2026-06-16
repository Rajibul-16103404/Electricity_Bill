<?php

use App\Models\ConsumerId;
use App\Models\DailyReport;
use App\Models\Recharge;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $responseHtml = file_get_contents(base_path('nesco_response.html'));
    $consumptionHtml = '<html><tbody class="font_bill_summ"><tr><td>2026</td><td>May</td><td>699</td><td>-2.52</td><td>212.06</td><td>160</td><td>336</td><td>0</td><td>0</td><td>33.29</td><td>741.35</td><td>256.2</td><td>45.8</td></tr></tbody></html>';

    Http::fake([
        'https://customer.nesco.gov.bd/*' => function ($request) use ($responseHtml, $consumptionHtml) {
            if ($request->method() === 'GET') {
                return Http::response('<meta name="csrf-token" content="mock-token">', 200);
            }
            if ($request->method() === 'POST' && ($request->data()['submit'] ?? '') === 'মাসিক ব্যবহার') {
                return Http::response($consumptionHtml, 200);
            }

            return Http::response($responseHtml, 200);
        },
    ]);
});

test('can login with a new consumer id and scrape data', function () {
    $responseHtml = file_get_contents(base_path('nesco_response.html'));
    $consumptionHtml = '<html><tbody class="font_bill_summ"><tr><td>2026</td><td>May</td><td>699</td><td>-2.52</td><td>212.06</td><td>160</td><td>336</td><td>0</td><td>0</td><td>33.29</td><td>741.35</td><td>256.2</td><td>45.8</td></tr></tbody></html>';

    Http::fake([
        'https://customer.nesco.gov.bd/*' => function ($request) use ($responseHtml, $consumptionHtml) {
            if ($request->method() === 'GET') {
                return Http::response('<meta name="csrf-token" content="mock-token">', 200);
            }
            if ($request->method() === 'POST' && ($request->data()['submit'] ?? '') === 'মাসিক ব্যবহার') {
                return Http::response($consumptionHtml, 200);
            }

            return Http::response($responseHtml, 200);
        },
    ]);

    $data = [
        'consumer_id' => '12345678',
    ];

    $response = $this->postJson('/api/login', $data);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'token',
            'consumer' => [
                'id',
                'consumer_id',
                'customer_name',
                'father_husband_name',
                'address',
                'mobile',
                'billing_office',
                'feeder_name',
                'meter_no',
                'sanction_load',
                'tariff',
                'meter_type',
                'meter_status',
                'installation_date',
                'min_recharge',
                'remaining_balance',
                'balance_updated_at',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJsonFragment([
            'consumer_id' => '12345678',
            'customer_name' => 'Test',
            'address' => 'Test Rajshahi',
            'meter_no' => '31013005538',
        ]);

    $this->assertDatabaseHas('consumer_ids', [
        'consumer_id' => '12345678',
        'customer_name' => 'Test',
    ]);

    $this->assertDatabaseHas('recharges', [
        'order_no' => '9305b99e4713041193023587246084682',
        'sale_name' => 'UVS_BoguraSD1',
    ]);

    $this->assertDatabaseHas('monthly_usages', [
        'year' => 2026,
        'month' => 'May',
        'total_recharge' => 699.00,
    ]);
});

test('can login with an existing consumer id without creating a duplicate and generate a token', function () {
    Http::fake([
        'https://customer.nesco.gov.bd/*' => function ($request) {
            if ($request->method() === 'GET') {
                return Http::response('<meta name="csrf-token" content="mock-token">', 200);
            }

            return Http::response('<html></html>', 200);
        },
    ]);

    $consumer = ConsumerId::factory()->create(['consumer_id' => '98765432']);

    $data = [
        'consumer_id' => '98765432',
    ];

    $response = $this->postJson('/api/login', $data);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'token',
            'consumer' => ['id', 'consumer_id', 'created_at', 'updated_at'],
        ])
        ->assertJsonFragment([
            'id' => $consumer->id,
            'consumer_id' => '98765432',
        ]);

    // Ensure we still only have 1 record
    expect(ConsumerId::where('consumer_id', '98765432')->count())->toBe(1);
});

test('validation errors on login', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['consumer_id']);
});

test('unauthenticated request is blocked from consumer id endpoints', function () {
    $response = $this->getJson('/api/consumer-ids');
    $response->assertUnauthorized();
});

test('can list all consumer ids when authenticated', function () {
    $consumer = ConsumerId::factory()->create();
    Sanctum::actingAs($consumer);

    ConsumerId::factory()->count(2)->create();

    $response = $this->getJson('/api/consumer-ids');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'consumer_id', 'created_at', 'updated_at'],
            ],
        ]);
});

test('can store a new consumer id when authenticated', function () {
    Http::fake([
        'https://customer.nesco.gov.bd/*' => function ($request) {
            if ($request->method() === 'GET') {
                return Http::response('<meta name="csrf-token" content="mock-token">', 200);
            }

            return Http::response('<html></html>', 200);
        },
    ]);

    $consumer = ConsumerId::factory()->create();
    Sanctum::actingAs($consumer);

    $data = [
        'consumer_id' => '11223344',
    ];

    $response = $this->postJson('/api/consumer-ids', $data);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['id', 'consumer_id', 'created_at', 'updated_at'],
        ])
        ->assertJsonFragment([
            'consumer_id' => '11223344',
        ]);

    $this->assertDatabaseHas('consumer_ids', [
        'consumer_id' => '11223344',
    ]);
});

test('validation errors when storing a consumer id when authenticated', function () {
    $consumer = ConsumerId::factory()->create();
    Sanctum::actingAs($consumer);

    // Required check
    $response = $this->postJson('/api/consumer-ids', []);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['consumer_id']);

    // Unique check
    ConsumerId::factory()->create(['consumer_id' => '12345678']);
    $response = $this->postJson('/api/consumer-ids', ['consumer_id' => '12345678']);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['consumer_id']);
});

test('can show a specific consumer id when authenticated', function () {
    $consumer = ConsumerId::factory()->create();
    Sanctum::actingAs($consumer);

    $target = ConsumerId::factory()->create(['consumer_id' => '98765432']);

    $response = $this->getJson("/api/consumer-ids/{$target->consumer_id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => ['id', 'consumer_id', 'created_at', 'updated_at'],
        ])
        ->assertJsonFragment([
            'id' => $target->id,
            'consumer_id' => '98765432',
        ]);
});

test('returns 404 if consumer id does not exist when authenticated', function () {
    $consumer = ConsumerId::factory()->create();
    Sanctum::actingAs($consumer);

    $response = $this->getJson('/api/consumer-ids/999');

    $response->assertNotFound();
});

test('can delete a consumer id when authenticated', function () {
    $consumer = ConsumerId::factory()->create();
    Sanctum::actingAs($consumer);

    $target = ConsumerId::factory()->create();

    $response = $this->deleteJson("/api/consumer-ids/{$target->consumer_id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('consumer_ids', [
        'id' => $target->id,
    ]);
});

test('can sync consumer data from nesco when authenticated', function () {
    $consumer = ConsumerId::factory()->create(['consumer_id' => '12345678']);
    Sanctum::actingAs($consumer);

    $responseHtml = file_get_contents(base_path('nesco_response.html'));
    $consumptionHtml = '<html><tbody class="font_bill_summ"><tr><td>2026</td><td>May</td><td>699</td><td>-2.52</td><td>212.06</td><td>160</td><td>336</td><td>0</td><td>0</td><td>33.29</td><td>741.35</td><td>256.2</td><td>45.8</td></tr></tbody></html>';

    Http::fake([
        'https://customer.nesco.gov.bd/*' => function ($request) use ($responseHtml, $consumptionHtml) {
            if ($request->method() === 'GET') {
                return Http::response('<meta name="csrf-token" content="mock-token">', 200);
            }
            if ($request->method() === 'POST' && ($request->data()['submit'] ?? '') === 'মাসিক ব্যবহার') {
                return Http::response($consumptionHtml, 200);
            }

            return Http::response($responseHtml, 200);
        },
    ]);

    $response = $this->postJson("/api/consumer-ids/{$consumer->consumer_id}/sync");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'consumer_id',
                'customer_name',
                'father_husband_name',
                'address',
                'mobile',
                'billing_office',
                'feeder_name',
                'meter_no',
                'sanction_load',
                'tariff',
                'meter_type',
                'meter_status',
                'installation_date',
                'min_recharge',
                'remaining_balance',
                'balance_updated_at',
            ],
        ])
        ->assertJsonFragment([
            'consumer_id' => '12345678',
            'customer_name' => 'Test',
        ]);

    $this->assertDatabaseHas('consumer_ids', [
        'id' => $consumer->id,
        'customer_name' => 'Test',
    ]);

    $this->assertDatabaseHas('monthly_usages', [
        'consumer_id_id' => $consumer->id,
        'year' => 2026,
        'month' => 'May',
        'total_recharge' => 699.00,
    ]);
});

test('check-token endpoint validation error when no token provided', function () {
    $response = $this->postJson('/api/check-token', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

test('check-token endpoint returns invalid for a non-existent token', function () {
    $response = $this->postJson('/api/check-token', ['token' => 'invalid-token']);

    $response->assertSuccessful()
        ->assertJson([
            'valid' => false,
            'expired' => null,
            'message' => 'Token not found or invalid.',
        ]);
});

test('check-token endpoint returns valid for a valid token via payload', function () {
    $consumer = ConsumerId::factory()->create();
    $tokenResult = $consumer->createToken('test_token');

    $response = $this->postJson('/api/check-token', ['token' => $tokenResult->plainTextToken]);

    $response->assertSuccessful()
        ->assertJson([
            'valid' => true,
            'expired' => false,
            'message' => 'Token is valid.',
        ]);
});

test('check-token endpoint returns valid for a valid token via header', function () {
    $consumer = ConsumerId::factory()->create();
    $tokenResult = $consumer->createToken('test_token');

    $response = $this->withToken($tokenResult->plainTextToken)
        ->postJson('/api/check-token');

    $response->assertSuccessful()
        ->assertJson([
            'valid' => true,
            'expired' => false,
            'message' => 'Token is valid.',
        ]);
});

test('check-token endpoint returns expired for an expired token', function () {
    $consumer = ConsumerId::factory()->create();
    $tokenResult = $consumer->createToken('test_token');

    // Manually expire the token in database
    $tokenResult->accessToken->update([
        'expires_at' => now()->subMinutes(10),
    ]);

    $response = $this->postJson('/api/check-token', ['token' => $tokenResult->plainTextToken]);

    $response->assertSuccessful()
        ->assertJson([
            'valid' => false,
            'expired' => true,
            'message' => 'Token has expired.',
        ]);
});

test('can list recharges for a specific consumer when authenticated', function () {
    $consumer = ConsumerId::factory()->create();
    Sanctum::actingAs($consumer);

    $target = ConsumerId::factory()->create(['consumer_id' => '12345678']);

    $response = $this->getJson("/api/consumer-ids/{$target->consumer_id}/recharges");

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'order_no',
                    'token',
                    'rent',
                    'demand_charge',
                    'pfc',
                    'tax',
                    'subsidy_amount',
                    'purchase_amount',
                    'total_amount',
                    'purchase_energy',
                    'sale_name',
                    'purchase_date',
                    'debt_amount',
                    'paid_amount',
                ],
            ],
        ]);
});

test('can list monthly usages for a specific consumer when authenticated', function () {
    $consumer = ConsumerId::factory()->create();
    Sanctum::actingAs($consumer);

    $target = ConsumerId::factory()->create(['consumer_id' => '12345678']);

    $response = $this->getJson("/api/consumer-ids/{$target->consumer_id}/monthly-usages");

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'year',
                    'month',
                    'total_recharge',
                    'rebate',
                    'used_electricity_taka',
                    'meter_rent',
                    'demand_charge',
                    'pfc_charge',
                    'paid_arrear_penalty',
                    'vat',
                    'total_usage_deduction',
                    'meter_balance',
                    'used_electricity_kwh',
                ],
            ],
        ]);
});

test('can list daily reports for a specific consumer when authenticated', function () {
    $consumer = ConsumerId::factory()->create();
    Sanctum::actingAs($consumer);

    $target = ConsumerId::factory()->create(['consumer_id' => '12345678']);

    $response = $this->getJson("/api/consumer-ids/{$target->consumer_id}/daily-reports");

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'consumer_id_id',
                    'date',
                    'remaining_balance',
                    'recharge_amount',
                    'usage_taka',
                    'usage_kwh',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});

test('can store and update a daily report manually and recalculate usage', function () {
    $consumer = ConsumerId::factory()->create();
    Sanctum::actingAs($consumer);

    $target = ConsumerId::factory()->create();

    // Create a recharge for testing calculations
    Recharge::factory()->create([
        'consumer_id_id' => $target->id,
        'total_amount' => 500.00,
        'purchase_date' => '12-JUN-2026 10:00 AM', // 2026-06-12
    ]);

    // Create a previous daily report snapshot on 2026-06-11
    DailyReport::create([
        'consumer_id_id' => $target->id,
        'date' => '2026-06-11',
        'remaining_balance' => 300.00,
    ]);

    // Store a new daily report snapshot on 2026-06-13
    $response = $this->postJson("/api/consumer-ids/{$target->consumer_id}/daily-reports", [
        'date' => '2026-06-13',
        'remaining_balance' => 600.00,
    ]);

    // Calculation:
    // prev balance = 300.00
    // recharge on 12th = 500.00
    // curr balance = 600.00
    // usage = 300.00 + 500.00 - 600.00 = 200.00
    $response->assertSuccessful()
        ->assertJsonFragment([
            'date' => '2026-06-13',
            'remaining_balance' => 600.00,
            'recharge_amount' => 500.00,
            'usage_taka' => 200.00,
        ]);

    $this->assertDatabaseHas('daily_reports', [
        'consumer_id_id' => $target->id,
        'date' => '2026-06-13',
        'remaining_balance' => 600.00,
        'usage_taka' => 200.00,
    ]);
});

test('the nesco:scrape command runs successfully and syncs consumer data', function () {
    $consumer = ConsumerId::factory()->create(['consumer_id' => '12345678']);

    $responseHtml = file_get_contents(base_path('nesco_response.html'));
    $consumptionHtml = '<html><tbody class="font_bill_summ"><tr><td>2026</td><td>May</td><td>699</td><td>-2.52</td><td>212.06</td><td>160</td><td>336</td><td>0</td><td>0</td><td>33.29</td><td>741.35</td><td>256.2</td><td>45.8</td></tr></tbody></html>';

    Http::fake([
        'https://customer.nesco.gov.bd/*' => function ($request) use ($responseHtml, $consumptionHtml) {
            if ($request->method() === 'GET') {
                return Http::response('<meta name="csrf-token" content="mock-token">', 200);
            }
            if ($request->method() === 'POST' && ($request->data()['submit'] ?? '') === 'মাসিক ব্যবহার') {
                return Http::response($consumptionHtml, 200);
            }

            return Http::response($responseHtml, 200);
        },
    ]);

    $this->artisan('nesco:scrape')
        ->expectsOutput('Starting NESCO scrape and sync for 1 consumer(s)...')
        ->expectsOutput('Scraping consumer ID: 12345678...')
        ->expectsOutput('Successfully synced consumer ID: 12345678')
        ->expectsOutput('NESCO scrape and sync completed.')
        ->assertExitCode(0);

    $this->assertDatabaseHas('consumer_ids', [
        'id' => $consumer->id,
        'customer_name' => 'Test',
    ]);
});

test('on-demand endpoints always trigger scraping and update the database', function () {
    $consumer = ConsumerId::factory()->create([
        'consumer_id' => '12345678',
    ]);
    Sanctum::actingAs($consumer);

    // Let's call show
    $response = $this->getJson("/api/consumer-ids/{$consumer->consumer_id}");
    $response->assertSuccessful();

    // Verify HTTP fake was hit (scraper was called)
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'customer.nesco.gov.bd');
    });

    // Reset Http fakes
    $responseHtml = file_get_contents(base_path('nesco_response.html'));
    $consumptionHtml = '<html><tbody class="font_bill_summ"><tr><td>2026</td><td>May</td><td>699</td><td>-2.52</td><td>212.06</td><td>160</td><td>336</td><td>0</td><td>0</td><td>33.29</td><td>741.35</td><td>256.2</td><td>45.8</td></tr></tbody></html>';
    Http::fake([
        'https://customer.nesco.gov.bd/*' => function ($request) use ($responseHtml, $consumptionHtml) {
            if ($request->method() === 'GET') {
                return Http::response('<meta name="csrf-token" content="mock-token">', 200);
            }
            if ($request->method() === 'POST' && ($request->data()['submit'] ?? '') === 'মাসিক ব্যবহার') {
                return Http::response($consumptionHtml, 200);
            }

            return Http::response($responseHtml, 200);
        },
    ]);

    // Let's call recharges
    $response = $this->getJson("/api/consumer-ids/{$consumer->consumer_id}/recharges");
    $response->assertSuccessful();

    // Verify HTTP fake was hit (scraper was called)
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'customer.nesco.gov.bd');
    });
});
