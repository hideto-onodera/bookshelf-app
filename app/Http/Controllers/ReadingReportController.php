<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class ReadingReportController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $reviews = Review::query()
            ->where('user_id', $userId);

        $totalReviews = (clone $reviews)->count();

        $reviewedBooksCount = (clone $reviews)
            ->distinct()
            ->count('book_id');

        $averageRating = (clone $reviews)->avg('rating');

        $ratingCounts = (clone $reviews)
            ->select('rating', DB::raw('COUNT(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $ratingDistribution = [];

        for ($rating = 1; $rating <= 5; $rating++) {
            $ratingDistribution[$rating] = (int) ($ratingCounts[$rating] ?? 0);
        }

        $topRatedBooks = DB::table('reviews')
            ->join('books', 'reviews.book_id', '=', 'books.id')
            ->where('reviews.user_id', $userId)
            ->where('reviews.rating', '>=', 4)
            ->select(
                'books.id',
                'books.title',
                DB::raw('AVG(reviews.rating) as average_rating'),
                DB::raw('COUNT(reviews.id) as review_count')
            )
            ->groupBy('books.id', 'books.title')
            ->orderByDesc('average_rating')
            ->orderByDesc('review_count')
            ->orderBy('books.id')
            ->limit(5)
            ->get();

        $topGenres = DB::table('reviews')
            ->join('book_genre', 'reviews.book_id', '=', 'book_genre.book_id')
            ->join('genres', 'book_genre.genre_id', '=', 'genres.id')
            ->where('reviews.user_id', $userId)
            ->select(
                'genres.id',
                'genres.name',
                DB::raw('AVG(reviews.rating) as average_rating'),
                DB::raw('COUNT(reviews.id) as review_count')
            )
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('average_rating')
            ->orderByDesc('review_count')
            ->orderBy('genres.id')
            ->limit(5)
            ->get();

        return view('reading-reports.index', compact(
            'totalReviews',
            'reviewedBooksCount',
            'averageRating',
            'ratingDistribution',
            'topRatedBooks',
            'topGenres'
        ));
    }
}
