<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            レビュー編集
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-6">
                        {{ $review->book->title }} のレビューを編集
                    </h3>

                    <form action="{{ route('reviews.update', $review) }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">
                                評価
                            </label>

                            <select name="rating" id="rating"
                                class="border-gray-300 rounded-md shadow-sm w-full">
                                <option value="">選択してください</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}"
                                        {{ (string) old('rating', $review->rating) === (string) $i ? 'selected' : '' }}>
                                        {{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }} ({{ $i }})
                                    </option>
                                @endfor
                            </select>

                            @error('rating')
                                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
                                コメント
                            </label>

                            <textarea name="comment" id="comment" rows="5"
                                class="border-gray-300 rounded-md shadow-sm w-full"
                                placeholder="この書籍の感想を書いてください">{{ old('comment', $review->comment) }}</textarea>

                            @error('comment')
                                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('books.show', $review->book_id) }}"
                                class="text-gray-600 hover:text-gray-900">
                                キャンセル
                            </a>

                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                更新する
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
