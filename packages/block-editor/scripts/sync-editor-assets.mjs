#!/usr/bin/env node
// Spiegelt resources/editor des Pakets nach public/vendor/moox/block-editor (Repo-Root).
import { cp, mkdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const packageRoot = join(__dirname, '..');
const repoRoot = join(__dirname, '../../../..');
const src = join(packageRoot, 'resources', 'editor');
const dest = join(repoRoot, 'public', 'vendor', 'moox', 'editor');

await mkdir(dirname(dest), { recursive: true });
await cp(src, dest, { recursive: true, force: true });

console.log(`Moox editor assets synced:\n  ${src}\n  -> ${dest}`);
