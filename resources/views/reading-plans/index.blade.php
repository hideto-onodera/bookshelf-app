<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            読書計画一覧
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="mb-6 rounded bg-green-100 p-4 text-green-800" role="status">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                        <form method="GET" action="{{ route('reading-plans.index') }}"
                              class="flex flex-wrap items-center gap-3">
                            <label for="status" class="font-medium">
                                ステータス
                            </label>

                            <select id="status" name="status"
                                    class="rounded-md border-gray-300">
                                <option value="">すべて</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}"
                                        @selected($selectedStatus === $status->value)>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="submit"
                                    class="rounded bg-gray-800 px-4 py-2 text-white">
                                絞り込み
                            </button>
                        </form>

                        <a href="{{ route('reading-plans.create') }}"
                           class="rounded bg-blue-700 px-4 py-2 text-white">
                            新しい読書計画を登録
                        </a>
                    </div>

                    @forelse ($readingPlans as $readingPlan)
                        <div class="mb-4 rounded-lg border border-gray-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold">
                                        <a href="{{ route('books.show', $readingPlan->book) }}"
                                           class="text-blue-700 hover:underline">
                                            {{ $readingPlan->book->title }}
                                        </a>
                                    </h3>

                                    <p class="mt-2 text-sm text-gray-700">
                                        読了目標日：
                                        {{ $readingPlan->target_date->format('Y年m月d日') }}
                                    </p>

                                    <p class="mt-1 text-sm text-gray-700">
                                        ステータス：
                                        {{ $readingPlan->status->label() }}
                                    </p>

                                    @if ($readingPlan->completed_at)
                                        <p class="mt-1 text-sm text-gray-700">
                                            完了日時：
                                            {{ $readingPlan->completed_at->format('Y年m月d日 H:i') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    @if (in_array($readingPlan->status, [
                                        \App\Enums\ReadingPlanStatus::InProgress,
                                        \App\Enums\ReadingPlanStatus::Expired,
                                    ], true))
                                        <a href="{{ route('reading-plans.edit', $readingPlan) }}"
                                           class="text-blue-700 hover:underline">
                                            編集
                                        </a>
                                    @endif

                                    @if ($readingPlan->status === \App\Enums\ReadingPlanStatus::InProgress)
                                        <form method="POST"
                                              action="{{ route('reading-plans.complete', $readingPlan) }}">
                                            @csrf

                                            <button type="submit"
                                                    class="rounded bg-green-700 px-3 py-2 text-white">
                                                読了
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST"
                                          action="{{ route('reading-plans.destroy', $readingPlan) }}"
                                          onsubmit="return confirm('この読書計画を削除しますか？');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="rounded bg-red-700 px-3 py-2 text-white">
                                            削除
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-gray-600">
                            該当する読書計画はありません。
                        </p>
                    @endforelse

                    <div class="mt-6">
                        {{ $readingPlans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
