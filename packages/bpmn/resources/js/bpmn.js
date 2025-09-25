import BpmnViewer from "bpmn-js/lib/Viewer";
import BpmnModeler from "bpmn-js/lib/Modeler";
import "bpmn-js/dist/assets/diagram-js.css";
import "bpmn-js/dist/assets/bpmn-js.css";

window.BpmnViewer = BpmnViewer;
window.BpmnModeler = BpmnModeler;

document.addEventListener("DOMContentLoaded", function () {
    const bpmnElements = document.querySelectorAll("[data-bpmn-viewer]");

    bpmnElements.forEach((element) => {
        const mode = element.dataset.mode || "view";
        const sourceType = element.dataset.sourceType;
        const container = element.querySelector(".bpmn-container");

        if (!container) return;

        let bpmnInstance;
        let bpmnData = null;

        // Initialize bpmn-js instance
        if (mode === "edit") {
            bpmnInstance = new BpmnModeler({
                container: container,
                keyboard: {
                    bindTo: document,
                },
            });
        } else {
            bpmnInstance = new BpmnViewer({
                container: container,
            });
        }

        // Load BPMN content from PHP
        const bpmnContent = getBpmnContentFromPHP(element);
        if (bpmnContent) {
            bpmnData = bpmnContent;
            bpmnInstance
                .importXML(bpmnContent)
                .then(() => {
                    console.log("BPMN file loaded successfully");
                    hideLoading(container);
                })
                .catch((err) => {
                    console.error("Error loading BPMN file:", err);
                    showError(container, "Error loading BPMN file");
                });
        } else {
            showError(container, "No BPMN content found");
        }

        // Setup editor functionality
        if (mode === "edit") {
            setupEditor(element, bpmnInstance, sourceType);
        }
    });
});

function getBpmnContentFromPHP(element) {
    // Find the JSON script tag with BPMN content
    const scriptTag = element.querySelector("script[data-bpmn-content]");
    if (scriptTag) {
        try {
            return scriptTag.textContent;
        } catch (error) {
            console.error("Error parsing BPMN content from PHP:", error);
            return null;
        }
    }
    return null;
}

function setupEditor(element, bpmnInstance, sourceType) {
    const saveButton = element.querySelector(".bpmn-save");

    if (saveButton) {
        saveButton.addEventListener("click", () => {
            saveBpmnFile(element, bpmnInstance, sourceType);
        });
    }
}

async function saveBpmnFile(element, bpmnInstance, sourceType) {
    const saveButton = element.querySelector(".bpmn-save");
    const container = element.querySelector(".bpmn-container");

    if (saveButton) {
        saveButton.disabled = true;
        saveButton.textContent = "Saving...";
    }

    try {
        const { xml } = await bpmnInstance.saveXML({ format: true });

        // Store the XML content in a hidden input for form submission
        storeBpmnContentForForm(element, xml);

        console.log("BPMN content prepared for saving");

        if (saveButton) {
            saveButton.textContent = "Saved!";
            setTimeout(() => {
                saveButton.textContent = "Save Changes";
                saveButton.disabled = false;
            }, 2000);
        }
    } catch (error) {
        console.error("Error preparing BPMN content:", error);
        showError(container, "Error preparing BPMN content");

        if (saveButton) {
            saveButton.textContent = "Save Changes";
            saveButton.disabled = false;
        }
    }
}

function storeBpmnContentForForm(element, content) {
    // Find or create a hidden input to store the BPMN content
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
    if (loading) {
        loading.style.display = "none";
    }
}

function showError(container, message) {
    const loading = container.querySelector(".bpmn-loading");
    if (loading) {
        loading.innerHTML = `<div class="bpmn-error">${message}</div>`;
    }
}
