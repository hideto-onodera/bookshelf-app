<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();

        $book->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validated();

        $review->update($validated);

        return redirect()
            ->route('books.show', $review->book_id)
            ->with('success', 'レビューを更新しました。');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $bookId = $review->book_id;

        $review->delete();

        return redirect()
            ->route('books.show', $bookId)
            ->with('success', 'レビューを削除しました。');
    }

    public function like(Request $request, Review $review): RedirectResponse
    {
        $user = $request->user();

        $isLiked = $user->likedReviews()
            ->whereKey($review->id)
            ->exists();

        if ($isLiked) {
            $user->likedReviews()->detach($review->id);

            $message = 'いいねを解除しました。';
        } else {
            $user->likedReviews()->syncWithoutDetaching([
                $review->id,
            ]);

            $message = 'いいねしました。';
        }

        return redirect()
            ->route('books.show', $review->book_id)
            ->with('success', $message);
    }
}
