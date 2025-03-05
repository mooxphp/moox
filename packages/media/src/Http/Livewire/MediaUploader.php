<?php

namespace Moox\Media\Http\Livewire;

use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
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
        if ($modelId && $modelClass) {
            $modelClass = str_replace('\\\\', '\\', $modelClass);

            if (!class_exists($modelClass)) {
                throw new \Exception("Die Klasse {$modelClass} existiert nicht.");
            }

            $this->model = app($modelClass)::find($modelId);

            if (!$this->model) {
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

        $media->model_id = $this->modelId;
        $media->model_type = $this->modelClass;

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
