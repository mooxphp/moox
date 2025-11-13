(function ($) {
    "use strict";

    // Import bpmn-js dynamically
    let BpmnViewer, BpmnModeler;

    async function loadBpmnJs() {
        if (!BpmnViewer || !BpmnModeler) {
            try {
                const { default: Viewer } = await import("bpmn-js/lib/Viewer");
                const { default: Modeler } = await import(
                    "bpmn-js/lib/Modeler"
                );

                BpmnViewer = Viewer;
                BpmnModeler = Modeler;

                // Import CSS
                await import("bpmn-js/dist/assets/diagram-js.css");
                await import("bpmn-js/dist/assets/bpmn-js.css");
            } catch (error) {
                console.error("Error loading bpmn-js:", error);
            }
        }
    }

    class MooxBpmnViewer {
        constructor(element) {
            this.element = $(element);
            this.mediaId = this.element.data("media-id");
            this.mode = this.element.data("mode");
            this.fileUrl = this.element.data("file-url");
            this.container = this.element.find(".moox-bpmn-container");
            this.bpmnInstance = null;

            this.init();
        }

        async init() {
            await loadBpmnJs();
            await this.loadBpmnContent();
            this.setupEventListeners();
        }

        async loadBpmnContent() {
            try {
                const response = await fetch(this.fileUrl);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const xml = await response.text();
                await this.renderBpmn(xml);
            } catch (error) {
                console.error("Error loading BPMN content:", error);
                this.showError("Error loading BPMN content");
            }
        }

        async renderBpmn(xml) {
            try {
                // Initialize bpmn-js instance
                if (this.mode === "edit") {
                    this.bpmnInstance = new BpmnModeler({
                        container: this.container[0],
                        keyboard: {
                            bindTo: document,
                        },
                    });
                } else {
                    this.bpmnInstance = new BpmnViewer({
                        container: this.container[0],
                    });
                }

                // Import XML
                await this.bpmnInstance.importXML(xml);
                this.hideLoading();
            } catch (error) {
                console.error("Error rendering BPMN:", error);
                this.showError("Error rendering BPMN diagram");
            }
        }

        setupEventListeners() {
            if (this.mode === "edit") {
                const saveButton = this.element.find(".moox-bpmn-save");
                saveButton.on("click", () => this.saveBpmn());
            }
        }

        async saveBpmn() {
            const saveButton = this.element.find(".moox-bpmn-save");
            const originalText = saveButton.text();

            try {
                saveButton.prop("disabled", true).text("Saving...");

                const { xml } = await this.bpmnInstance.saveXML({
                    format: true,
                });

                const formData = new FormData();
                formData.append("action", "moox_bpmn_save");
                formData.append("mediaId", this.mediaId);
                formData.append("bpmnContent", xml);
                formData.append("nonce", mooxBpmn.nonce);

                const response = await fetch(mooxBpmn.ajaxUrl, {
                    method: "POST",
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    saveButton.text("Saved!");
                    setTimeout(() => {
                        saveButton.text(originalText).prop("disabled", false);
                    }, 2000);
                } else {
                    throw new Error(data.data || "Unknown error");
                }
            } catch (error) {
                console.error("Error saving BPMN:", error);
                saveButton.text("Error saving");
                setTimeout(() => {
                    saveButton.text(originalText).prop("disabled", false);
                }, 2000);
            }
        }

        hideLoading() {
            this.container.find(".moox-bpmn-loading").hide();
        }

        showError(message) {
            this.container.html(
                `<div class="moox-bpmn-error">${message}</div>`
            );
        }
    }

    class MooxBpmnEditor {
        constructor(element) {
            this.element = $(element);
            this.bpmnContent = this.element.data("bpmn-content");
            this.mode = this.element.data("mode");
            this.bpmnInstance = null;

            this.init();
        }

        async init() {
            await loadBpmnJs();
            await this.renderBpmn();
        }

        async renderBpmn() {
            try {
                // Initialize bpmn-js instance
                if (this.mode === "edit") {
                    this.bpmnInstance = new BpmnModeler({
                        container: this.element[0],
                        keyboard: {
                            bindTo: document,
                        },
                    });
                } else {
                    this.bpmnInstance = new BpmnViewer({
                        container: this.element[0],
                    });
                }

                // Import XML
                await this.bpmnInstance.importXML(this.bpmnContent);
            } catch (error) {
                console.error("Error rendering BPMN:", error);
                this.element.html(
                    '<div class="moox-bpmn-error">Error rendering BPMN diagram</div>'
                );
            }
        }

        async getXml() {
            if (this.bpmnInstance) {
                const { xml } = await this.bpmnInstance.saveXML({
                    format: true,
                });
                return xml;
            }
            return null;
        }
    }

    // Initialize viewers on document ready
    $(document).ready(function () {
        $(".moox-bpmn-viewer").each(function () {
            new MooxBpmnViewer(this);
        });

        $(".moox-bpmn-editor").each(function () {
            new MooxBpmnEditor(this);
        });
    });

    // Export for global access
    window.MooxBpmnViewer = MooxBpmnViewer;
    window.MooxBpmnEditor = MooxBpmnEditor;
})(jQuery);
