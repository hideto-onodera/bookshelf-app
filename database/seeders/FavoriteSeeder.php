<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $favorites = [
            'yamada@example.com' => [
                '9784101010014',
                '9784422100524',
                '9784873115658',
                '9784863940246',
            ],
            'suzuki@example.com' => [
                '9784101010021',
                '9784309226712',
                '9784478025819',
                '9784163902302',
            ],
            'tanaka@example.com' => [
                '9784873115658',
                '9784048930598',
                '9784822289607',
                '9784822251468',
            ],
            'sato@example.com' => [
                '9784101010014',
                '9784309226712',
                '9784163902302',
                '9784822289607',
            ],
            'takahashi@example.com' => [
                '9784422100524',
                '9784863940246',
                '9784048930598',
                '9784822251468',
            ],
        ];

        foreach ($favorites as $email => $isbns) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                throw new RuntimeException(
                    'お気に入り登録対象のユーザーが存在しません: '
                    .$email
                );
            }

            $books = Book::whereIn('isbn', $isbns)->get();

            if ($books->count() !== count($isbns)) {
                throw new RuntimeException(
                    'お気に入り登録対象の書籍が不足しています: '
                    .$email
                );
            }

            $user->favoriteBooks()->syncWithoutDetaching(
                $books->pluck('id')->toArray()
            );
        }
    }
}
