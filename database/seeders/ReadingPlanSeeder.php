<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $yamada = User::query()
            ->where('email', 'yamada@example.com')
            ->first();

        $suzuki = User::query()
            ->where('email', 'suzuki@example.com')
            ->first();

        if ($yamada === null || $suzuki === null) {
            throw new RuntimeException(
                '読書計画登録用の山田太郎・鈴木花子が存在しません。'
            );
        }

        $books = Book::query()
            ->orderBy('id')
            ->limit(6)
            ->get();

        if ($books->count() < 6) {
            throw new RuntimeException(
                '読書計画の登録には6件以上の書籍が必要です。'
            );
        }

        $today = Carbon::today('Asia/Tokyo');

        $plans = [
            [
                'user_id' => $yamada->id,
                'days' => 3,
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'days' => 0,
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'days' => -3,
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'days' => 7,
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'days' => -10,
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => $today->copy()->subDays(5),
            ],
            [
                'user_id' => $suzuki->id,
                'days' => 5,
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
        ];

        foreach ($plans as $index => $data) {
            ReadingPlan::create([
                'user_id' => $data['user_id'],
                'book_id' => $books[$index]->id,
                'target_date' => $today->copy()
                    ->addDays($data['days'])
                    ->toDateString(),
                'status' => $data['status'],
                'completed_at' => $data['completed_at'],
            ]);
        }
    }
}
