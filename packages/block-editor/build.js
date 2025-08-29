#!/usr/bin/env node

import { execSync } from "child_process";
import { readFileSync, writeFileSync, readdirSync } from "fs";
import { join, dirname } from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

console.log("🚀 Building Block Editor...");

try {
    const packageJson = JSON.parse(
        readFileSync(join(__dirname, "package.json"), "utf-8")
    );
    const version = packageJson.version;

    console.log(`📦 Building version ${version}...`);

    console.log("🔨 Running Vite build...");
    execSync("npx vite build", { stdio: "inherit", cwd: __dirname });

    const assetsDir = join(__dirname, "public", "assets");
    const files = readdirSync(assetsDir);

    const jsExists = files.includes("index.js");
    const cssExists = files.includes("index.css");

    if (!jsExists || !cssExists) {
        throw new Error(
            "Could not find built index.js or index.css in public/assets"
        );
    }

    const jsFile = `index.js?v=${version}`;
    const cssFile = `index.css?v=${version}`;

    const bladeViewPath = join(
        __dirname,
        "resources",
        "views",
        "editor.blade.php"
    );

    const bladeContent = `<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1.0" />
        <link rel="icon" href="{{ url('moox/block-editor/images/favicon.png') }}">
        <title>Moox Block Editor</title>
        <script type="module" crossorigin src="{{ url('moox/block-editor/assets/${jsFile}') }}"></script>
        <link rel="stylesheet" crossorigin href="{{ url('moox/block-editor/assets/${cssFile}') }}">
    </head>
    <body class="min-h-screen">
        <div
            id="block-editor"
            data-mode="{{ $mode ?? 'web' }}"
            data-initial-content='@json($initialContent ?? [])'
        ></div>
    </body>
</html>`;

    writeFileSync(bladeViewPath, bladeContent);

    console.log("✅ Build completed successfully!");
    console.log("📝 Updated editor.blade.php with cache-busted assets");
    console.log("🌐 Assets will be served from: /moox/block-editor/assets/");
} catch (error) {
    console.error("❌ Build failed:", error.message);
    process.exit(1);
}
