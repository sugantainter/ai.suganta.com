@extends('layouts.public')

@section('content')
<div class="public-share-banner">
    <div class="public-share-banner__inner">
        <p><strong>Shared conversation</strong> — public view-only snapshot on Kaalo AI by SuGanta.</p>
        <a href="{{ url('/') }}" class="public-btn public-btn--primary" style="padding: 8px 16px; font-size: 13px;">Open Kaalo AI</a>
    </div>
</div>
<div class="public-app-mount">
    <div id="app"></div>
</div>
@endsection
