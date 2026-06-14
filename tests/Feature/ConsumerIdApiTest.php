<?php

use App\Models\ConsumerId;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('can login with a new consumer id and generate a token', function () {
    $data = [
        'consumer_id' => '12345678',
    ];

    $response = $this->postJson('/api/login', $data);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'token',
            'consumer' => ['id', 'consumer_id', 'created_at', 'updated_at'],
        ])
        ->assertJsonFragment([
            'consumer_id' => '12345678',
        ]);

    $this->assertDatabaseHas('consumer_ids', [
        'consumer_id' => '12345678',
    ]);
});

test('can login with an existing consumer id without creating a duplicate and generate a token', function () {
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

    $response = $this->getJson("/api/consumer-ids/{$target->id}");

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

    $response = $this->deleteJson("/api/consumer-ids/{$target->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('consumer_ids', [
        'id' => $target->id,
    ]);
});
