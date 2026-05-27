<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\KositValidator\Models\KositValidation;
use Moox\KositValidator\Tests\Support\TestEnvironment;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('guests are redirected from the KOSIT HTML report route', function (): void {
    $validation = KositValidation::query()->create([
        'passed' => true,
        'report_html_path' => '/nonexistent/dir/report.html',
        'validated_at' => now(),
    ]);

    $this->get(route('kosit-validator.report.html', $validation))
        ->assertRedirect();
});

test('authenticated users receive 404 when the KOSIT HTML file is missing', function (): void {
    $user = TestEnvironment::makeTestUser();

    $validation = KositValidation::query()->create([
        'passed' => true,
        'report_html_path' => '/nonexistent/dir/foo-report.html',
        'validated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('kosit-validator.report.html', $validation))
        ->assertNotFound();
});

test('authenticated users can view the KOSIT HTML report with security headers', function (): void {
    $user = TestEnvironment::makeTestUser();
    $dir = sys_get_temp_dir().'/kosit-validator-route-test-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $xmlPath = $dir.'/inv-report.xml';
    $htmlPath = $dir.'/inv-report.html';
    file_put_contents($xmlPath, '<report/>');
    file_put_contents($htmlPath, '<html><body>Report OK</body></html>');

    $validation = KositValidation::query()->create([
        'passed' => true,
        'report_xml_path' => $xmlPath,
        'report_html_path' => $htmlPath,
        'validated_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('kosit-validator.report.html', $validation));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/html; charset=UTF-8');
    $response->assertHeader('x-content-type-options', 'nosniff');
    $response->assertHeader('x-frame-options', 'SAMEORIGIN');
    expect($response->headers->get('Content-Security-Policy'))->toContain('default-src');

    @unlink($htmlPath);
    @unlink($xmlPath);
    @rmdir($dir);
});

test('downloadInputFile returns the file at input_path', function (): void {
    $user = TestEnvironment::makeTestUser();
    $tmpFile = tempnam(sys_get_temp_dir(), 'kosit-test-');
    $xmlPath = $tmpFile.'.xml';
    rename($tmpFile, $xmlPath);
    file_put_contents($xmlPath, '<?xml version="1.0"?><test/>');

    $validation = KositValidation::query()->create([
        'input_path' => $xmlPath,
        'report_xml_path' => '/nonexistent/report.xml',
        'report_html_path' => '/nonexistent/report.html',
        'passed' => true,
        'errors' => [],
        'validated_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('kosit-validator.download.input-file', $validation));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/xml');
    expect($response->headers->get('content-disposition'))->toContain(basename($xmlPath));

    @unlink($xmlPath);
});

test('downloadInputFile 404s when the file is missing', function (): void {
    $user = TestEnvironment::makeTestUser();

    $validation = KositValidation::query()->create([
        'input_path' => '/nonexistent/missing-input.xml',
        'passed' => true,
        'validated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('kosit-validator.download.input-file', $validation))
        ->assertNotFound();
});

test('downloadReportHtml returns the file at report_html_path', function (): void {
    $user = TestEnvironment::makeTestUser();
    $dir = sys_get_temp_dir().'/kosit-validator-html-dl-test-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $htmlPath = $dir.'/inv-report.html';
    file_put_contents($htmlPath, '<html><body>Report OK</body></html>');

    $validation = KositValidation::query()->create([
        'input_path' => $dir.'/inv.xml',
        'report_html_path' => $htmlPath,
        'passed' => true,
        'errors' => [],
        'validated_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('kosit-validator.download.report-html', $validation));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/html; charset=UTF-8');
    expect($response->headers->get('content-disposition'))->toContain('inv-report.html');

    @unlink($htmlPath);
    @rmdir($dir);
});

test('downloadReportHtml 404s when the file is missing', function (): void {
    $user = TestEnvironment::makeTestUser();

    $validation = KositValidation::query()->create([
        'input_path' => '/tmp/inv.xml',
        'report_html_path' => '/nonexistent/missing-report.html',
        'passed' => true,
        'validated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('kosit-validator.download.report-html', $validation))
        ->assertNotFound();
});

test('downloadReportXml returns the file at report_xml_path', function (): void {
    $user = TestEnvironment::makeTestUser();
    $dir = sys_get_temp_dir().'/kosit-validator-dl-test-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $xmlPath = $dir.'/inv-report.xml';
    file_put_contents($xmlPath, '<report/>');

    $validation = KositValidation::query()->create([
        'input_path' => $dir.'/inv.xml',
        'report_xml_path' => $xmlPath,
        'report_html_path' => '/nonexistent/report.html',
        'passed' => true,
        'errors' => [],
        'validated_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('kosit-validator.download.report-xml', $validation));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/xml');
    expect($response->headers->get('content-disposition'))->toContain('inv-report.xml');

    @unlink($xmlPath);
    @rmdir($dir);
});

test('downloadReportXml 404s when the file is missing', function (): void {
    $user = TestEnvironment::makeTestUser();

    $validation = KositValidation::query()->create([
        'input_path' => '/tmp/inv.xml',
        'report_xml_path' => '/nonexistent/missing-report.xml',
        'passed' => true,
        'validated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('kosit-validator.download.report-xml', $validation))
        ->assertNotFound();
});
