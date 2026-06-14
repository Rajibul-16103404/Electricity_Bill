<?php

use App\Models\ConsumerId;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('can list all consumer ids', function () {
    ConsumerId::factory()->count(3)->create();

    $response = $this->getJson('/api/consumer-ids');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'consumer_id', 'created_at', 'updated_at'],
            ],
        ]);
});

test('can store a new consumer id', function () {
    $data = [
        'consumer_id' => '1234567890',
    ];

    $response = $this->postJson('/api/consumer-ids', $data);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['id', 'consumer_id', 'created_at', 'updated_at'],
        ])
        ->assertJsonFragment([
            'consumer_id' => '1234567890',
        ]);

    $this->assertDatabaseHas('consumer_ids', [
        'consumer_id' => '1234567890',
    ]);
});

test('validation errors when storing a consumer id', function () {
    // Required check
    $response = $this->postJson('/api/consumer-ids', []);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['consumer_id']);

    // Unique check
    ConsumerId::factory()->create(['consumer_id' => '1234567890']);
    $response = $this->postJson('/api/consumer-ids', ['consumer_id' => '1234567890']);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['consumer_id']);
});

test('can show a specific consumer id', function () {
    $consumer = ConsumerId::factory()->create(['consumer_id' => '9876543210']);

    $response = $this->getJson("/api/consumer-ids/{$consumer->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => ['id', 'consumer_id', 'created_at', 'updated_at'],
        ])
        ->assertJsonFragment([
            'id' => $consumer->id,
            'consumer_id' => '9876543210',
        ]);
});

test('returns 404 if consumer id does not exist', function () {
    $response = $this->getJson('/api/consumer-ids/999');

    $response->assertNotFound();
});

test('can delete a consumer id', function () {
    $consumer = ConsumerId::factory()->create();

    $response = $this->deleteJson("/api/consumer-ids/{$consumer->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('consumer_ids', [
        'id' => $consumer->id,
    ]);
});
