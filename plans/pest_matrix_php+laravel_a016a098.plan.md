---
name: Pest Matrix PHP+Laravel
overview: Replace the single-PHP matrix with two explicit PHP+Laravel combinations (8.4+L12, 8.5+L13) in both the build and pest jobs, and fix the artifact naming throughout.
todos:
  - id: matrix-build
    content: Add include matrix to build job, use matrix.php and matrix.laravel variables
    status: completed
  - id: matrix-pest
    content: Add include matrix to pest job, update artifact download/upload names
    status: completed
  - id: fix-coverage-artifacts
    content: Fix codacy and qlty artifact references to clover-report-8.4-12
    status: completed
isProject: false
---

# Pest Matrix: PHP 8.4+L12 and PHP 8.5+L13

## Target file
[`.github/workflows/pest.yml`](.github/workflows/pest.yml)

## Changes

### `build` job
- Add a matrix with `include` to run both combinations
- Replace hardcoded `php-version: 8.5` with `${{ matrix.php }}`
- Pass `--laravel=${{ matrix.laravel }}` to `dev.php`
- Upload the artifact with name `app-artifact-${{ matrix.php }}-${{ matrix.laravel }}`

```yaml
build:
  name: Build Laravel ${{ matrix.laravel }} (PHP ${{ matrix.php }})
  runs-on: ubuntu-latest
  strategy:
    matrix:
      include:
        - php: '8.4'
          laravel: '12'
        - php: '8.5'
          laravel: '13'
  steps:
    - uses: actions/checkout@v6
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php }}
    - name: Bootstrap Laravel App
      run: |
        php dev.php --laravel=${{ matrix.laravel }} --db=mysql
        mkdir -p storage/app storage/framework storage/logs
        touch storage/.gitkeep
    - name: Upload App Artifact
      uses: actions/upload-artifact@v7
      with:
        name: app-artifact-${{ matrix.php }}-${{ matrix.laravel }}
        path: app-artifact.tar.gz
```

### `pest` job
- Same `include` matrix
- Download `app-artifact-${{ matrix.php }}-${{ matrix.laravel }}`
- Upload clover as `clover-report-${{ matrix.php }}-${{ matrix.laravel }}`

### `codacy` and `qlty` jobs
- Fix the hardcoded wrong artifact name (`clover-report-8.4` → `clover-report-8.4-12`)
- Use the PHP 8.4 + Laravel 12 report as the canonical coverage artifact (stable combo)

## Note on current bug
The existing `codacy`/`qlty` jobs reference `clover-report-8.4` but the pest matrix was `[8.5]` — this is already broken. The new naming (`clover-report-8.4-12`) fixes this consistently.
