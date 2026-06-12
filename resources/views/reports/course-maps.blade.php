@extends('reports.layout')

@section('title', 'MAPA DE CURSO')
@section('subtitle', 'Mapas de curso registados no sistema')

@section('content')
    @include('reports.partials.curriculum-map-table', ['records' => $records])
@endsection
