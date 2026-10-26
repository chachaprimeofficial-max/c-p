<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    public function generateProductCopy(string $productName, string $notes, string $locale = 'en'): string
    {
        $key = (string) config('services.gemini.key');
        if ($key === '') throw new RuntimeException('Gemini API key is not configured.');
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $prompt = "Write accurate ecommerce product copy in locale {$locale}. Product name: {$productName}. Seller notes: {$notes}. Return a concise description only. Do not invent certifications, materials, specifications, stock or claims that are not in the notes.";
        $response = Http::timeout(25)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".urlencode($key), [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 700],
        ])->throw()->json();
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($text) || trim($text) === '') throw new RuntimeException('Gemini returned an empty response.');
        return trim($text);
    }
}
