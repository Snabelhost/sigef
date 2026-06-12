@extends('reports.layout')

@section('title', 'PLANO DE CURSO')
@section('subtitle', 'Planos de curso e suas fases')

@section('content')
    @include('reports.partials.curriculum-map-table', ['records' => $records])
@endsection
