<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\StoreConsumerIdRequest;
use App\Http\Resources\ConsumerIdResource;
use App\Models\ConsumerId;
use App\Services\NescoScraperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ConsumerIdController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected NescoScraperService $scraperService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return ConsumerIdResource::collection(ConsumerId::latest()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreConsumerIdRequest $request): ConsumerIdResource
    {
        $consumerId = ConsumerId::create($request->validated());

        // Attempt to scrape details from NESCO
        $this->scraperService->scrapeAndSync($consumerId);

        return new ConsumerIdResource($consumerId->load('recharges'));
    }

    /**
     * Display the specified resource.
     */
    public function show(ConsumerId $consumerId): ConsumerIdResource
    {
        return new ConsumerIdResource($consumerId->load('recharges'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConsumerId $consumerId): Response
    {
        $consumerId->delete();

        return response()->noContent();
    }

    /**
     * Authenticate or register the consumer ID and generate a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $consumerId = ConsumerId::firstOrCreate(
            ['consumer_id' => $request->validated('consumer_id')]
        );

        // Attempt to scrape details from NESCO
        $this->scraperService->scrapeAndSync($consumerId);

        $token = $consumerId->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'consumer' => new ConsumerIdResource($consumerId->load('recharges')),
        ]);
    }

    /**
     * Sync data from NESCO portal for the specified consumer.
     */
    public function sync(ConsumerId $consumerId): ConsumerIdResource
    {
        $this->scraperService->scrapeAndSync($consumerId);

        return new ConsumerIdResource($consumerId->load('recharges'));
    }
}
