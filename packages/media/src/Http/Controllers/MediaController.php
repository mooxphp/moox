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
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

class MediaController extends Controller
{
    use AuthorizesRequests;

    public function index(MediaIndexRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Media::class);

        $effectiveLocale = $request->lang()
            ?? $this->getLangFromReferer($request)
            ?? app()->getLocale();

        if ($request->lang() || $this->getLangFromReferer($request)) {
            app()->setLocale($effectiveLocale);
        }

        $locales = $this->getLocaleFallbackChain($effectiveLocale, (string) config('app.fallback_locale'));

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
    }

    public function store(MediaStoreRequest $request)
    {
        $this->authorize('create', Media::class);

        $effectiveLocale = $request->lang()
            ?? $this->getLangFromReferer($request)
            ?? app()->getLocale();

        if ($request->lang() || $this->getLangFromReferer($request)) {
            app()->setLocale($effectiveLocale);
        }

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
        $collectionName = $this->resolveCollectionName($collection, $effectiveLocale);

        $model = new Media;
        $model->exists = true;

        /** @var Media $media */
        $media = app(FileAdderFactory::class)
            ->create($model, $file)
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
        $lang = $effectiveLocale;

        $translation = $media->translateOrNew($lang);
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

        // allow only common locale chars to avoid weird injections
        if (preg_match('/^[A-Za-z0-9_-]+$/', $lang) !== 1) {
            return null;
        }

        return $lang;
    }

    protected function resolveCollectionName(MediaCollection $collection, string $effectiveLocale): string
    {
        $locales = $this->getLocaleFallbackChain($effectiveLocale, (string) config('app.fallback_locale'));

        foreach ($locales as $locale) {
            $translation = $collection->translate($locale, false);
            $name = is_object($translation) ? $translation->getAttribute('name') : null;
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        if ($collection->translations->isNotEmpty()) {
            $first = $collection->translations->first();
            $name = $first->getAttribute('name');
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        return (string) $collection->getKey();
    }

    /**
     * @return array<int, string>
     */
    protected function getLocaleFallbackChain(string $locale, string $fallbackLocale): array
    {
        $locales = array_filter([
            $locale,
            $fallbackLocale,
            'en_US',
        ], static fn (string $value): bool => trim($value) !== '');

        $expanded = [];
        foreach ($locales as $locale) {
            $expanded[] = $locale;

            $expanded[] = str_replace('-', '_', $locale);
            $expanded[] = str_replace('_', '-', $locale);

            $base = preg_split('/[-_]/', $locale)[0] ?? null;
            if (is_string($base) && $base !== '') {
                $expanded[] = $base;
            }
        }

        return array_values(array_unique(array_filter($expanded, static fn (string $value): bool => trim($value) !== '')));
    }
}
