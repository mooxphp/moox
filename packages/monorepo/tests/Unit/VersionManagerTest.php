<?php

use Moox\Monorepo\Contracts\GitHubClientInterface;
use Moox\Monorepo\Services\VersionManager;

beforeEach(function () {
    $this->githubClient = Mockery::mock(GitHubClientInterface::class);
    $this->versionManager = new VersionManager($this->githubClient);
});

it('validates semantic version format correctly', function () {
    expect($this->versionManager->validateVersionFormat('1.0.0'))->toBeTrue();
    expect($this->versionManager->validateVersionFormat('1.2.3'))->toBeTrue();
    expect($this->versionManager->validateVersionFormat('10.20.30'))->toBeTrue();
    expect($this->versionManager->validateVersionFormat('1.0.0-alpha.1'))->toBeTrue();
    expect($this->versionManager->validateVersionFormat('1.0.0-beta.2'))->toBeTrue();
    expect($this->versionManager->validateVersionFormat('1.0.0-rc.1'))->toBeTrue();
    
    expect($this->versionManager->validateVersionFormat('1.0'))->toBeFalse();
    expect($this->versionManager->validateVersionFormat('v1.0.0'))->toBeFalse();
    expect($this->versionManager->validateVersionFormat('1.0.0-invalid'))->toBeFalse();
    expect($this->versionManager->validateVersionFormat('invalid'))->toBeFalse();
});

it('detects prerelease versions correctly', function () {
    expect($this->versionManager->isPrerelease('1.0.0'))->toBeFalse();
    expect($this->versionManager->isPrerelease('1.0.0-alpha.1'))->toBeTrue();
    expect($this->versionManager->isPrerelease('1.0.0-beta.2'))->toBeTrue();
    expect($this->versionManager->isPrerelease('1.0.0-rc.1'))->toBeTrue();
});

it('parses version components correctly', function () {
    $components = $this->versionManager->parseVersion('1.2.3');
    expect($components)->toBe([
        'major' => 1,
        'minor' => 2,
        'patch' => 3,
        'prerelease' => null,
        'prerelease_version' => null,
        'is_prerelease' => false,
    ]);

    $components = $this->versionManager->parseVersion('1.2.3-alpha.4');
    expect($components)->toBe([
        'major' => 1,
        'minor' => 2,
        'patch' => 3,
        'prerelease' => 'alpha',
        'prerelease_version' => 4,
        'is_prerelease' => true,
    ]);
});

it('suggests next stable version correctly', function () {
    expect($this->versionManager->suggestNextVersion('1.2.3'))->toBe('1.2.4');
    expect($this->versionManager->suggestNextVersion('2.0.0'))->toBe('2.0.1');
});

it('suggests next prerelease version correctly', function () {
    expect($this->versionManager->suggestNextVersion('1.2.3-alpha.1'))->toBe('1.2.3-alpha.2');
    expect($this->versionManager->suggestNextVersion('1.2.3-beta.1'))->toBe('1.2.3-beta.2');
    expect($this->versionManager->suggestNextVersion('1.2.3-rc.1'))->toBe('1.2.3');
});

it('compares versions correctly', function () {
    expect($this->versionManager->compareVersions('1.0.0', '1.0.1'))->toBeLessThan(0);
    expect($this->versionManager->compareVersions('1.0.1', '1.0.0'))->toBeGreaterThan(0);
    expect($this->versionManager->compareVersions('1.0.0', '1.0.0'))->toBe(0);
});

it('gets current version from repository', function () {
    $this->githubClient
        ->shouldReceive('getLatestReleaseTag')
        ->with('org', 'repo')
        ->once()
        ->andReturn('v1.2.3');

    $version = $this->versionManager->getCurrentVersion('org', 'repo');
    expect($version)->toBe('1.2.3');
});

it('handles missing releases', function () {
    $this->githubClient
        ->shouldReceive('getLatestReleaseTag')
        ->with('org', 'repo')
        ->once()
        ->andReturn(null);

    $version = $this->versionManager->getCurrentVersion('org', 'repo');
    expect($version)->toBeNull();
});

it('creates version suggestions', function () {
    $suggestions = $this->versionManager->createVersionSuggestions('1.2.3');
    
    expect($suggestions['patch'])->toBe('1.2.4');
    expect($suggestions['minor'])->toBe('1.3.0');
    expect($suggestions['major'])->toBe('2.0.0');
    expect($suggestions['alpha'])->toBe('1.2.4-alpha.1');
    expect($suggestions['beta'])->toBe('1.2.4-beta.1');
    expect($suggestions['rc'])->toBe('1.2.4-rc.1');
});

it('formats version for display', function () {
    expect($this->versionManager->formatVersionForDisplay('1.2.3'))->toBe('v1.2.3');
    expect($this->versionManager->formatVersionForDisplay('1.2.3-alpha.1'))->toBe('v1.2.3-alpha.1 (Alpha)');
    expect($this->versionManager->formatVersionForDisplay('1.2.3-beta.1'))->toBe('v1.2.3-beta.1 (Beta)');
    expect($this->versionManager->formatVersionForDisplay('1.2.3-rc.1'))->toBe('v1.2.3-rc.1 (Release Candidate)');
}); 