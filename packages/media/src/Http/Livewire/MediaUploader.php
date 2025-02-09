<?php

namespace Moox\Media\Http\Livewire;

use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Spatie\LivewireFilepond\WithFilePond;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

class MediaUploader extends Component
{
    use WithFilePond;

    public $file; // FilePond hochgeladene Datei

    public ?Model $model = null; // Das zugehörige Modell

    public ?int $modelId = null; // ID des Modells

    public ?string $modelClass = null; // Typ des Modells

    public string $collection = 'default'; // Name der Medien-Sammlung

    public function mount(?int $modelId = null, ?string $modelClass = null)
    {
        // Initialisierung: Falls ein `modelId` und `modelClass` angegeben sind, das zugehörige Modell laden
        if ($modelId && $modelClass) {
            $modelClass = str_replace('\\\\', '\\', $modelClass);

            if (! class_exists($modelClass)) {
                throw new \Exception("Die Klasse {$modelClass} existiert nicht.");
            }

            $this->model = app($modelClass)::find($modelId);

            if (! $this->model) {
                throw new \Exception("Modell mit ID {$modelId} nicht gefunden.");
            }

            $this->modelId = $this->model->getKey();
            $this->modelClass = get_class($this->model);
        } else {
            throw new \Exception('Es wurde kein gültiges Modell angegeben.');
        }
    }

    public function updatedFile()
    {
        // Keine Datei hochgeladen, Abbruch
        if (! $this->file) {
            return;
        }

        // Prüfen, ob ein Modell vorhanden ist
        if (! $this->modelId || ! $this->modelClass) {
            throw new \Exception('Kein Modell angegeben für den Upload.');
        }

        // Datei zur spezifischen Model-Sammlung hinzufügen
        /** @var \Spatie\MediaLibrary\MediaCollections\Models\Media $media */
        $fileAdder = app(FileAdderFactory::class)->create($this->model, $this->file);

        $media = $fileAdder->toMediaCollection($this->collection);

        // Medien mit `original_model_type` und `original_model_id` initialisieren
        $media->original_model_type = $this->modelClass; // Modell-Klasse
        $media->original_model_id = $this->modelId; // Modell-ID

        // Felder `model_id` und `model_type` explizit auf `null` setzen
        $media->model_id = null;
        $media->model_type = null;

        // Speichern der geänderten Werte
        $media->save();

        // Datei zurücksetzen und Event auslösen
        $this->reset('file');
        $this->dispatch('mediaUploaded');
    }

    public function render()
    {
        // Rendert die MediaUploader-Komponente
        return view('media::livewire.media-uploader');
    }
}
