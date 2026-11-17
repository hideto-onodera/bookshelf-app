<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ジャンル編集
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-6">
                        ジャンル名を編集
                    </h3>

                    <form action="{{ route('genres.update', $genre) }}"
                        method="POST" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <label for="name"
                                class="block text-sm font-medium text-gray-700 mb-2">
                                ジャンル名
                            </label>

                            <input type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $genre->name) }}"
                                maxlength="255"
                                required
                                class="border-gray-300 rounded-md shadow-sm w-full">

                            @error('name')
                                <p class="text-sm text-red-600 mt-2">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('genres.show', $genre) }}"
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
