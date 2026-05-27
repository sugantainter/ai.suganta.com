@php
    $viteEntries = ['resources/css/seo.css', 'resources/js/seo.js'];
    if (request()->routeIs('kaalo.home')) {
        $viteEntries[] = 'resources/css/kaalo-home.css';
        $mainFullWidth = $mainFullWidth ?? true;
    } else {
        $viteEntries[] = 'resources/css/seo-page.css';
    }
@endphp

@extends('layouts.public')
