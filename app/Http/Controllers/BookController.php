<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre' => [
                'nullable',
                'integer',
                Rule::exists('genres', 'id'),
            ],
            'sort' => [
                'nullable',
                Rule::in(['latest', 'oldest', 'title', 'rating']),
            ],
        ]);

        $keyword = $validated['keyword'] ?? null;
        $genreId = $validated['genre'] ?? null;
        $sort = $validated['sort'] ?? 'latest';

        $query = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating');

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->where('title', 'like', '%'.$keyword.'%')
                    ->orWhere('author', 'like', '%'.$keyword.'%');
            });
        }

        if ($genreId !== null) {
            $query->whereHas('genres', function ($query) use ($genreId) {
                $query->where('genres.id', $genreId);
            });
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('books.created_at')
                    ->orderBy('books.id');
                break;

            case 'title':
                $query->orderBy('books.title')
                    ->orderBy('books.id');
                break;

            case 'rating':
                $query->orderByRaw(
                    'CASE WHEN reviews_avg_rating IS NULL THEN 1 ELSE 0 END'
                )
                    ->orderByDesc('reviews_avg_rating')
                    ->orderBy('books.id');
                break;

            case 'latest':
            default:
                $query->orderByDesc('books.created_at')
                    ->orderByDesc('books.id');
                break;
        }

        $books = $query->paginate(10)->withQueryString();

        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return view('books.index', compact(
            'books',
            'genres',
            'keyword',
            'genreId',
            'sort'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $validated['user_id'] = $request->user()->id;

        $book = DB::transaction(function () use ($validated, $genreIds) {
            $book = Book::create($validated);

            $book->genres()->attach($genreIds);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): View
    {
        $book->load([
            'user',
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $book->load('genres');

        $genres = Genre::query()
            ->orderBy('name')
            ->get();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();

        $genreIds = $validated['genres'];
        unset($validated['genres']);

        DB::transaction(function () use ($book, $validated, $genreIds) {
            $book->update($validated);

            $book->genres()->sync($genreIds);
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
