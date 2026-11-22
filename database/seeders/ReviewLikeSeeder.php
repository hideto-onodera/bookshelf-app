<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emails = [
            'yamada@example.com',
            'suzuki@example.com',
            'tanaka@example.com',
            'sato@example.com',
            'takahashi@example.com',
        ];

        $users = User::whereIn('email', $emails)
            ->get()
            ->keyBy('email');

        if ($users->count() !== 5) {
            throw new RuntimeException(
                'いいね登録に必要なユーザー5人が揃っていません。'
            );
        }

        $reviews = Review::whereHas('user', function ($query) use ($emails) {
            $query->whereIn('email', $emails);
        })
            ->whereHas('book', function ($query) {
                $query->whereIn('isbn', [
                    '9784101010014',
                    '9784422100524',
                    '9784873115658',
                    '9784863940246',
                    '9784101010021',
                    '9784309226712',
                    '9784048930598',
                    '9784478025819',
                    '9784163902302',
                    '9784822289607',
                    '9784822251468',
                ]);
            })
            ->orderBy('id')
            ->get();

        if ($reviews->count() !== 32) {
            throw new RuntimeException(
                '対象レビューが32件ではありません。'
            );
        }

        foreach ($reviews as $index => $review) {
            // 各レビューに0〜3人のいいねを割り当てる
            $likeCount = $index % 4;

            // レビュー投稿者本人を除外する
            $eligibleUsers = [];

            foreach ($emails as $email) {
                $user = $users->get($email);

                if ($user->id !== $review->user_id) {
                    $eligibleUsers[] = $user->id;
                }
            }

            // 対象ユーザーを順番にずらして選択する
            $userIds = [];
            $eligibleCount = count($eligibleUsers);

            for ($offset = 0; $offset < $likeCount; $offset++) {
                $userIndex = ($index + $offset) % $eligibleCount;
                $userIds[] = $eligibleUsers[$userIndex];
            }

            $review->likedByUsers()->syncWithoutDetaching($userIds);
        }
    }
}
