@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-4">Tableau de bord</h1>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Bienvenue dans votre espace d'administration</h5>
                    <p class="card-text">
                        Depuis ce tableau de bord, vous pouvez gérer les éducateurs, les programmes et les plannings d'entraînement de votre académie de football.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-primary h-100">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-users me-2"></i>Éducateurs
                </div>
                <div class="card-body">
                    <h5 class="card-title">Nombre d'éducateurs: {{ $coachesCount }}</h5>
                    <p class="card-text">Gérez les profils des éducateurs de votre académie.</p>
                    <a href="{{ route('admin.coaches.index') }}" class="btn btn-primary">
                        <i class="fas fa-list me-1"></i> Voir tous les éducateurs
                    </a>
                    <a href="{{ route('admin.coaches.create') }}" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-1"></i> Ajouter un éducateur
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-book me-2"></i>Programmes & Objectifs
                </div>
                <div class="card-body">
                    <p class="card-text">Gérez les programmes et objectifs des catégories.</p>
                    <a href="{{ route('admin.programs.index') }}" class="btn btn-success">
                        <i class="fas fa-list me-1"></i> Voir tous les programmes
                    </a>
                    <a href="{{ route('admin.programs.create') }}" class="btn btn-outline-success">
                        <i class="fas fa-plus me-1"></i> Ajouter un programme
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-info h-100">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-calendar-alt me-2"></i>Plannings d'Entraînement
                </div>
                <div class="card-body">
                    <p class="card-text">Gérez les plannings d'entraînement des catégories.</p>
                    <a href="{{ route('admin.schedules.index') }}" class="btn btn-info">
                        <i class="fas fa-list me-1"></i> Voir tous les plannings
                    </a>
                    <a href="{{ route('admin.schedules.create') }}" class="btn btn-outline-info">
                        <i class="fas fa-plus me-1"></i> Ajouter un planning
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
