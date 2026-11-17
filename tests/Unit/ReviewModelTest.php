<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class ReviewModelTest extends TestCase
{
    public function test_review_belongs_to_book(): void
    {
        $review = new Review;

        $relation = $review->book();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Book::class, $relation->getRelated());
        $this->assertSame('book_id', $relation->getForeignKeyName());
    }

    public function test_review_belongs_to_user(): void
    {
        $review = new Review;

        $relation = $review->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_review_belongs_to_many_liking_users(): void
    {
        $review = new Review;

        $relation = $review->likedByUsers();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('review_likes', $relation->getTable());
    }

    public function test_rating_is_cast_to_integer(): void
    {
        $review = new Review;

        $review->rating = '5';

        $this->assertSame(5, $review->rating);
    }

    public function test_review_has_expected_fillable_attributes(): void
    {
        $review = new Review;

        $this->assertSame(
            [
                'user_id',
                'book_id',
                'rating',
                'comment',
            ],
            $review->getFillable()
        );
    }
}
