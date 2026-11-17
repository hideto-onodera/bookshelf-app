<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookModelTest extends TestCase
{
    public function test_book_belongs_to_user(): void
    {
        $book = new Book;

        $relation = $book->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_book_belongs_to_many_genres(): void
    {
        $book = new Book;

        $relation = $book->genres();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertInstanceOf(Genre::class, $relation->getRelated());
        $this->assertSame('book_genre', $relation->getTable());
    }

    public function test_book_has_many_reviews(): void
    {
        $book = new Book;

        $relation = $book->reviews();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Review::class, $relation->getRelated());
        $this->assertSame('book_id', $relation->getForeignKeyName());
    }

    public function test_book_belongs_to_many_favoriting_users(): void
    {
        $book = new Book;

        $relation = $book->favoritedByUsers();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('favorites', $relation->getTable());
    }

    public function test_published_date_is_cast_to_date(): void
    {
        $book = new Book;

        $book->published_date = '2026-11-15';

        $this->assertInstanceOf(Carbon::class, $book->published_date);
        $this->assertSame(
            '2026-11-15',
            $book->published_date->format('Y-m-d')
        );
    }

    public function test_book_has_expected_fillable_attributes(): void
    {
        $book = new Book;

        $this->assertSame(
            [
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
                'user_id',
            ],
            $book->getFillable()
        );
    }
}
