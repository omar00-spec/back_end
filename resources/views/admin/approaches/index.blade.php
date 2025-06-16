@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gestion des Approches Pédagogiques</h1>
        <a href="{{ route('admin.approaches.create') }}" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Ajouter une approche
        </a>
    </div>

    @if(count($approaches) > 0)
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="approaches-table">
                        <thead>
                            <tr>
                                <th width="70">Ordre</th>
                                <th>Description</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="sortable">
                            @foreach($approaches as $approach)
                                <tr data-id="{{ $approach->id }}">
                                    <td>
                                        <span class="btn btn-sm btn-light drag-handle">
                                            <i class="fas fa-grip-vertical"></i> {{ $approach->order }}
                                        </span>
                                    </td>
                                    <td>{{ $approach->description }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.approaches.edit', $approach) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Éditer
                                            </a>
                                            <form action="{{ route('admin.approaches.destroy', $approach) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette approche?');">
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
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i> Vous pouvez réorganiser les approches en glissant-déposant les lignes.
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Aucune approche pédagogique n'a été ajoutée. <a href="{{ route('admin.approaches.create') }}">Ajouter une approche</a>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<script>
    $(function() {
        $('.sortable').sortable({
            handle: '.drag-handle',
            update: function(event, ui) {
                let ids = [];
                $('.sortable tr').each(function() {
                    ids.push($(this).data('id'));
                });

                $.ajax({
                    url: '{{ route("admin.approaches.reorder") }}',
                    method: 'POST',
                    data: {
                        ids: ids,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reload page to update the order numbers
                            window.location.reload();
                        }
                    }
                });
            }
        });
    });
</script>
@endsection
