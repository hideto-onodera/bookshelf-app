<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * ログインユーザーの読書計画を状態で絞り込み、一覧表示する。
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ReadingPlan::class);

        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in(array_column(ReadingPlanStatus::cases(), 'value')),
            ],
        ]);

        $query = $request->user()
            ->readingPlans()
            ->with('book');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $readingPlans = $query
            ->orderBy('target_date')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('reading-plans.index', [
            'readingPlans' => $readingPlans,
            'selectedStatus' => $validated['status'] ?? null,
            'statuses' => ReadingPlanStatus::cases(),
        ]);
    }

    /**
     * 読書計画の新規登録画面を表示する。
     */
    public function create(): View
    {
        $this->authorize('create', ReadingPlan::class);

        $books = Book::query()
            ->orderBy('title')
            ->orderBy('id')
            ->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 検証済みの入力値から進行中の読書計画を登録する。
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            ReadingPlan::create([
                'user_id' => $request->user()->id,
                'book_id' => $validated['book_id'],
                'target_date' => $validated['target_date'],
                'status' => ReadingPlanStatus::InProgress,
            ]);
        });

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    /**
     * 更新権限を確認し、読書計画の編集画面を表示する。
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画の期限を更新する。
     * 期限切れ計画の再開時は、同じ書籍の進行中計画との重複を防ぐ。
     */
    public function update(
        UpdateReadingPlanRequest $request,
        ReadingPlan $readingPlan
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($readingPlan, $validated): void {
            if ($readingPlan->status === ReadingPlanStatus::Expired) {
                $alreadyExists = ReadingPlan::query()
                    ->where('user_id', $readingPlan->user_id)
                    ->where('book_id', $readingPlan->book_id)
                    ->where('status', ReadingPlanStatus::InProgress->value)
                    ->whereKeyNot($readingPlan->id)
                    ->exists();

                if ($alreadyExists) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'target_date' => 'この書籍には進行中の読書計画が既に存在します。',
                    ]);
                }

                $readingPlan->status = ReadingPlanStatus::InProgress;
                $readingPlan->completed_at = null;
            }

            $readingPlan->target_date = $validated['target_date'];
            $readingPlan->save();
        });

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を完了状態にし、完了日時を記録する。
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('complete', $readingPlan);

        DB::transaction(function () use ($readingPlan): void {
            $readingPlan->update([
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]);
        });

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を完了しました。');
    }

    /**
     * 読書計画と関連する期限通知をトランザクション内で削除する。
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        DB::transaction(function () use ($readingPlan): void {
            DB::table('notifications')
                ->where('type', \App\Notifications\ReadingPlanDeadlineNotification::class)
                ->where('notifiable_type', \App\Models\User::class)
                ->where('notifiable_id', $readingPlan->user_id)
                ->where('data->reading_plan_id', $readingPlan->id)
                ->delete();

            $readingPlan->delete();
        });

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }
}
