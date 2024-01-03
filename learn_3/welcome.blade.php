@extends('layouts.index')

@section('style')
ul li {
    list-style: none;
    float:left;
    display: table-cell;
}

.item-list {
    width : 20%;
}

.item-title {
    font-size : 14px;
}

.item-img {
    width : 150px;
    height : 150px;
}
@endsection

@section('content')
<div>
    <ul>
        <li class="item-list">
            <p class="item-title">タイトル</p>
            <img src="" class="item-img">
        </li>
    </ul>
</div>
@endsection
