<?php

namespace Moox\Media\Http\Livewire;

use Livewire\Component;
use Moox\Media\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Spatie\LivewireFilepond\WithFilePond;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

class MediaUploader extends Component
{
    use WithFilePond;

    public $file;

    public ?Model $model = null;

    public ?int $modelId = null;

    public ?string $modelClass = null;

    public string $collection = 'default';

    public function mount(?int $modelId = null, ?string $modelClass = null)
    {
        if ($modelClass) {
            $modelClass = str_replace('\\\\', '\\', $modelClass);

            if (!class_exists($modelClass)) {
                throw new \Exception("Die Klasse {$modelClass} existiert nicht.");
            }

            if ($modelId) {
                $this->model = app($modelClass)::find($modelId);

                if (!$this->model) {
                    throw new \Exception("Modell mit ID {$modelId} nicht gefunden.");
                }

                $this->modelId = $this->model->getKey();
            } else {
                // Für neue Einträge erstellen wir eine neue Instanz
                $this->model = new $modelClass;
                $this->modelId = 0;
            }

            $this->modelClass = $modelClass;
        } else {
            throw new \Exception('Es wurde keine gültige Modell-Klasse angegeben.');
        }
    }

    public function updatedFile()
    {
        if (!$this->file) {
            return;
        }

        if (!$this->modelId || !$this->modelClass) {
            throw new \Exception('Kein Modell angegeben für den Upload.');
        }

        $fileAdder = app(FileAdderFactory::class)->create($this->model, $this->file);

        $media = $fileAdder->toMediaCollection($this->collection);

        $title = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME);

        $media->original_model_type = $this->modelClass;
        $media->original_model_id = $this->modelId;

        $media->model_id = $media->id;
        $media->model_type = Media::class;

        $media->title = $title;
        $media->description = null;
        $media->internal_note = null;
        $media->alt = $title;

        $media->save();

        $this->reset('file');
        $this->dispatch('mediaUploaded');
    }

    public function render()
    {
        return view('media::livewire.media-uploader');
    }
}
