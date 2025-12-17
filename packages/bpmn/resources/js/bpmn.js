// === Imports ===
import BpmnViewer from "bpmn-js/dist/bpmn-viewer.development.js";
import BpmnModeler from "bpmn-js/dist/bpmn-modeler.development.js";
import "bpmn-js/dist/assets/diagram-js.css";
import "bpmn-js/dist/assets/bpmn-js.css";
import "bpmn-js/dist/assets/bpmn-font/css/bpmn.css";
import "bpmn-js/dist/assets/bpmn-font/css/bpmn-codes.css";
import "bpmn-js/dist/assets/bpmn-font/css/bpmn-embedded.css";
import ZoomScrollModule from "diagram-js/lib/navigation/zoomscroll";
import MoveCanvasModule from "diagram-js/lib/navigation/movecanvas";

// Expose globally if needed
window.BpmnViewer = BpmnViewer;
window.BpmnModeler = BpmnModeler;

// === Initialization ===
document.addEventListener("DOMContentLoaded", () => {
  const bpmnElements = document.querySelectorAll("[data-bpmn-viewer]");
  bpmnElements.forEach((element) => {
    const mode = element.dataset.mode || "view";
    const sourceType = element.dataset.sourceType;
    const container = element.querySelector(".bpmn-container");
    if (!container) return;

    const bpmnInstance =
      mode === "edit"
        ? new BpmnModeler({
            container,
            keyboard: { bindTo: document },
            additionalModules: [ZoomScrollModule, MoveCanvasModule],
          })
        : new BpmnViewer({
            container,
            additionalModules: [ZoomScrollModule, MoveCanvasModule],
          });

    if (mode === "edit") enableUnloadWarning(bpmnInstance);


    const bpmnContent = getBpmnContentFromPHP(element);

    if (bpmnContent) {
      loadBpmn(bpmnInstance, bpmnContent, container);
    } else if (sourceType === "file" && element.dataset.filePath) {
      fetch(element.dataset.filePath)
        .then(res => res.text())
        .then(xml => loadBpmn(bpmnInstance, xml, container))
        .catch(() => showError(container, "Error loading BPMN file"));
    } else {
      showError(container, "No BPMN content found");
    }
    
    if (mode === "edit") {
      setupEditor(element, bpmnInstance, sourceType);
      setupUpload(element, bpmnInstance);
    }
    
    setupZoomManager(element, bpmnInstance);
    
  });
}); 


 
// === Helpers ===
function getBpmnContentFromPHP(element) {
  const scriptTag = element.querySelector("script[data-bpmn-content]");
  if (scriptTag) {
    try {
      const json = JSON.parse(scriptTag.textContent);
      return typeof json === "string" ? json : null;
    } catch {
      return scriptTag.textContent.trim();
    }
  }
  return null;
}

function loadBpmn(bpmnInstance, xml, container) {
  bpmnInstance
    .importXML(xml)
    .then(() => {
      const canvas = bpmnInstance.get("canvas");
      canvas.zoom("fit-viewport", "auto");
      hideLoading(container);

      // 🟦 Dynamic Grid
      container.style.backgroundImage =
        "linear-gradient(to right, #e5e7eb 1px, transparent 1px), linear-gradient(to bottom, #e5e7eb 1px, transparent 1px)";
      container.style.backgroundSize = "20px 20px";
      container.style.transition = "background-position 0.1s linear";

      canvas.on("canvas.viewbox.changed", ({ viewbox }) => {
        const { x, y, scale } = viewbox;
        container.style.backgroundPosition = `${x * scale}px ${y * scale}px`;
        container.style.backgroundSize = `${20 * scale}px ${20 * scale}px`;
      });

      const wrapper = container.closest("[data-bpmn-viewer]");
      if (wrapper) setupZoomControls(wrapper, bpmnInstance);
    })
    .catch((err) => showError(container, "Error loading BPMN diagram"));
}

// === Editor / Save ===
function setupEditor(element, bpmnInstance, sourceType) {
  const menu = element.querySelector("[data-bpmn-save-menu]");
  if (!menu) return;

  const button = menu.querySelector(".bpmn-save");
  const dropdown = menu.querySelector("div");

  function openDropdown() {
    dropdown.classList.remove("hidden");
    requestAnimationFrame(() => {
      dropdown.classList.remove("scale-95", "opacity-0");
      dropdown.classList.add("scale-100", "opacity-100");
    });
  }
  function closeDropdown() {
    dropdown.classList.add("scale-95", "opacity-0");
    dropdown.classList.remove("scale-100", "opacity-100");
    setTimeout(() => dropdown.classList.add("hidden"), 200);
  }

  button.addEventListener("click", (e) => {
    e.stopPropagation();
    if (dropdown.classList.contains("hidden")) openDropdown();
    else closeDropdown();
  });
  document.addEventListener("click", (e) => {
    if (!menu.contains(e.target)) closeDropdown();
  });

  dropdown.querySelectorAll("[data-save-type]").forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      closeDropdown();
      const type = e.currentTarget.dataset.saveType;
      await saveBpmnByType(element, bpmnInstance, sourceType, type);
    });
  });
}

