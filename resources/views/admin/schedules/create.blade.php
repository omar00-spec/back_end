@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ajouter un Horaire</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.schedules.index') }}" class="btn btn-default">
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

                    <form action="{{ route('admin.schedules.store') }}" method="POST">
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
                            <label>Horaires d'entraînement</label>
                            <div id="schedules-container">
                                @if(old('schedules'))
                                    @foreach(old('schedules') as $key => $schedule)
                                        <div class="card mb-3 schedule-card">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0">Séance #{{ $key + 1 }}</h5>
                                                <button type="button" class="btn btn-sm btn-danger remove-schedule"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Jour</label>
                                                            <input type="text" name="schedules[{{ $key }}][day]" class="form-control @error('schedules.'.$key.'.day') is-invalid @enderror" placeholder="Exemple: Lundi" value="{{ $schedule['day'] ?? '' }}" required>
                                                            @error('schedules.'.$key.'.day')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Horaire</label>
                                                            <input type="text" name="schedules[{{ $key }}][time]" class="form-control @error('schedules.'.$key.'.time') is-invalid @enderror" placeholder="Exemple: 18:00 - 19:30" value="{{ $schedule['time'] ?? '' }}" required>
                                                            @error('schedules.'.$key.'.time')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Lieu</label>
                                                            <input type="text" name="schedules[{{ $key }}][location]" class="form-control @error('schedules.'.$key.'.location') is-invalid @enderror" placeholder="Exemple: Terrain principal" value="{{ $schedule['location'] ?? '' }}" required>
                                                            @error('schedules.'.$key.'.location')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Type d'activité</label>
                                                            <input type="text" name="schedules[{{ $key }}][activity]" class="form-control @error('schedules.'.$key.'.activity') is-invalid @enderror" placeholder="Exemple: Entraînement technique" value="{{ $schedule['activity'] ?? '' }}" required>
                                                            @error('schedules.'.$key.'.activity')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="card mb-3 schedule-card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">Séance #1</h5>
                                            <button type="button" class="btn btn-sm btn-danger remove-schedule" disabled><i class="fas fa-times"></i></button>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Jour</label>
                                                        <input type="text" name="schedules[0][day]" class="form-control" placeholder="Exemple: Lundi" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Horaire</label>
                                                        <input type="text" name="schedules[0][time]" class="form-control" placeholder="Exemple: 18:00 - 19:30" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Lieu</label>
                                                        <input type="text" name="schedules[0][location]" class="form-control" placeholder="Exemple: Terrain principal" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Type d'activité</label>
                                                        <input type="text" name="schedules[0][activity]" class="form-control" placeholder="Exemple: Entraînement technique" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-info mt-2" id="add-schedule">
                                <i class="fas fa-plus"></i> Ajouter une séance
                            </button>
                        </div>

                        <div class="form-group">
                            <label for="note">Note <small class="text-muted">(Optionnel)</small></label>
                            <textarea name="note" id="note" rows="3" class="form-control @error('note') is-invalid @enderror" placeholder="Notes supplémentaires (optionnel)">{{ old('note') }}</textarea>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
        // Variable pour compter les séances
        let scheduleCount = $('.schedule-card').length;

        // Ajouter une séance
        $('#add-schedule').click(function() {
            scheduleCount++;

            let newCard = `
                <div class="card mb-3 schedule-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Séance #${scheduleCount}</h5>
                        <button type="button" class="btn btn-sm btn-danger remove-schedule"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jour</label>
                                    <input type="text" name="schedules[${scheduleCount-1}][day]" class="form-control" placeholder="Exemple: Lundi" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Horaire</label>
                                    <input type="text" name="schedules[${scheduleCount-1}][time]" class="form-control" placeholder="Exemple: 18:00 - 19:30" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Lieu</label>
                                    <input type="text" name="schedules[${scheduleCount-1}][location]" class="form-control" placeholder="Exemple: Terrain principal" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Type d'activité</label>
                                    <input type="text" name="schedules[${scheduleCount-1}][activity]" class="form-control" placeholder="Exemple: Entraînement technique" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#schedules-container').append(newCard);
            toggleRemoveButtons();
        });

        // Supprimer une séance
        $(document).on('click', '.remove-schedule', function() {
            $(this).closest('.schedule-card').remove();
            renumberSchedules();
            toggleRemoveButtons();
        });

        // Renuméroter les séances après suppression
        function renumberSchedules() {
            $('.schedule-card').each(function(index) {
                $(this).find('h5').text(`Séance #${index+1}`);

                // Mettre à jour les noms des champs
                $(this).find('input[name^="schedules["]').each(function() {
                    let fieldName = $(this).attr('name');
                    fieldName = fieldName.replace(/schedules\[\d+\]/, `schedules[${index}]`);
                    $(this).attr('name', fieldName);
                });
            });

            scheduleCount = $('.schedule-card').length;
        }

        // Activer/désactiver les boutons de suppression
        function toggleRemoveButtons() {
            if ($('.schedule-card').length === 1) {
                $('.remove-schedule').prop('disabled', true);
            } else {
                $('.remove-schedule').prop('disabled', false);
            }
        }

        // Initialisation
        toggleRemoveButtons();
    });
</script>
@endpush
@endsection
