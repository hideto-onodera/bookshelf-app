<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class GenreModelTest extends TestCase
{
    public function test_genre_belongs_to_many_books(): void
    {
        $genre = new Genre;

        $relation = $genre->books();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertInstanceOf(Book::class, $relation->getRelated());
        $this->assertSame('book_genre', $relation->getTable());
    }

    public function test_genre_has_expected_fillable_attributes(): void
    {
        $genre = new Genre;

        $this->assertSame(
            ['name'],
            $genre->getFillable()
        );
    }

    public function test_genre_uses_expected_table(): void
    {
        $genre = new Genre;

        $this->assertSame('genres', $genre->getTable());
    }
}
