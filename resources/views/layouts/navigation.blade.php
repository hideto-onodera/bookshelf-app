<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('books.index') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('books.index')" :active="request()->routeIs('books.index')">
                        {{ __('書籍一覧') }}
                    </x-nav-link>

                    @if (Route::has('ranking.index'))
                        <x-nav-link :href="route('ranking.index')" :active="request()->routeIs('ranking.index')">
                            {{ __('ランキング') }}
                        </x-nav-link>
                    @endif

                    @auth
                        @if (Route::has('books.create'))
                            <x-nav-link :href="route('books.create')" :active="request()->routeIs('books.create')">
                                {{ __('書籍登録') }}
                            </x-nav-link>
                        @endif

                        @if (Route::has('favorites.index'))
                            <x-nav-link :href="route('favorites.index')" :active="request()->routeIs('favorites.index')">
                                {{ __('お気に入り') }}
                            </x-nav-link>
                        @endif

                        @if (Route::has('reading-plans.index'))
                            <x-nav-link :href="route('reading-plans.index')" :active="request()->routeIs('reading-plans.*')">
                                {{ __('読書計画') }}
                            </x-nav-link>
                        @endif

                        @if (Route::has('reading-reports.index'))
                            <x-nav-link :href="route('reading-reports.index')" :active="request()->routeIs('reading-reports.*')">
                                {{ __('マイ読書レポート') }}
                            </x-nav-link>
                        @endif

                        @if (Route::has('notifications.index'))
                            <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                                <svg class="mr-1 inline-block h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
                                </svg>
                                {{ __('通知一覧') }}
                            </x-nav-link>
                        @endif

                        @if (Route::has('genres.index'))
                            <x-nav-link :href="route('genres.index')" :active="request()->routeIs('genres.*')">
                                {{ __('ジャンル管理') }}
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm text-gray-500">
                                <div>{{ Auth::user()->name }}</div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if (Route::has('logout'))
                                <form method="POST" action="{{ route('logout') }}" novalidate>
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('ログアウト') }}
                                    </x-dropdown-link>
                                </form>
                            @endif
                        </x-slot>
                    </x-dropdown>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="text-sm text-gray-700 underline hover:text-gray-900">
                            ログイン
                        </a>
                    @endif

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 underline hover:text-gray-900">
                            新規登録
                        </a>
                    @endif
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }"
                            class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18L18 6M6 6l12 12" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }"
                            class="hidden" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('books.index')" :active="request()->routeIs('books.index')">
                {{ __('書籍一覧') }}
            </x-responsive-nav-link>

            @if (Route::has('ranking.index'))
                <x-responsive-nav-link :href="route('ranking.index')" :active="request()->routeIs('ranking.index')">
                    {{ __('ランキング') }}
                </x-responsive-nav-link>
            @endif

            @auth
                @if (Route::has('books.create'))
                    <x-responsive-nav-link :href="route('books.create')" :active="request()->routeIs('books.create')">
                        {{ __('書籍登録') }}
                    </x-responsive-nav-link>
                @endif

                @if (Route::has('favorites.index'))
                    <x-responsive-nav-link :href="route('favorites.index')" :active="request()->routeIs('favorites.index')">
                        {{ __('お気に入り') }}
                    </x-responsive-nav-link>
                @endif

                @if (Route::has('reading-plans.index'))
                    <x-responsive-nav-link :href="route('reading-plans.index')" :active="request()->routeIs('reading-plans.*')">
                        {{ __('読書計画') }}
                    </x-responsive-nav-link>
                @endif

                @if (Route::has('reading-reports.index'))
                    <x-responsive-nav-link :href="route('reading-reports.index')" :active="request()->routeIs('reading-reports.*')">
                        {{ __('マイ読書レポート') }}
                    </x-responsive-nav-link>
                @endif

                @if (Route::has('notifications.index'))
                    <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                        <svg class="mr-1 inline-block h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
                        </svg>
                        {{ __('通知一覧') }}
                    </x-responsive-nav-link>
                @endif

                @if (Route::has('genres.index'))
                    <x-responsive-nav-link :href="route('genres.index')" :active="request()->routeIs('genres.*')">
                        {{ __('ジャンル管理') }}
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            @auth
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                @if (Route::has('logout'))
                    <div class="mt-3 space-y-1">
                        <form method="POST" action="{{ route('logout') }}" novalidate>
                            @csrf
                            <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('ログアウト') }}
                            </x-responsive-nav-link>
                        </form>
                    </div>
                @endif
            @else
                <div class="mt-3 space-y-1">
                    @if (Route::has('login'))
                        <x-responsive-nav-link :href="route('login')">
                            {{ __('ログイン') }}
                        </x-responsive-nav-link>
                    @endif

                    @if (Route::has('register'))
                        <x-responsive-nav-link :href="route('register')">
                            {{ __('新規登録') }}
                        </x-responsive-nav-link>
                    @endif
                </div>
            @endauth
        </div>
    </div>
</nav>
