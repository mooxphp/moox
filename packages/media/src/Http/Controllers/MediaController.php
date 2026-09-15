<?php

declare(strict_types=1);

namespace Moox\Media\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Moox\Media\Http\Requests\MediaIndexRequest;
use Moox\Media\Http\Requests\MediaStoreRequest;
use Moox\Media\Http\Resources\MediaItemResource;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Support\MediaLocaleResolver;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

class MediaController extends Controller
{
    use AuthorizesRequests;

    public function index(MediaIndexRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Media::class);

        $resolver = app(MediaLocaleResolver::class);
        $effectiveLocale = $request->lang()
            ?? $this->getLangFromReferer($request)
            ?? $resolver->adminDefaultLocale();

        return $resolver->withLocale($effectiveLocale, function () use ($request, $resolver, $effectiveLocale): AnonymousResourceCollection {
            $locales = $resolver->fallbackChain($effectiveLocale);

            $query = Media::query()
                ->with([
                    'translations',
                    'collection.translations',
                ])
                ->latest('id');

            if ($type = $request->type()) {
                if ($type === 'document') {
                    $query->where(function ($q): void {
                        $q->where('mime_type', 'like', 'application/%')
                            ->orWhere('mime_type', 'like', 'text/%')
                            ->orWhere('mime_type', 'like', 'model/%');
                    });
                } else {
                    $query->where('mime_type', 'like', $type.'/%');
                }
            }

            if ($collectionId = $request->collectionId()) {
                $query->where('media_collection_id', $collectionId);
            }

            if ($search = $request->search()) {
                $query->where(function ($q) use ($search, $locales) {
                    $q->where('file_name', 'like', '%'.$search.'%')
                        ->orWhereHas('translations', function ($t) use ($search, $locales) {
                            $t->whereIn('locale', $locales)
                                ->where(function ($tt) use ($search) {
                                    $tt->where('name', 'like', '%'.$search.'%')
                                        ->orWhere('title', 'like', '%'.$search.'%')
                                        ->orWhere('alt', 'like', '%'.$search.'%');
                                });
                        });
                });
            }

            return MediaItemResource::collection(
                $query->paginate($request->perPage())->withQueryString()
            )->additional([
                'context' => [
                    'locale' => app()->getLocale(),
                ],
            ]);
        });
    }

    public function store(MediaStoreRequest $request)
    {
        $this->authorize('create', Media::class);

        $resolver = app(MediaLocaleResolver::class);
        $effectiveLocale = $request->lang()
            ?? $this->getLangFromReferer($request)
            ?? $resolver->adminDefaultLocale();

        return $resolver->withLocale($effectiveLocale, function () use ($request, $resolver, $effectiveLocale) {
            /** @var UploadedFile $file */
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileHash = hash_file('sha256', $file->getRealPath());

            $existingMedia = Media::query()
                ->where('custom_properties->file_hash', $fileHash)
                ->orWhereHas('translations', function ($q) use ($originalName): void {
                    $q->where('name', $originalName);
                })
                ->first();

            if ($existingMedia) {
                return response()->json([
                    'message' => 'Duplicate file.',
                    'existing_id' => $existingMedia->getKey(),
                    'context' => [
                        'locale' => app()->getLocale(),
                    ],
                ], 409);
            }

            $collection = MediaCollection::query()->with('translations')->findOrFail($request->mediaCollectionId());
            $collectionName = $resolver->collectionName($collection, $effectiveLocale);

            $model = new Media;
            $model->exists = true;

            $fileAdder = FileAdderFactory::create($model, $file);
            $fileSize = method_exists($file, 'getSize') ? $file->getSize() : null;
            if (is_int($fileSize) && $fileSize > 0) {
                $fileAdder->setFileSize($fileSize);
            }

            /** @var Media $media */
            $media = $fileAdder
                ->preservingOriginal()
                ->toMediaCollection($collectionName);

            $media->media_collection_id = $collection->getKey();
            $media->collection_name = $collectionName;

            $user = $request->user();
            $media->uploader_type = $user ? $user::class : null;
            $media->uploader_id = $user?->getAuthIdentifier();

            $media->original_model_type = Media::class;
            $media->original_model_id = $media->getKey();
            $media->model_id = $media->getKey();
            $media->model_type = Media::class;

            $media->setCustomProperty('file_hash', $fileHash);
            if (str_starts_with((string) $media->mime_type, 'image/')) {
                try {
                    $path = $media->getPath();
                    if ($path !== '') {
                        $size = @getimagesize($path);
                        if ($size !== false) {
                            $media->setCustomProperty('dimensions', [
                                'width' => (int) $size[0],
                                'height' => (int) $size[1],
                            ]);
                        }
                    }
                } catch (\Throwable) {
                    // ignore
                }
            }

            $media->save();

            $titleFallback = pathinfo($originalName, PATHINFO_FILENAME);

            $translation = $media->translateOrNew($effectiveLocale);
            $translation->setAttribute('name', $request->name() ?? $originalName);
            $translation->setAttribute('title', $request->title() ?? $titleFallback);
            $translation->setAttribute('alt', $request->alt() ?? $titleFallback);
            $translation->save();

            $media->load(['translations', 'collection.translations']);

            return (new MediaItemResource($media))
                ->additional([
                    'message' => 'Uploaded.',
                    'context' => [
                        'locale' => app()->getLocale(),
                    ],
                ])
                ->response()
                ->setStatusCode(201);
        });
    }

    protected function getLangFromReferer(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (! is_string($referer) || trim($referer) === '') {
            return null;
        }

        $query = parse_url($referer, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return null;
        }

        parse_str($query, $params);

        $lang = $params['lang'] ?? null;
        if (! is_string($lang)) {
            return null;
        }

        $lang = trim($lang);
        if ($lang === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9_-]+$/', $lang) !== 1) {
            return null;
        }

        return $lang;
    }
}
