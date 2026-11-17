<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_user_has_many_books(): void
    {
        $user = new User();

        $relation = $user->books();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Book::class, $relation->getRelated());
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_user_has_many_reviews(): void
    {
        $user = new User();

        $relation = $user->reviews();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Review::class, $relation->getRelated());
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_user_belongs_to_many_favorite_books(): void
    {
        $user = new User();

        $relation = $user->favoriteBooks();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertInstanceOf(Book::class, $relation->getRelated());
        $this->assertSame('favorites', $relation->getTable());
    }

    public function test_user_belongs_to_many_liked_reviews(): void
    {
        $user = new User();

        $relation = $user->likedReviews();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertInstanceOf(Review::class, $relation->getRelated());
        $this->assertSame('review_likes', $relation->getTable());
    }

    public function test_user_has_expected_fillable_attributes(): void
    {
        $user = new User();

        $this->assertSame(
            [
                'name',
                'email',
                'password',
            ],
            $user->getFillable()
        );
    }
}
