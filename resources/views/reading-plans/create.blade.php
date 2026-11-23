<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            読書計画の登録
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('reading-plans.store') }}">
                        @csrf

                        <div class="mb-6">
                            <label for="book_id"
                                   class="block mb-2 font-medium text-gray-700">
                                書籍
                                <span class="text-red-600">*</span>
                            </label>

                            <select id="book_id"
                                    name="book_id"
                                    required
                                    class="w-full rounded-md border-gray-300">
                                <option value="">書籍を選択してください</option>

                                @foreach ($books as $book)
                                    <option value="{{ $book->id }}"
                                        @selected((string) old('book_id') === (string) $book->id)>
                                        {{ $book->title }}（{{ $book->author }}）
                                    </option>
                                @endforeach
                            </select>

                            @error('book_id')
                                <p class="mt-2 text-sm text-red-600" role="alert">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="target_date"
                                   class="block mb-2 font-medium text-gray-700">
                                読了目標日
                                <span class="text-red-600">*</span>
                            </label>

                            <input type="date"
                                   id="target_date"
                                   name="target_date"
                                   value="{{ old('target_date') }}"
                                   required
                                   class="w-full rounded-md border-gray-300">

                            @error('target_date')
                                <p class="mt-2 text-sm text-red-600" role="alert">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap items-center gap-4">
                            <button type="submit"
                                    class="rounded bg-blue-700 px-5 py-2 text-white">
                                読書計画を登録
                            </button>

                            <a href="{{ route('reading-plans.index') }}"
                               class="text-gray-700 hover:underline">
                                一覧へ戻る
                            </a>
                        </div>
                    </form>

                    @if ($books->isEmpty())
                        <p class="mt-6 text-sm text-gray-600">
                            登録できる書籍がありません。
                            先に書籍を登録してください。
                        </p>

                        <a href="{{ route('books.create') }}"
                           class="mt-2 inline-block text-blue-700 hover:underline">
                            書籍登録画面へ
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
