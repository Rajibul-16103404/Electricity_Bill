<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckTokenRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\StoreConsumerIdRequest;
use App\Http\Requests\StoreDailyReportRequest;
use App\Http\Resources\ConsumerIdResource;
use App\Http\Resources\DailyReportResource;
use App\Http\Resources\MonthlyUsageResource;
use App\Http\Resources\RechargeResource;
use App\Models\ConsumerId;
use App\Models\DailyReport;
use App\Services\DailyReportService;
use App\Services\NescoScraperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

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

        return new ConsumerIdResource($consumerId->refresh());
    }

    /**
     * Display the specified resource.
     */
    public function show(ConsumerId $consumerId): ConsumerIdResource
    {
        if (! $consumerId->updated_at || ! $consumerId->updated_at->isToday()) {
            $this->scraperService->scrapeAndSync($consumerId);
            $consumerId->refresh();
        }

        return new ConsumerIdResource($consumerId);
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
            'consumer' => new ConsumerIdResource($consumerId->refresh()),
        ]);
    }

    /**
     * Sync data from NESCO portal for the specified consumer.
     */
    public function sync(ConsumerId $consumerId): ConsumerIdResource
    {
        $this->scraperService->scrapeAndSync($consumerId);

        return new ConsumerIdResource($consumerId->refresh());
    }

    /**
     * Check if a token is expired or not.
     */
    public function checkToken(CheckTokenRequest $request): JsonResponse
    {
        $token = $request->validated('token') ?? $request->bearerToken();

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return response()->json([
                'valid' => false,
                'expired' => null,
                'message' => 'Token not found or invalid.',
            ]);
        }

        $expiration = config('sanctum.expiration');

        $isExpired = false;
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            $isExpired = true;
        } elseif ($expiration && $accessToken->created_at->addMinutes($expiration)->isPast()) {
            $isExpired = true;
        }

        if ($isExpired) {
            return response()->json([
                'valid' => false,
                'expired' => true,
                'message' => 'Token has expired.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'expired' => false,
            'message' => 'Token is valid.',
            'expires_at' => $accessToken->expires_at ? $accessToken->expires_at->toIso8601String() : null,
        ]);
    }

    /**
     * Display a listing of recharges for the specified consumer.
     */
    public function recharges(ConsumerId $consumerId): AnonymousResourceCollection
    {
        if (! $consumerId->updated_at || ! $consumerId->updated_at->isToday()) {
            $this->scraperService->scrapeAndSync($consumerId);
        }

        return RechargeResource::collection($consumerId->recharges()->orderBy('id', 'asc')->get());
    }

    /**
     * Display a listing of monthly usages for the specified consumer.
     */
    public function monthlyUsages(ConsumerId $consumerId): AnonymousResourceCollection
    {
        if (! $consumerId->updated_at || ! $consumerId->updated_at->isToday()) {
            $this->scraperService->scrapeAndSync($consumerId);
        }

        return MonthlyUsageResource::collection($consumerId->monthlyUsages()->orderBy('id', 'asc')->get());
    }

    /**
     * Display a listing of daily reports for the specified consumer.
     */
    public function dailyReports(ConsumerId $consumerId): AnonymousResourceCollection
    {
        if (! $consumerId->updated_at || ! $consumerId->updated_at->isToday()) {
            $this->scraperService->scrapeAndSync($consumerId);
        }

        return DailyReportResource::collection($consumerId->dailyReports()->orderBy('date', 'desc')->get());
    }

    /**
     * Store a manually entered daily report for the specified consumer.
     */
    public function storeDailyReport(StoreDailyReportRequest $request, ConsumerId $consumerId): DailyReportResource
    {
        $dailyReport = DailyReport::updateOrCreate(
            [
                'consumer_id_id' => $consumerId->id,
                'date' => $request->validated('date'),
            ],
            [
                'remaining_balance' => $request->validated('remaining_balance'),
            ]
        );

        app(DailyReportService::class)->recalculateAll($consumerId);

        return new DailyReportResource($dailyReport->refresh());
    }
}
