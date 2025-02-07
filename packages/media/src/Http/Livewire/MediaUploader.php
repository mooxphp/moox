<?php

namespace Moox\Media\Http\Livewire;

use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Spatie\LivewireFilepond\WithFilePond;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

class MediaUploader extends Component
{
    use WithFilepond;

    public $file;

    public ?Model $model = null;

    public ?int $modelId = null;

    public ?string $modelClass = null;

    public string $collection = 'default';

    public function mount(?int $modelId = null, ?string $modelClass = null)
    {
        if ($modelId && $modelClass) {
            $modelClass = str_replace('\\\\', '\\', $modelClass);
            if (! class_exists($modelClass)) {
                throw new \Exception("Die Klasse {$modelClass} existiert nicht.");
            }
            $this->model = app($modelClass)::find($modelId);
        }
    }

    public function updatedFile()
    {
        if (! $this->file) {
            return;
        }

        if (! $this->model) {
            throw new \Exception('Kein Model angegeben für den Upload.');
        }

        $fileAdder = app(FileAdderFactory::class)->create($this->model, $this->file);

        $media = $fileAdder->toMediaCollection($this->collection);

        $media->original_model_type = get_class($this->model);
        $media->original_model_id = $this->model->id;
        $media->save();

        $this->reset('file');
        $this->dispatch('mediaUploaded');
    }

    public function render()
    {
        return view('media::livewire.media-uploader');
    }
}
