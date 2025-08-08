#!/usr/bin/env node

import { execSync } from "child_process";
import { readFileSync, writeFileSync, readdirSync } from "fs";
import { join, dirname } from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

console.log("🚀 Building Block Editor...");

try {
    // Read package.json to get version
    const packageJson = JSON.parse(
        readFileSync(join(__dirname, "package.json"), "utf-8")
    );
    const version = packageJson.version;

    console.log(`📦 Building version ${version}...`);

    // Run Vite build
    console.log("🔨 Running Vite build...");
    execSync("npx vite build", { stdio: "inherit", cwd: __dirname });

    // Find built assets
    const assetsDir = join(__dirname, "public", "assets");
    const files = readdirSync(assetsDir);

    const jsFile = files.find(
        (file) => file.startsWith("index.v") && file.endsWith(".js")
    );
    const cssFile = files.find(
        (file) => file.startsWith("index.v") && file.endsWith(".css")
    );

    if (!jsFile || !cssFile) {
        throw new Error("Could not find built JS or CSS files");
    }

    console.log(`📄 Found assets: ${jsFile}, ${cssFile}`);

    // Update Blade view
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
        <div id="root"></div>
    </body>
</html>`;

    writeFileSync(bladeViewPath, bladeContent);

    console.log("✅ Build completed successfully!");
    console.log(`📝 Updated editor.blade.php with versioned assets`);
    console.log(`🌐 Assets will be served from: /moox/block-editor/assets/`);
} catch (error) {
    console.error("❌ Build failed:", error.message);
    process.exit(1);
}
