<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            読書計画の編集
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST"
                          action="{{ route('reading-plans.update', $readingPlan) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <p class="mb-2 font-medium text-gray-700">
                                書籍
                            </p>

                            <p class="rounded-md bg-gray-100 p-3">
                                {{ $readingPlan->book->title }}
                            </p>
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
                                   value="{{ old('target_date', $readingPlan->target_date->format('Y-m-d')) }}"
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
                                変更を保存
                            </button>

                            <a href="{{ route('reading-plans.index') }}"
                               class="text-gray-700 hover:underline">
                                一覧へ戻る
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
