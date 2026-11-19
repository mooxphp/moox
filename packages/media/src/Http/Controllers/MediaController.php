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
use Moox\Media\Support\MediaUploadOptions;
use Moox\Media\Support\MediaUploadService;

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

    public function show(Request $request, Media $media): MediaItemResource
    {
        $this->authorize('view', $media);

        $resolver = app(MediaLocaleResolver::class);

        $lang = $request->query('lang');
        $lang = is_string($lang) && preg_match('/^[A-Za-z0-9_-]+$/', trim($lang)) === 1
            ? trim($lang)
            : null;

        $effectiveLocale = $lang
            ?? $this->getLangFromReferer($request)
            ?? $resolver->adminDefaultLocale();

        return $resolver->withLocale($effectiveLocale, function () use ($media): MediaItemResource {
            $media->load(['translations', 'collection.translations']);

            return (new MediaItemResource($media))->additional([
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

            $collection = MediaCollection::query()->with('translations')->findOrFail($request->mediaCollectionId());
            $collectionName = $resolver->collectionName($collection, $effectiveLocale);
            $titleFallback = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $apiAcceptedTypes = config('media.upload.api.accepted_file_types');

            $result = app(MediaUploadService::class)->upload($file, new MediaUploadOptions(
                mediaCollectionId: (int) $collection->getKey(),
                collectionName: $collectionName,
                locale: $effectiveLocale,
                name: $request->name(),
                title: $request->title() ?? $titleFallback,
                alt: $request->alt() ?? $titleFallback,
                acceptedFileTypes: is_array($apiAcceptedTypes) ? $apiAcceptedTypes : null,
                uploader: $request->user(),
            ));

            if ($result->isDuplicate()) {
                $payload = [
                    'message' => 'Duplicate file.',
                    'context' => [
                        'locale' => app()->getLocale(),
                    ],
                ];

                // Only reveal existing_id when the caller may view that record.
                $existing = $result->existingMedia;
                if ($existing instanceof Media && $request->user()?->can('view', $existing)) {
                    $payload['existing_id'] = $existing->getKey();
                }

                return response()->json($payload, 409);
            }

            $media = $result->media;

            if (! $media instanceof Media) {
                abort(500, 'Upload succeeded without a media record.');
            }

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
