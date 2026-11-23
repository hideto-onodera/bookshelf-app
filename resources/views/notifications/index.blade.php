<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('通知一覧') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-green-800" role="status">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($notifications->isEmpty())
                        <p class="text-gray-600">
                            通知はありません。
                        </p>
                    @else
                        <div class="space-y-4">
                            @foreach ($notifications as $notification)
                                @php
                                    $data = $notification->data;
                                @endphp

                                <div class="rounded-lg border p-4 {{ $notification->read_at === null ? 'border-blue-300 bg-blue-50' : 'border-gray-200 bg-white' }}">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if ($notification->read_at === null)
                                                    <span class="rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white">
                                                        未読
                                                    </span>
                                                @else
                                                    <span class="rounded bg-gray-200 px-2 py-1 text-xs text-gray-700">
                                                        既読
                                                    </span>
                                                @endif

                                                <span class="text-sm text-gray-500">
                                                    {{ $notification->created_at->format('Y/m/d H:i') }}
                                                </span>
                                            </div>

                                            <p class="font-semibold">
                                                {{ $data['book_title'] ?? '書籍情報なし' }}
                                            </p>

                                            <p>
                                                {{ $data['message'] ?? '読書計画をご確認ください。' }}
                                            </p>

                                            @if (!empty($data['target_date']))
                                                <p class="text-sm text-gray-600">
                                                    読書目標日：
                                                    {{ $data['target_date'] }}
                                                </p>
                                            @endif

                                            @if (!empty($data['reading_plan_id']))
                                                <a href="{{ route('reading-plans.index') }}"
                                                    class="inline-block text-sm text-indigo-600 hover:text-indigo-800 underline">
                                                    読書計画一覧を見る
                                                </a>
                                            @endif
                                        </div>

                                        @if ($notification->read_at === null)
                                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                                    既読にする
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
