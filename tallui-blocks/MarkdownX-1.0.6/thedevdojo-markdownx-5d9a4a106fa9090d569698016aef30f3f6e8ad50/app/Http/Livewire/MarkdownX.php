<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use GrahamCampbell\Markdown\Facades\Markdown as MarkdownConverter;

class MarkdownX extends Component
{

    public $content;
    public $contentPreview;
    public $name;
    public $key;
    public $style;
    public $section = 'write';

    /**
     * Laravel livewire listeners, learn more at https://laravel-livewire.com/docs/events#event-listeners
     *
     * 'markdown-x-image-upload' => uploads image files from the editor
     */
    protected $listeners = [
        'markdown-x-image-upload' => 'upload',
        'markdown-x-giphy-load' => 'getGiphyTrendingImages',
        'markdown-x-giphy-search' => 'getGiphySearchImages'
    ];

    /**
     * Mount the MarkdownX component, you can pass the current content, the name for the textarea field,
     * and a generic Key. This key can be specified so that way you can include multiple MarkdownX
     * editors in a single page.
     *
     * @param string $content
     * @param string $name
     * @param string $key
     * @return void
     */
    public function mount($content = '', $name = '', $key = ''){
        $this->content = $content;
        $this->name = $name;
        $this->key = $key;
        $this->updateContentPreview();
    }

    /**
     * Anytime the editor is blurred, this function will be triggered and it will updated the $content,
     * this will also emit an event to the parent component with the updated content.
     *
     * @param array $data
     */
    public function update($data){
        $content = $data['content'];
        $this->content = $content;
        $this->emitUp('markdown-x:update', $this->content);
    }

    /**
     * When the content changes this function will fire an event with the updatedPmark
     * content is being emitted to the parent component.
     *
     */
    public function updatedContent(){
        $this->emitUp('markdown-x:update', $this->content);
    }

    /**
     * This function will update the Content Preview when user clicks on the Preview tab in the
     * Markdown editor toolbar.
     *
     * Note: This function can be overwritten with your customer Markdown parser. This is the
     * only function that will/can be changed when you upgrade to the latest version of the
     * MarkdownX editor.
     *
     */
    public function updateContentPreview(){
        $this->contentPreview = Str::markdown($this->content);
    }

    /**
     * This function is called from the markdown-x view when the image file input has changed
     * The following data is sent with the payload:
     * {
     *      image: reader.result,
     *      name: file.name,
     *      key: key,
     *      text: "![" + file.name + "](Uploading...)"
     * }
     *
     * @param array $payload
     * @return void
     */
    public function upload($payload){

        $payload = (object)$payload;

        $path = 'images/' . strtolower(date('FY')) . '/';
        $fullPath = '';

        try{

            $original_filename = pathinfo($payload->name, PATHINFO_FILENAME);
            $filename = $original_filename;
            $extension = explode('/', mime_content_type($payload->image))[1];
            $filename_counter = 1;

            // Make sure the filename does not exist, if it does make sure to add a number to the end 1, 2, 3, etc...
            while (Storage::disk(config('markdownx.storage.disk'))->exists($path.$filename.'.'.$extension)) {
                $filename = Str::slug($original_filename).(string) ($filename_counter++);
            }

            $fullPath = $path.$filename.'.'.$extension;

            // Get the Base64 string to store
            @list($type, $file_data) = explode(';', $payload->image);
            @list(, $file_data) = explode(',', $file_data);
            $type = explode('/', $type)[1];

            if(!in_array($type, config('markdownx.image.allowed_file_types'))){
                $this->dispatchBrowserEvent('markdown-x-image-uploaded', [
                    'status' => 400,
                    'message' => 'File type not supported. Must be of type ' . implode(', ', config('markdownx.image.allowed_file_types')),
                    'key' => $payload->key,
                    'text' => $payload->text
                ]);
                return;
            }

            Storage::disk(config('markdownx.storage.disk'))->put($fullPath, base64_decode($file_data), 'public');

            $this->dispatchBrowserEvent('markdown-x-image-uploaded', [
                'status' => 200,
                'message' => 'Successfully uploaded image.',
                'path' => str_replace(' ', '%20', Storage::url($fullPath)),
                'key' => $payload->key,
                'text' => $payload->text,
                'name' => $payload->name
            ]);

        } catch(Exception $e){
            $this->dispatchBrowserEvent('markdown-x-image-uploaded', [
                'status' => 400,
                'message' => 'Error when trying to upload.',
                'key' => $payload->key,
                'text' => $payload->text
            ]);
        }

    }

    public function getGiphyImages($payload){
        $api_key = config('markdownx.integrations.giphy.api_key');

        $response = Http::get('https://api.giphy.com/v1/gifs/trending', [
                        'api_key' => $api_key,
                        'limit' => 30,
                        'rating' => 'pg'
                    ]);

        if($response->ok()){
            $this->sendResultsToView($response);
        }
    }

    public function getGiphyTrendingImages($payload){
        $api_key = config('markdownx.integrations.giphy.api_key');

        $response = Http::get('https://api.giphy.com/v1/gifs/trending', [
                        'api_key' => $api_key,
                        'limit' => 30,
                        'rating' => 'pg'
                    ]);

        if($response->ok()){
            $this->sendResultsToView($response, $payload['key']);
        }
    }

    public function getGiphySearchImages($payload){
        $api_key = config('markdownx.integrations.giphy.api_key');

        $response = Http::get('api.giphy.com/v1/gifs/search', [
                        'api_key' => $api_key,
                        'q' => $payload['search'],
                        'limit' => 30,
                        'rating' => 'pg'
                    ]);


        if($response->ok()){
            $this->sendResultsToView($response, $payload['key']);
        }
    }

    public function sendResultsToView($response, $key){
        $parse_giphy_results = [];
        foreach($response->json()['data'] as $result){
            array_push($parse_giphy_results, [
                'image' => $result['images']['fixed_height_small']['url'],
                'embed' => $result['embed_url'] ]
            );
        }

        $this->dispatchBrowserEvent('markdown-x-giphy-results', [
            'status' => 200,
            'message' => 'Successfully returned results.',
            'results' => $parse_giphy_results,
            'key' => $key,
        ]);
    }

    /**
     * Render the markdown-x view. Hazah!
     *
     */
    public function render()
    {
        return view('livewire.markdown-x');
    }
}
