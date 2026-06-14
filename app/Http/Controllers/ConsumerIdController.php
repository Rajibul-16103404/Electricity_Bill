<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsumerIdRequest;
use App\Http\Resources\ConsumerIdResource;
use App\Models\ConsumerId;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ConsumerIdController extends Controller
{
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

        return new ConsumerIdResource($consumerId);
    }

    /**
     * Display the specified resource.
     */
    public function show(ConsumerId $consumerId): ConsumerIdResource
    {
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
}
