<?php

use App\Models\ConsumerId;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('can login with a new consumer id and scrape data', function () {
    $responseHtml = file_get_contents(base_path('nesco_response.html'));

    Http::fake([
        'https://customer.nesco.gov.bd/*' => function ($request) use ($responseHtml) {
            if ($request->method() === 'GET') {
                return Http::response('<meta name="csrf-token" content="mock-token">', 200);
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
                'recharges' => [
                    '*' => [
                        'id',
                        'order_no',
                        'token',
                        'seq',
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

    Http::fake([
        'https://customer.nesco.gov.bd/*' => function ($request) use ($responseHtml) {
            if ($request->method() === 'GET') {
                return Http::response('<meta name="csrf-token" content="mock-token">', 200);
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
                'recharges',
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
});
