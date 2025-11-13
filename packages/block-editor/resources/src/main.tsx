import React from "react";
import ReactDOM from "react-dom/client";
import "./index.css";
import { App } from "./App";

// Erst Laravel data-attributes prüfen (für Build)
const blockEditorRoot = document.getElementById("block-editor");
// Fallback auf root (für Dev)
const devRoot = document.getElementById("root");

const root = blockEditorRoot || devRoot;
const laravelMode = root?.getAttribute("data-mode");
const laravelContent = root?.getAttribute("data-initial-content");

// URL-Parameter als Fallback für Entwicklung
const urlParams = new URLSearchParams(window.location.search);
const urlMode = urlParams.get("mode");

// Strenge Validierung - nur gültige Modi akzeptieren
const validModes = ["web", "mail"] as const;
const mode = validModes.includes(urlMode as any) ? urlMode as "web" | "mail" :
            validModes.includes(laravelMode as any) ? laravelMode as "web" | "mail" : null;

if (mode) {
  // Mode gesetzt - Editor rendern
  ReactDOM.createRoot(root!).render(
    <React.StrictMode>
      <App mode={mode} />
    </React.StrictMode>
  );
} else {
  // Kein Mode - Zwei Links anzeigen
  if (root) {
    root.innerHTML = `
      <div class="flex justify-center items-center min-h-screen text-white bg-zinc-900">
        <div class="space-y-6 text-center">
          <img src="/images/logo.png" alt="Moox" class="mx-auto mb-16 w-auto h-12" />
          <h1 class="text-4xl font-bold text-zinc-100">Moox Block Editor</h1>
          <p class="text-zinc-400">Wähle deinen Editor-Modus:</p>
          <div class="flex gap-4 justify-center">
            <a href="?mode=web" class="px-6 py-3 font-medium bg-violet-500 rounded-md transition-colors hover:bg-violet-600">
              Web Editor
            </a>
            <a href="?mode=mail" class="px-6 py-3 font-medium bg-blue-500 rounded-md transition-colors hover:bg-violet-600">
              Mail Editor
            </a>
            </div>
          </div>
        </div>
      </div>
    `;
  }
}
