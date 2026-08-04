@extends('layouts.app')

@section('title', $lodge->name)

@section('content')
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold mb-4">{{ $lodge->name }}</h1>
        
        @can('update', $lodge)
        <div>
            <a href="{{ route('lodges.edit', $lodge) }}" class="bg-yellow-500 text-white px-4 py-2 rounded mr-2">編集</a>
            
            <form action="{{ route('lodges.destroy', $lodge) }}" method="POST" class="inline" onsubmit="return confirm('本当にこのロッジを削除しますか？');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded">削除</button>
            </form>
        </div>
        @endcan
    </div>
    
    <div class="bg-white p-4 rounded shadow mb-6">
        <p><strong>場所:</strong> {{ $lodge->location }}</p>
        <p><strong>設立年:</strong> {{ $lodge->founded_year }}</p>
    </div>

    <h2 class="text-2xl font-semibold mt-6 mb-2">メンバー</h2>
    @auth
        <a href="{{ route('members.create', $lodge) }}" class="bg-green-500 text-white px-4 py-2 rounded">新しいメンバーを追加</a>
    @endauth
    <ul class="mt-2 bg-white p-4 rounded shadow">
        @forelse($lodge->members as $member)
            <li class="mb-3 pb-2 border-b last:border-b-0 flex justify-between items-center">
                <div>
                    <span class="font-medium">{{ $member->name }}</span> 
                    <span class="text-gray-600">({{ $member->rank }})</span> 
                    <div class="text-sm text-gray-500">入会日: {{ $member->initiation_date->format('Y-m-d') }}</div>
                </div>
                
                <div class="flex">
                    @auth
                        <form action="{{ route('members.promote', [$lodge, $member]) }}" method="POST" class="inline mr-2">
                            @csrf
                            <button type="submit" class="bg-yellow-500 text-white px-2 py-1 rounded text-sm">昇進</button>
                        </form>
                        
                        <a href="{{ route('members.edit', [$lodge, $member]) }}" class="bg-blue-500 text-white px-2 py-1 rounded text-sm mr-2">編集</a>
                        
                        <form action="{{ route('members.destroy', [$lodge, $member]) }}" method="POST" class="inline" onsubmit="return confirm('本当にこのメンバーを削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded text-sm">削除</button>
                        </form>
                    @endauth
                </div>
            </li>
        @empty
            <li class="text-gray-500">メンバーはまだ登録されていません。</li>
        @endforelse
    </ul>

    <h2 class="text-2xl font-semibold mt-6 mb-2">今後のイベント</h2>
    @auth
        <a href="{{ route('events.create', $lodge) }}" class="bg-purple-500 text-white px-4 py-2 rounded">新しいイベントを作成</a>
    @endauth
    <ul class="mt-2 bg-white p-4 rounded shadow">
        @forelse($lodge->upcomingEvents as $event)
            <li class="mb-3 pb-2 border-b last:border-b-0 flex justify-between items-center">
                <div>
                    <span class="font-medium">{{ $event->title }}</span>
                    <span class="ml-2 px-2 py-1 bg-gray-200 text-xs rounded">{{ $event->type }}</span>
                    <div class="text-sm text-gray-500">{{ $event->date->format('Y-m-d') }}</div>
                    <p class="mt-1 text-gray-600">{{ Str::limit($event->description, 100) }}</p>
                </div>
                
                <div class="flex">
                    @auth
                        <a href="{{ route('events.edit', [$lodge, $event]) }}" class="bg-blue-500 text-white px-2 py-1 rounded text-sm mr-2">編集</a>
                        
                        <form action="{{ route('events.destroy', [$lodge, $event]) }}" method="POST" class="inline" onsubmit="return confirm('本当にこのイベントを削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded text-sm">削除</button>
                        </form>
                    @endauth
                </div>
            </li>
        @empty
            <li class="text-gray-500">今後のイベントはありません。</li>
        @endforelse
    </ul>
@endsection
