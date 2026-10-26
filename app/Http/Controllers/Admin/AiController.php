<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiController extends Controller
{
    public function productCopy(Request $request, GeminiService $gemini): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:5000'],
            'locale' => ['nullable', 'in:en,zh_CN,ur,ar,ja'],
        ]);
        try {
            return response()->json([
                'description' => $gemini->generateProductCopy($data['name'], $data['notes'], $data['locale'] ?? 'en'),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'AI copy could not be generated. Check the Gemini API configuration and try again.'], 422);
        }
    }
}
