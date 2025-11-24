import { readFileSync, writeFileSync } from 'fs';
import { join } from 'path';

const manifestPath = join(process.cwd(), 'public/build/manifest.json');
const manifest = JSON.parse(readFileSync(manifestPath, 'utf-8'));

// Normalize paths in manifest to use relative paths from package root
const normalizedManifest = {};
for (const [key, value] of Object.entries(manifest)) {
    // Extract just the filename part (resources/css/app.css)
    const normalizedKey = key.replace(/^.*\/(resources\/[^/]+\/[^/]+)$/, '$1');
    
    normalizedManifest[normalizedKey] = {
        ...value,
        src: normalizedKey,
    };
    
    // Also normalize imports
    if (value.imports) {
        normalizedManifest[normalizedKey].imports = value.imports.map(imp => 
            imp.replace(/^.*\/(resources\/[^/]+\/[^/]+)$/, '$1')
        );
    }
}

writeFileSync(manifestPath, JSON.stringify(normalizedManifest, null, 2));
console.log('Manifest normalized successfully');

