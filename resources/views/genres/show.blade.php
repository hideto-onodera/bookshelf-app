<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ジャンル詳細
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold">
                                {{ $genre->name }}
                            </h3>
                            <p class="text-sm text-gray-500 mt-2">
                                登録書籍数：{{ $books->total() }}件
                            </p>
                        </div>

                        <a href="{{ route('genres.index') }}"
                            class="text-blue-600 hover:underline">
                            ジャンル一覧に戻る
                        </a>
                    </div>

                    <div class="flex gap-4 mb-8">
                        <a href="{{ route('genres.edit', $genre) }}"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            編集する
                        </a>

                        <form action="{{ route('genres.destroy', $genre) }}"
                            method="POST"
                            onsubmit="return confirm('このジャンルを削除しますか？')">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                削除する
                            </button>
                        </form>
                    </div>

                    <h4 class="text-lg font-semibold mb-4">
                        このジャンルの書籍
                    </h4>

                    @forelse ($books as $book)
                        <div class="border-b border-gray-200 py-4">
                            <a href="{{ route('books.show', $book) }}"
                                class="text-blue-600 hover:underline font-semibold">
                                {{ $book->title }}
                            </a>

                            <p class="text-sm text-gray-600 mt-1">
                                著者：{{ $book->author }}
                            </p>
                        </div>
                    @empty
                        <p class="text-gray-500">
                            このジャンルに登録されている書籍はありません。
                        </p>
                    @endforelse

                    <div class="mt-6">
                        {{ $books->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
