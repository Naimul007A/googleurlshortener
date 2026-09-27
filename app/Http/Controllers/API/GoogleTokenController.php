<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UpdateGoogleTokenRequest;
use App\Models\GoogleToken;
use Illuminate\Support\Facades\DB;

class GoogleTokenController extends Controller
{
    public function update(UpdateGoogleTokenRequest $request)
    {
        $token = trim($request->validated('token'));

        DB::transaction(function () use ($token): void {
            GoogleToken::query()->active()->update(['status' => false]);
            GoogleToken::query()->create([
                'token' => $token,
                'status' => true,
            ]);
        });

        return response()->json([
            'message' => 'Google token updated successfully.',
            'status' => 200,
        ]);
    }
}
