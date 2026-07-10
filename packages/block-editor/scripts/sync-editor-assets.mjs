#!/usr/bin/env node
// Spiegelt resources/editor des Pakets nach public/vendor/moox/block-editor (Repo-Root).
import { cp, mkdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const packageRoot = join(__dirname, '..');
const webPublicRoot = join(__dirname, '../../../web/public');
const src = join(packageRoot, 'resources', 'editor');
const dest = join(webPublicRoot, 'vendor', 'moox', 'block-editor');
const browserSrc = join(packageRoot, 'resources', 'js', 'browser@4.js');
const browserDest = join(dest, 'browser@4.js');

await mkdir(dest, { recursive: true });
await cp(src, dest, { recursive: true, force: true });
await cp(browserSrc, browserDest, { force: true });

console.log(`Moox editor assets synced:\n  ${src}\n  -> ${dest}`);