async function saveBpmnByType(element, bpmnInstance, sourceType, type) {
  const saveButton = element.querySelector(".bpmn-save");
  const spinner = saveButton.querySelector("svg");

  saveButton.disabled = true;
  spinner.classList.remove("hidden");

  try {
    const baseName = await showRenameModal();
    if (!baseName) return (spinner.classList.add("hidden"), (saveButton.disabled = false));

    let content, mimeType, ext;
    if (type === "bpmn" || !type) {
      const { xml } = await bpmnInstance.saveXML({ format: true });
      storeBpmnContentForForm(element, xml);
      content = xml;
      mimeType = "application/xml";
      ext = "bpmn";
    } else if (type === "svg") {
      const { svg } = await bpmnInstance.saveSVG();
      content = svg;
      mimeType = "image/svg+xml";
      ext = "svg";
    } else if (type === "png") {
      const { svg } = await bpmnInstance.saveSVG();
      content = await svgToPngBlob(svg);
      mimeType = "image/png";
      ext = "png";
    }

    downloadFile(content, `${baseName}.${ext}`, mimeType);
    saveButton.classList.add("bg-green-500");
    document.dispatchEvent(new CustomEvent("bpmn:saved"));
    setTimeout(() => saveButton.classList.remove("bg-green-500"), 1000);
  } catch (err) {
    showError(element.querySelector(".bpmn-container"), "Error saving BPMN file");
  } finally {
    spinner.classList.add("hidden");
    saveButton.disabled = false;
  }
}

function setupUpload(element, bpmnInstance) {
  const fileInput = element.querySelector("[data-bpmn-upload]");
  if (!fileInput) return;

  fileInput.addEventListener("change", async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const container = element.querySelector(".bpmn-container");
    const reader = new FileReader();
    reader.onload = async (evt) => {
      const xml = evt.target.result;
      try {
        await bpmnInstance.importXML(xml);
        bpmnInstance.get("canvas").zoom("fit-viewport", "auto");
        storeBpmnContentForForm(element, xml);
        localStorage.setItem(
          "bpmn_filename",
          sanitizeFilename(file.name.replace(/\.[^/.]+$/, ""))
        );
        hideLoading(container);
      } catch {
        showError(container, "Invalid BPMN file");
      }
    };
    reader.readAsText(file);
  });
}

// === Zoom Toolbar ===
function setupZoomManager(element, bpmnInstance) {
  const container = element.querySelector(".bpmn-container");
  if (!container) return;

  const canvas = bpmnInstance.get("canvas");
  let currentZoom = 1;

  // --- TOOLBAR WRAPPER ---
  const toolbar = document.createElement("div");
  toolbar.className = "bpmn-zoom-toolbar";
  toolbar.innerHTML = `
      <button data-zoom="in" data-hint="Zoom In">＋</button>
      <button data-zoom="out" data-hint="Zoom Out">−</button>
      <button data-zoom="reset" data-hint="Reset Zoom">⟳</button>
      <button data-zoom="fit" data-hint="Fit to Screen">⛶</button>
      <button data-zoom="center" data-hint="Center Diagram">◎</button>
      <span class="bpmn-zoom-level">100%</span>
  `;
  container.style.position = "relative";
  container.appendChild(toolbar);

  const zoomLabel = toolbar.querySelector(".bpmn-zoom-level");
  
   // TOOLTIPS TIMMER
   let tooltipTimeout = null;

   toolbar.querySelectorAll("button[data-hint]").forEach((btn) => {
     btn.addEventListener("mouseenter", () => {
       const text = btn.dataset.hint;
   
       const tip = document.createElement("div");
       tip.className = "bpmn-zoom-tooltip";
       tip.textContent = text;
   
       btn.appendChild(tip);
   
       // fade in
       requestAnimationFrame(() => (tip.style.opacity = "1"));
   
       // remove after timer
       clearTimeout(tooltipTimeout);
       tooltipTimeout = setTimeout(() => {
         tip.style.opacity = "0";
         setTimeout(() => tip.remove(), 150); // allow fade-out
       }, 1200); // <<< TIMER HERE (1.2 seconds)
     });
   
     // always remove tooltip when mouse leaves
     btn.addEventListener("mouseleave", () => {
       const tip = btn.querySelector(".bpmn-zoom-tooltip");
       if (tip) {
         tip.style.opacity = "0";
         setTimeout(() => tip.remove(), 150);
       }
     });
   });


  // --- HELPERS ---
  const clampZoom = (z) => Math.min(3, Math.max(0.2, z));

  const updateZoomDisplay = () => {
    zoomLabel.textContent = `${Math.round(currentZoom * 100)}%`;
  };

  const applyZoom = (z) => {
    currentZoom = clampZoom(z);
    canvas.zoom(currentZoom);
    updateZoomDisplay();
  };

  const commands = {
    in: () => applyZoom(currentZoom + 0.1),
    out: () => applyZoom(currentZoom - 0.1),
    reset: () => applyZoom(1),
    fit: () => {
      canvas.zoom("fit-viewport", "auto");
      currentZoom = 1;
      updateZoomDisplay();
    },
    center: () => canvas.zoom(currentZoom, "auto")
  };

  // --- CLICK EVENTS ---
  toolbar.addEventListener("click", (e) => {
    const type = e.target.dataset.zoom;
    if (type && commands[type]) commands[type]();
  });

  // --- CTRL + MOUSE WHEEL ---
  container.addEventListener(
    "wheel",
    (e) => {
      if (!e.ctrlKey) return;
      e.preventDefault();
      const step = e.deltaY > 0 ? -0.1 : 0.1;
      applyZoom(currentZoom + step);
    },
    { passive: false }
  );

  // --- KEYBOARD SHORTCUTS ---
  window.addEventListener("keydown", (e) => {
    if (!e.ctrlKey) return;
    switch (e.key.toLowerCase()) {
      case "+": case "=": commands.in(); break;
      case "-": commands.out(); break;
      case "0": commands.reset(); break;
      case "f": commands.fit(); break;
    }
  });

  updateZoomDisplay();
}




