import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tailwind from "@tailwindcss/vite";
import { readFileSync } from "fs";

const packageJson = JSON.parse(readFileSync("./package.json", "utf-8"));
const version = packageJson.version;

export default defineConfig(({ command }) => ({
  base: command === "build" ? "/moox/block-editor/assets/" : "/",
  plugins: [react(), tailwind()],
  root: "resources",
  build: {
    outDir: "../public/assets",
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: `index.v${version}.js`,
        chunkFileNames: `[name].v${version}.js`,
        assetFileNames: `[name].v${version}.[ext]`
      }
    }
  },
  server: {
    port: 5173,
    proxy: {
        '/moox/block-editor/assets': {
          target: 'https://mooxdev.test',
          changeOrigin: true,
          secure: false
        }
      }
  },
}));
