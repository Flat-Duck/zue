@extends('layouts.app', ['page' => 'navigation-builder'])

@section('title', __('nav.navigation_builder'))

@section('content')
    <livewire:navigation-builder />
@endsection
