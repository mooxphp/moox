# Demo asset sources

Static files under `resources/demo/assets/` are bundled with `moox/demo` for offline seeding.
They were fetched once for maintainers; **`moox:demo` does not download from the internet.**

Fetched: 2026-05-26

## Layout

| Path | Purpose | Count (approx.) |
|------|---------|-----------------|
| `images/products/` | Product / category style photos | 36 |
| `images/users/` | User avatar photos | 222 |
| `files/pdf/` | PDF documents | 4 |
| `files/documents/` | TXT, DOCX, XLSX | 3 |
| `files/audio/` | MP3 sample | 1 |
| `videos/short/` | Short MP4/WebM clips | 5 |

## Sources and licenses

### User avatars (`images/users/`)

- **Source:** Bundled demo portraits (legacy pool from `resources/demo/media/users/`, relocated 2026-05-27).
- **License:** Demo use only; replace with your own avatars in production.

### Product images (`images/products/`)

- **Source:** [Lorem Picsum](https://picsum.photos/) — `https://picsum.photos/id/{id}/800/800`
- **License:** Photos from [Unsplash](https://unsplash.com/license) via Picsum; use for demos only, not as your own product photography in production marketing without checking each image.

### PDF (`files/pdf/`)

| File | URL |
|------|-----|
| `sample-dummy.pdf` | https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf |
| `sample-1.pdf` … `sample-3.pdf` | https://filesamples.com/samples/document/pdf/sample{1,2,3}.pdf |

### Documents (`files/documents/`)

| File | URL |
|------|-----|
| `sample.txt` | https://filesamples.com/samples/document/txt/sample1.txt |
| `sample.docx` | https://filesamples.com/samples/document/docx/sample1.docx |
| `sample.xlsx` | https://filesamples.com/samples/document/xlsx/sample1.xlsx |

### Audio (`files/audio/`)

| File | URL |
|------|-----|
| `sample.mp3` | https://filesamples.com/samples/audio/mp3/sample1.mp3 |

### Videos (`videos/short/`)

| File | URL | Notes |
|------|-----|-------|
| `sample-5s.mp4` | https://download.samplelib.com/mp4/sample-5s.mp4 | ~2.7 MB |
| `sample-10s.mp4` | https://download.samplelib.com/mp4/sample-10s.mp4 | ~5.2 MB |
| `flower.webm` | https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.webm | CC0 (MDN) |
| `big-buck-bunny-360.mp4` | https://test-videos.co.uk/vids/bigbuckbunny/mp4/h264/360/Big_Buck_Bunny_360_10s_1MB.mp4 | Blender Foundation |
| `sample-mp4.mp4` | https://www.learningcontainer.com/wp-content/uploads/2020/05/sample-mp4-file.mp4 | ~10 MB |

**Total video size:** ~20 MB — consider Git LFS if the repo grows further (see demo package plan Phase 2.5).

## Re-download (maintainers)

From the repo root, example for product images (PowerShell):

```powershell
$dir = "packages/demo/resources/demo/assets/images/products"
1..35 | ForEach-Object {
  Invoke-WebRequest -Uri "https://picsum.photos/id/$_/800/800" -OutFile "$dir/product-{0:D3}.jpg" -f $_
}
```

See URLs in the tables above for other types.
