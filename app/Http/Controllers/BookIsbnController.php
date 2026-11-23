<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class BookIsbnController extends Controller
{
    public function show(string $isbn): JsonResponse
    {
        if (! preg_match('/^[0-9]{13}$/', $isbn)) {
            return response()->json([
                'message' => 'ISBNは13桁の数字で入力してください。',
            ], 400);
        }

        try {
            $response = Http::timeout(10)
                ->get('https://www.googleapis.com/books/v1/volumes', [
                    'q' => 'isbn:'.$isbn,
                    'maxResults' => 1,
                ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => '書籍情報の取得中に通信エラーが発生しました。手動で入力してください。',
            ], 500);
        }

        if ($response->status() === 429) {
            return response()->json([
                'message' => 'ISBN検索の利用上限に達しました。時間をおいて再試行するか、手動で入力してください。',
            ], 429);
        }

        if (! $response->successful()) {
            return response()->json([
                'message' => '書籍情報の取得に失敗しました。手動で入力してください。',
            ], 500);
        }

        $volume = $response->json('items.0.volumeInfo');

        if (! is_array($volume)) {
            return response()->json([
                'message' => '該当する書籍が見つかりませんでした。手動で入力してください。',
            ], 404);
        }

        $publishedDate = $volume['publishedDate'] ?? null;

        if (is_string($publishedDate)) {
            if (preg_match('/^\d{4}$/', $publishedDate)) {
                $publishedDate .= '-01-01';
            } elseif (preg_match('/^\d{4}-\d{2}$/', $publishedDate)) {
                $publishedDate .= '-01';
            } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $publishedDate)) {
                $publishedDate = null;
            }
        } else {
            $publishedDate = null;
        }

        return response()->json([
            'title' => $volume['title'] ?? null,
            'author' => isset($volume['authors']) && is_array($volume['authors'])
                ? implode('、', $volume['authors'])
                : null,
            'published_date' => $publishedDate,
            'description' => $volume['description'] ?? null,
            'image_url' => $volume['imageLinks']['thumbnail'] ?? null,
        ]);
    }
}
