@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestion des Programmes</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.programs.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Ajouter un Programme
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <h5><i class="icon fas fa-check"></i> Succès!</h5>
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(count($programs) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Catégorie</th>
                                        <th>Objectifs</th>
                                        <th>Note</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($programs as $program)
                                        <tr>
                                            <td>{{ $program['category'] }}</td>
                                            <td>
                                                @if(isset($program['ludiques_items']) && count($program['ludiques_items']) > 0 ||
                                                    isset($program['psychomoteurs_items']) && count($program['psychomoteurs_items']) > 0 ||
                                                    isset($program['educatifs_items']) && count($program['educatifs_items']) > 0)
                                                    <ul class="mb-0">
                                                        @if(isset($program['ludiques_items']))
                                                            @foreach($program['ludiques_items'] as $item)
                                                                <li>{{ $item }}</li>
                                                            @endforeach
                                                        @endif
                                                        @if(isset($program['psychomoteurs_items']))
                                                            @foreach($program['psychomoteurs_items'] as $item)
                                                                <li>{{ $item }}</li>
                                                            @endforeach
                                                        @endif
                                                        @if(isset($program['educatifs_items']))
                                                            @foreach($program['educatifs_items'] as $item)
                                                                <li>{{ $item }}</li>
                                                            @endforeach
                                                        @endif
                                                    </ul>
                                                @else
                                                    <span class="text-muted">Aucun objectif défini</span>
                                                @endif
                                            </td>
                                            <td>{{ $program['intro'] ?? 'N/A' }}</td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('admin.programs.edit', $program['id']) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i> Modifier
                                                    </a>
                                                    <form action="{{ route('admin.programs.destroy', $program['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce programme ?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
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
                        <div class="mt-3">
                            {{ $programs->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-info"></i> Information</h5>
                            Aucun programme n'a été ajouté pour le moment.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .btn-group form {
        margin-left: 5px;
    }
</style>
@endpush
