<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\File;
use Moox\FrontendAuth\Http\Middleware\FrontendAuthMiddleware;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $root = storage_path('framework/testing/clickdummy');
    File::deleteDirectory($root);
    File::ensureDirectoryExists($root);

    config()->set('click-dummy.enabled', true);
    config()->set('click-dummy.route_prefix', 'clickdummy');
    config()->set('click-dummy.path', $root);
    config()->set('moox-frontend-auth.enabled', true);
});

function clickDummyUser(): User
{
    $user = new User([
        'id' => 1,
        'name' => 'Dev',
        'email' => 'dev@heco.de',
    ]);
    $user->exists = true;

    return $user;
}

function assertBinaryFileContains(\Illuminate\Testing\TestResponse $response, string $needle): void
{
    $response->assertOk();

    $base = $response->baseResponse;
    expect($base)->toBeInstanceOf(BinaryFileResponse::class);

    /** @var BinaryFileResponse $base */
    expect(File::get($base->getFile()->getPathname()))->toContain($needle);
}

it('redirects guests to the filament admin login', function (): void {
    File::put(config('click-dummy.path').DIRECTORY_SEPARATOR.'index.html', '<h1>Clickdummy</h1>');

    $this->get('/clickdummy')
        ->assertRedirect(route('filament.admin.auth.login'));
});

it('serves html for authenticated users and injects a base href', function (): void {
    File::put(
        config('click-dummy.path').DIRECTORY_SEPARATOR.'index.html',
        '<html><head><title>t</title></head><body><h1>Clickdummy works</h1></body></html>',
    );

    $this->actingAs(clickDummyUser())
        ->get('/clickdummy')
        ->assertOk()
        ->assertSee('Clickdummy works', false)
        ->assertSee('<base href="/clickdummy/">', false);
});

it('serves sibling assets under the same tree', function (): void {
    File::put(config('click-dummy.path').DIRECTORY_SEPARATOR.'style.css', 'body{color:red}');

    assertBinaryFileContains(
        $this->actingAs(clickDummyUser())->get('/clickdummy/style.css'),
        'body{color:red}',
    );
});

it('resolves nested directories to index.html with nested base href', function (): void {
    $folder = config('click-dummy.path').DIRECTORY_SEPARATOR.'demo';
    File::ensureDirectoryExists($folder);
    File::put(
        $folder.DIRECTORY_SEPARATOR.'index.html',
        '<html><head></head><body><p>Nested</p></body></html>',
    );

    $this->actingAs(clickDummyUser())
        ->get('/clickdummy/demo')
        ->assertOk()
        ->assertSee('Nested', false)
        ->assertSee('<base href="/clickdummy/demo/">', false);
});

it('returns 404 when index.html is missing for a directory', function (): void {
    File::ensureDirectoryExists(config('click-dummy.path').DIRECTORY_SEPARATOR.'empty');

    $this->actingAs(clickDummyUser())
        ->get('/clickdummy/empty')
        ->assertNotFound();
});

it('returns 404 for missing files', function (): void {
    $this->actingAs(clickDummyUser())
        ->get('/clickdummy/missing.html')
        ->assertNotFound();
});

it('returns 404 for path traversal attempts', function (): void {
    $this->withoutMiddleware(FrontendAuthMiddleware::class);

    $this->get('/clickdummy/foo/../../secrets.txt')
        ->assertNotFound();
});

it('returns 404 for disallowed extensions', function (): void {
    File::put(config('click-dummy.path').DIRECTORY_SEPARATOR.'secret.env', 'APP_KEY=test');

    $this->actingAs(clickDummyUser())
        ->get('/clickdummy/secret.env')
        ->assertNotFound();
});

it('uses frontend-auth middleware in the default config', function (): void {
    $config = require dirname(__DIR__, 2).'/config/click-dummy.php';

    expect($config['middleware'])->toContain('moox.frontend-auth');
});
