import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tailwind from "@tailwindcss/vite";

export default defineConfig(({ command }) => ({
  base: command === "build" ? "/moox/block-editor/assets/" : "/",
  plugins: [react(), tailwind()],
  root: "resources",
  publicDir: "../public",
  build: {
    outDir: "../public/assets",
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: `index.js`,
        chunkFileNames: `[name].js`,
        assetFileNames: `[name].[ext]`
      }
    }
  },
  server: {
    port: 5173,
  },
}));
