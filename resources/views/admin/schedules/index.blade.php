@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestion des Horaires</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Ajouter un Horaire
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

                    @if(count($schedules) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Catégorie</th>
                                        <th>Horaires</th>
                                        <th>Note</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($schedules as $schedule)
                                        <tr>
                                            <td>{{ $schedule['category'] }}</td>
                                            <td>
                                                @if(isset($schedule['schedules']) && count($schedule['schedules']) > 0)
                                                    <ul class="mb-0">
                                                        @foreach($schedule['schedules'] as $time)
                                                            <li>
                                                                {{ $time['day'] }} {{ $time['time'] }} - {{ $time['location'] }} ({{ $time['activity'] }})
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-muted">Aucun horaire défini</span>
                                                @endif
                                            </td>
                                            <td>{{ $schedule['note'] ?? 'N/A' }}</td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('admin.schedules.edit', $schedule['id']) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i> Modifier
                                                    </a>
                                                    <form action="{{ route('admin.schedules.destroy', $schedule['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet horaire ?');">
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
                            {{ $schedules->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-info"></i> Information</h5>
                            Aucun horaire n'a été ajouté pour le moment.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
