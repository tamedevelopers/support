
<!-- tests/layout/partials/app.blade.php -->
@extends('layout.partials.app')

@section('title', 'Homepage')

@section('content')
    <h1>Welcome to the Homepage!</h1>
    <p>This is the @\section('content') of the homepage.</p>
    
    @include('layout.partials.footer', ['year2' => $header['year']])
@endsection