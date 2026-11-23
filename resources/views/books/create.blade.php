<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('書籍の登録') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 p-4 bg-gray-50 rounded-md">
                        <label for="isbn_search" class="block font-medium text-sm text-gray-700 mb-2">
                            ISBNから書籍情報を検索
                        </label>

                        <div class="flex flex-col sm:flex-row gap-2">
                            <input
                                type="text"
                                id="isbn_search"
                                inputmode="numeric"
                                maxlength="13"
                                placeholder="13桁のISBNを入力"
                                class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"
                            >

                            <button
                                type="button"
                                id="isbn_search_button"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded whitespace-nowrap"
                            >
                                ISBN検索
                            </button>
                        </div>

                        <p id="isbn_search_message" class="text-sm mt-2" role="status" aria-live="polite"></p>
                        <p class="text-xs text-gray-500 mt-2">
                            検索できない場合も、下のフォームから手動で登録できます。
                        </p>
                    </div>

                    <form action="{{ route('books.store') }}" method="POST" novalidate>
                        @include('books._form')

                        <div class="flex items-center justify-end mt-6 pt-6 border-t border-gray-200">
                            <a href="{{ route('books.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
                                キャンセル
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                登録
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isbnInput = document.getElementById('isbn_search');
            const searchButton = document.getElementById('isbn_search_button');
            const message = document.getElementById('isbn_search_message');
            const urlTemplate = @json(route('books.isbn.show', ['isbn' => '__ISBN__']));

            const fields = {
                title: document.getElementById('title'),
                author: document.getElementById('author'),
                isbn: document.getElementById('isbn'),
                published_date: document.getElementById('published_date'),
                description: document.getElementById('description'),
                image_url: document.getElementById('image_url')
            };

            function showMessage(text, isError) {
                message.textContent = text;
                message.className = isError
                    ? 'text-sm mt-2 text-red-600'
                    : 'text-sm mt-2 text-green-700';
            }

            async function searchBook() {
                const isbn = isbnInput.value.trim();

                if (!/^[0-9]{13}$/.test(isbn)) {
                    showMessage('ISBNは13桁の数字で入力してください。', true);
                    return;
                }

                searchButton.disabled = true;
                searchButton.textContent = '検索中...';
                showMessage('書籍情報を検索しています。', false);

                try {
                    const url = urlTemplate.replace('__ISBN__', encodeURIComponent(isbn));

                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        showMessage(
                            data.message || '検索に失敗しました。手動で入力してください。',
                            true
                        );
                        return;
                    }

                    fields.isbn.value = isbn;

                    for (const fieldName of [
                        'title',
                        'author',
                        'published_date',
                        'description',
                        'image_url'
                    ]) {
                        if (typeof data[fieldName] === 'string' && data[fieldName] !== '') {
                            fields[fieldName].value = data[fieldName];
                        }
                    }

                    showMessage(
                        '書籍情報を取得しました。内容を確認し、不足項目を入力してください。',
                        false
                    );
                } catch (error) {
                    showMessage(
                        '通信エラーが発生しました。手動で入力してください。',
                        true
                    );
                } finally {
                    searchButton.disabled = false;
                    searchButton.textContent = 'ISBN検索';
                }
            }

            searchButton.addEventListener('click', searchBook);

            isbnInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchBook();
                }
            });
        });
    </script>
</x-app-layout>
