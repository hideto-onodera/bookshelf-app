<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            マイ読書レポート
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-medium text-gray-600">レビュー総数</h3>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ $totalReviews }}<span class="ml-1 text-base font-normal">件</span>
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-medium text-gray-600">レビューした書籍数</h3>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ $reviewedBooksCount }}<span class="ml-1 text-base font-normal">冊</span>
                        </p>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-medium text-gray-600">平均評価</h3>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ $averageRating === null ? '―' : number_format((float) $averageRating, 2) }}
                            <span class="text-base font-normal text-gray-600">/ 5</span>
                        </p>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-5 text-lg font-semibold text-gray-900">評価分布</h3>

                    @if ($totalReviews === 0)
                        <p class="text-gray-500">まだレビューがありません。</p>
                    @else
                        <div class="space-y-4">
                            @for ($rating = 5; $rating >= 1; $rating--)
                                @php
                                    $count = $ratingDistribution[$rating];
                                    $percentage = $totalReviews > 0
                                        ? ($count / $totalReviews) * 100
                                        : 0;
                                @endphp

                                <div class="flex items-center gap-4">
                                    <span class="w-12 shrink-0 text-sm font-medium text-gray-700">
                                        ★{{ $rating }}
                                    </span>
                                    <div class="h-4 flex-1 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full bg-yellow-400"
                                             style="width: {{ number_format($percentage, 2, '.', '') }}%">
                                        </div>
                                    </div>
                                    <span class="w-14 shrink-0 text-right text-sm text-gray-700">
                                        {{ $count }}件
                                    </span>
                                </div>
                            @endfor
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-5 text-lg font-semibold text-gray-900">
                            高評価書籍 TOP5
                        </h3>
                        <p class="mb-4 text-sm text-gray-500">
                            自分が評価4以上を付けた書籍
                        </p>

                        @forelse ($topRatedBooks as $index => $book)
                            <div class="flex items-center justify-between gap-4 border-b border-gray-100 py-3 last:border-b-0">
                                <div class="min-w-0">
                                    <span class="mr-2 text-sm font-bold text-gray-500">
                                        {{ $index + 1 }}位
                                    </span>
                                    <a href="{{ route('books.show', $book->id) }}"
                                       class="font-medium text-blue-700 hover:underline">
                                        {{ $book->title }}
                                    </a>
                                    <p class="mt-1 text-xs text-gray-500">
                                        対象レビュー {{ $book->review_count }}件
                                    </p>
                                </div>
                                <span class="shrink-0 font-semibold text-yellow-600">
                                    ★ {{ number_format((float) $book->average_rating, 2) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-gray-500">評価4以上の書籍はありません。</p>
                        @endforelse
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-5 text-lg font-semibold text-gray-900">
                            ジャンル別平均評価 TOP5
                        </h3>

                        @forelse ($topGenres as $index => $genre)
                            <div class="flex items-center justify-between gap-4 border-b border-gray-100 py-3 last:border-b-0">
                                <div class="min-w-0">
                                    <span class="mr-2 text-sm font-bold text-gray-500">
                                        {{ $index + 1 }}位
                                    </span>
                                    <span class="font-medium text-gray-900">
                                        {{ $genre->name }}
                                    </span>
                                    <p class="mt-1 text-xs text-gray-500">
                                        レビュー {{ $genre->review_count }}件
                                    </p>
                                </div>
                                <span class="shrink-0 font-semibold text-yellow-600">
                                    ★ {{ number_format((float) $genre->average_rating, 2) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-gray-500">集計できるジャンルはありません。</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
