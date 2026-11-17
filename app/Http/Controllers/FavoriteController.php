<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Display the authenticated user's favorite books.
     */
    public function index(Request $request): View
    {
        $books = $request->user()
            ->favoriteBooks()
            ->orderByPivot('created_at', 'desc')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * Toggle the authenticated user's favorite book.
     */
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $user = $request->user();

        $isFavorite = $user->favoriteBooks()
            ->whereKey($book->id)
            ->exists();

        if ($isFavorite) {
            $user->favoriteBooks()->detach($book->id);

            $message = 'お気に入りから削除しました。';
        } else {
            $user->favoriteBooks()->syncWithoutDetaching([
                $book->id,
            ]);

            $message = 'お気に入りに登録しました。';
        }

        return redirect()
            ->route('books.show', $book)
            ->with('success', $message);
    }
}
