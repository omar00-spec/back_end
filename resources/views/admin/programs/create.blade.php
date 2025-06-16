@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ajouter un Programme</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.programs.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Erreur!</h5>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('admin.programs.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="category">Catégorie</label>
                            <select name="category" id="category" class="form-control @error('category') is-invalid @enderror" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <option value="U5" {{ old('category') == 'U5' ? 'selected' : '' }}>U5</option>
                                <option value="U7" {{ old('category') == 'U7' ? 'selected' : '' }}>U7</option>
                                <option value="U9" {{ old('category') == 'U9' ? 'selected' : '' }}>U9</option>
                                <option value="U11" {{ old('category') == 'U11' ? 'selected' : '' }}>U11</option>
                                <option value="U13" {{ old('category') == 'U13' ? 'selected' : '' }}>U13</option>
                                <option value="U15" {{ old('category') == 'U15' ? 'selected' : '' }}>U15</option>
                                <option value="U17" {{ old('category') == 'U17' ? 'selected' : '' }}>U17</option>
                                <option value="U19" {{ old('category') == 'U19' ? 'selected' : '' }}>U19</option>
                                <option value="U21" {{ old('category') == 'U21' ? 'selected' : '' }}>U21</option>
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="intro">Introduction</label>
                            <textarea name="intro" id="intro" rows="3" class="form-control @error('intro') is-invalid @enderror" required>{{ old('intro') }}</textarea>
                            @error('intro')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Objectifs ludiques</label>
                            <div id="ludiques-container">
                                @if(old('ludiques_items'))
                                    @foreach(old('ludiques_items') as $key => $item)
                                        <div class="input-group mb-2 ludique-input">
                                            <input type="text" name="ludiques_items[]" class="form-control @error('ludiques_items.'.$key) is-invalid @enderror" placeholder="Objectif ludique" value="{{ $item }}" required>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-danger remove-ludique"><i class="fas fa-times"></i></button>
                                            </div>
                                            @error('ludiques_items.'.$key)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach
                                @else
                                    <div class="input-group mb-2 ludique-input">
                                        <input type="text" name="ludiques_items[]" class="form-control" placeholder="Objectif ludique" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger remove-ludique" disabled><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-info btn-sm mt-2" id="add-ludique">
                                <i class="fas fa-plus"></i> Ajouter un objectif ludique
                            </button>
                        </div>

                        <div class="form-group">
                            <label>Objectifs psychomoteurs</label>
                            <div id="psychomoteurs-container">
                                @if(old('psychomoteurs_items'))
                                    @foreach(old('psychomoteurs_items') as $key => $item)
                                        <div class="input-group mb-2 psychomoteur-input">
                                            <input type="text" name="psychomoteurs_items[]" class="form-control @error('psychomoteurs_items.'.$key) is-invalid @enderror" placeholder="Objectif psychomoteur" value="{{ $item }}" required>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-danger remove-psychomoteur"><i class="fas fa-times"></i></button>
                                            </div>
                                            @error('psychomoteurs_items.'.$key)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach
                                @else
                                    <div class="input-group mb-2 psychomoteur-input">
                                        <input type="text" name="psychomoteurs_items[]" class="form-control" placeholder="Objectif psychomoteur" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger remove-psychomoteur" disabled><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-info btn-sm mt-2" id="add-psychomoteur">
                                <i class="fas fa-plus"></i> Ajouter un objectif psychomoteur
                            </button>
                        </div>

                        <div class="form-group">
                            <label>Objectifs socio-éducatifs</label>
                            <div id="educatifs-container">
                                @if(old('educatifs_items'))
                                    @foreach(old('educatifs_items') as $key => $item)
                                        <div class="input-group mb-2 educatif-input">
                                            <input type="text" name="educatifs_items[]" class="form-control @error('educatifs_items.'.$key) is-invalid @enderror" placeholder="Objectif socio-éducatif" value="{{ $item }}" required>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-danger remove-educatif"><i class="fas fa-times"></i></button>
                                            </div>
                                            @error('educatifs_items.'.$key)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach
                                @else
                                    <div class="input-group mb-2 educatif-input">
                                        <input type="text" name="educatifs_items[]" class="form-control" placeholder="Objectif socio-éducatif" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger remove-educatif" disabled><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-info btn-sm mt-2" id="add-educatif">
                                <i class="fas fa-plus"></i> Ajouter un objectif socio-éducatif
                            </button>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Fonctions pour les objectifs ludiques
        $('#add-ludique').click(function() {
            let newInput = `
                <div class="input-group mb-2 ludique-input">
                    <input type="text" name="ludiques_items[]" class="form-control" placeholder="Objectif ludique" required>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-danger remove-ludique"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            `;
            $('#ludiques-container').append(newInput);
            toggleRemoveButtons('ludique');
        });

        $(document).on('click', '.remove-ludique', function() {
            $(this).closest('.ludique-input').remove();
            toggleRemoveButtons('ludique');
        });

        // Fonctions pour les objectifs psychomoteurs
        $('#add-psychomoteur').click(function() {
            let newInput = `
                <div class="input-group mb-2 psychomoteur-input">
                    <input type="text" name="psychomoteurs_items[]" class="form-control" placeholder="Objectif psychomoteur" required>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-danger remove-psychomoteur"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            `;
            $('#psychomoteurs-container').append(newInput);
            toggleRemoveButtons('psychomoteur');
        });

        $(document).on('click', '.remove-psychomoteur', function() {
            $(this).closest('.psychomoteur-input').remove();
            toggleRemoveButtons('psychomoteur');
        });

        // Fonctions pour les objectifs éducatifs
        $('#add-educatif').click(function() {
            let newInput = `
                <div class="input-group mb-2 educatif-input">
                    <input type="text" name="educatifs_items[]" class="form-control" placeholder="Objectif socio-éducatif" required>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-danger remove-educatif"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            `;
            $('#educatifs-container').append(newInput);
            toggleRemoveButtons('educatif');
        });

        $(document).on('click', '.remove-educatif', function() {
            $(this).closest('.educatif-input').remove();
            toggleRemoveButtons('educatif');
        });

        // Fonction pour activer/désactiver les boutons de suppression
        function toggleRemoveButtons(type) {
            let count = $(`.${type}-input`).length;
            if (count === 1) {
                $(`.remove-${type}`).prop('disabled', true);
            } else {
                $(`.remove-${type}`).prop('disabled', false);
            }
        }

        // Initialisation
        toggleRemoveButtons('ludique');
        toggleRemoveButtons('psychomoteur');
        toggleRemoveButtons('educatif');
    });
</script>
@endpush
@endsection