// === Utility Functions ===
function downloadFile(content, filename, mimeType) {
  const blob = content instanceof Blob ? content : new Blob([content], { type: mimeType });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}
async function svgToPngBlob(svgString) {
  return new Promise((resolve) => {
    const img = new Image();
    const svgBlob = new Blob([svgString], { type: "image/svg+xml" });
    const url = URL.createObjectURL(svgBlob);
    img.onload = () => {
      const canvas = document.createElement("canvas");
      canvas.width = img.width;
      canvas.height = img.height;
      const ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0);
      canvas.toBlob((blob) => { resolve(blob); URL.revokeObjectURL(url); }, "image/png");
    };
    img.src = url;
  });
}
function storeBpmnContentForForm(element, content) {
  let hiddenInput = element.querySelector('input[name="bpmn_content"]');
  if (!hiddenInput) {
    hiddenInput = document.createElement("input");
    hiddenInput.type = "hidden";
    hiddenInput.name = "bpmn_content";
    element.appendChild(hiddenInput);
  }
  hiddenInput.value = content;
}
function hideLoading(container) {
  const loading = container.querySelector(".bpmn-loading");
  if (loading) loading.style.display = "none";
}
function showError(container, message) {
  const loading = container.querySelector(".bpmn-loading");
  if (loading) loading.innerHTML = `<div class="bpmn-error">${message}</div>`;
}
function sanitizeFilename(name) {
  if (!name) return "diagram";
  return name.trim().replace(/\s+/g, "_").replace(/[^\w\-]/g, "").substring(0, 50) || "diagram";
}

// === Rename Modal ===
async function showRenameModal() {
  return new Promise((resolve) => {
    const lastName = localStorage.getItem("bpmn_filename") || "diagram";
    document.querySelector("#renameModal")?.remove();
    const modal = document.createElement("div");
    modal.id = "renameModal";
    modal.className = "fixed inset-0 flex items-center justify-center bg-black/40 z-50";
    modal.innerHTML = `
      <div class="bg-gray-100 rounded-xl shadow-2xl p-6 w-80 transform transition-all scale-95 opacity-0">
        <h2 class="text-lg font-semibold mb-3 text-gray-800">Rename File</h2>
        <input type="text" id="renameInput" value="${lastName}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
        <div class="flex justify-end mt-4 space-x-2">
          <button class="cancel-btn px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">Cancel</button>
          <button class="save-btn px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Save</button>
        </div>
      </div>`;
    document.body.appendChild(modal);
    const dialog = modal.querySelector("div");
    requestAnimationFrame(() => {
      dialog.classList.remove("scale-95", "opacity-0");
      dialog.classList.add("scale-100", "opacity-100");
    });

    const input = modal.querySelector("#renameInput");
    const cancel = modal.querySelector(".cancel-btn");
    const save = modal.querySelector(".save-btn");

    cancel.addEventListener("click", () => close(null));
    save.addEventListener("click", () => close(input.value.trim()));
    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter") close(input.value.trim());
      if (e.key === "Escape") close(null);
    });

    function close(value) {
      dialog.classList.add("scale-95", "opacity-0");
      setTimeout(() => modal.remove(), 150);
      if (!value) return resolve(null);
      const finalName = sanitizeFilename(value);
      localStorage.setItem("bpmn_filename", finalName);
      resolve(finalName);
    }
  });
}

// === Unsaved Changes Warning ===
function enableUnloadWarning(bpmnInstance) {
  let hasChanges = false;
  bpmnInstance.on("commandStack.changed", () => { hasChanges = true; });
  document.addEventListener("bpmn:saved", () => { hasChanges = false; });
  window.addEventListener("beforeunload", (e) => {
    if (!hasChanges) return;
    e.preventDefault();
    e.returnValue = "You have unsaved BPMN changes. Are you sure you want to leave?";
    return e.returnValue;
  });
}
