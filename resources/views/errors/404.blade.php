<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>404 - ページが見つかりません | {{ config('app.name', 'BookShelf') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <main class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-lg bg-white shadow-sm rounded-lg p-8 text-center">
            <p class="text-5xl font-bold text-gray-700 mb-4">404</p>

            <h1 class="text-2xl font-semibold text-gray-800 mb-4">
                ページが見つかりません
            </h1>

            <p class="text-gray-600 mb-8">
                お探しのページは存在しないか、移動または削除された可能性があります。
            </p>

            <a href="{{ route('books.index') }}"
               class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                書籍一覧へ戻る
            </a>
        </div>
    </main>
</body>
</html>
