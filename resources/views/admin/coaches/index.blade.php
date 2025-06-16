@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gestion des Éducateurs</h1>
        <a href="{{ route('admin.coaches.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Ajouter un éducateur
        </a>
    </div>

    @if(count($coaches) > 0)
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="80">Photo</th>
                                <th>Nom</th>
                                <th>Rôle</th>
                                <th>Catégorie</th>
                                <th>Description</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coaches as $coach)
                                <tr>
                                    <td>
                                        @if($coach->image)
                                            <img src="{{ asset('storage/' . $coach->image) }}" alt="{{ $coach->name }}" class="img-thumbnail" width="60">
                                        @else
                                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $coach->name }}</td>
                                    <td>{{ $coach->role }}</td>
                                    <td>
                                        @if($coach->category)
                                            <span class="badge bg-primary">{{ strtoupper($coach->category) }}</span>
                                        @else
                                            <span class="badge bg-secondary">Toutes</span>
                                        @endif
                                    </td>
                                    <td>{{ Str::limit($coach->description, 100) }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.coaches.edit', $coach) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Éditer
                                            </a>
                                            <form action="{{ route('admin.coaches.destroy', $coach) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet éducateur?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Aucun éducateur n'a été ajouté. <a href="{{ route('admin.coaches.create') }}">Ajouter un éducateur</a>
        </div>
    @endif
</div>
@endsection
