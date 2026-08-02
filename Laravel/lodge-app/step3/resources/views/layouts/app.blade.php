<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Freemason App')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 p-4 text-white">
        <div class="container mx-auto flex justify-between">
            <a href="{{ url('/') }}" class="text-lg font-semibold">Freemason App</a>
            <div>
                <a href="{{ route('lodges.index') }}" class="mr-4">Lodges</a>
                <a href="{{ route('symbols.index') }}" class="mr-4">Symbols</a>
                @guest
                    <a href="{{ route('login') }}" class="mr-4">Login</a>
                    <a href="{{ route('register') }}">Register</a>
                @else
                    <span class="mr-4">{{ Auth::user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-white">Logout</button>
                    </form>
                @endguest
            </div>
        </div>
    </nav>

    <main class="container mx-auto mt-8 px-4">
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
                <button @click="show = false" class="absolute top-0 right-0 px-4 py-3">
                    <span class="sr-only">Close</span>
                    <span class="text-green-700">&times;</span>
                </button>
            </div>
        @endif

        @if(session('info'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('info') }}</span>
                <button @click="show = false" class="absolute top-0 right-0 px-4 py-3">
                    <span class="sr-only">Close</span>
                    <span class="text-blue-700">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
                <button @click="show = false" class="absolute top-0 right-0 px-4 py-3">
                    <span class="sr-only">Close</span>
                    <span class="text-red-700">&times;</span>
                </button>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
