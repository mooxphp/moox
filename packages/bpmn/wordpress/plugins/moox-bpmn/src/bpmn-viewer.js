(function ($) {
    "use strict";

    class MooxBpmnViewer {

        constructor(element) {
            this.element = $(element);
            this.mediaId = this.element.data("media-id");
            this.mode = this.element.data("mode") || "view";
            this.fileUrl = this.element.data("file-url");
            this.container = this.element.find(".moox-bpmn-container");
            this.saveButton = this.element.find(".moox-bpmn-save");
            this.bpmnInstance = null;

            this.init();
        }

        async init() {
            if (!this.fileUrl) return this.showError("Missing BPMN file URL");

            try {
                const xml = await this.loadBpmnContent();
                await this.renderBpmn(xml);
                this.bindEvents();
            } catch (err) {
                console.error("BPMN init failed:", err);
                this.showError("Unable to load BPMN diagram.");
            }
        }

        async loadBpmnContent() {
            const res = await fetch(this.fileUrl);
            if (!res.ok) throw new Error("Failed to load BPMN");
            return await res.text();
        }

        /** PICK THE CORRECT BPMN ENGINE **/
        getConstructor() {
            if (this.mode === "edit" && window.BpmnModeler) return window.BpmnModeler;
            if (window.BpmnNavigatedViewer) return window.BpmnNavigatedViewer;
            if (window.BpmnViewer) return window.BpmnViewer;
            if (window.BpmnJS) return window.BpmnJS;

            console.error("No BPMN constructor available.");
            return null;
        }

        async renderBpmn(xml) {
            const Constructor = this.getConstructor();
            if (!Constructor) return this.showError("BPMN engine not available.");

            this.bpmnInstance = new Constructor({
                container: this.container[0],
                keyboard: { bindTo: document }
            });

            try {
                await this.bpmnInstance.importXML(xml);
            } catch (err) {
                console.error("Import error:", err);
                return this.showError("Invalid BPMN XML");
            }

            const canvas = this.bpmnInstance.get("canvas");

            if (canvas?.zoom) canvas.zoom("fit-viewport");

            window.addEventListener("resize", () => {
                if (canvas?.zoom) canvas.zoom("fit-viewport");
            });

            this.addZoomControls();
            this.hideLoading();
        }

        addZoomControls() {
            const zoomScroll = this.bpmnInstance.get("zoomScroll");
            const canvas = this.bpmnInstance.get("canvas");

            if (!zoomScroll) return; // no navigation = viewer mode only

            const controls = $(`
                <div class="moox-bpmn-zoom-controls">
                    <button class="zoom-in">+</button>
                    <button class="zoom-out">−</button>
                    <button class="zoom-reset">Reset</button>
                </div>
            `).appendTo(this.container);

            controls.find(".zoom-in").on("click", () => zoomScroll.stepZoom(1));
            controls.find(".zoom-out").on("click", () => zoomScroll.stepZoom(-1));
            controls.find(".zoom-reset").on("click", () => canvas.zoom("fit-viewport"));
        }

        bindEvents() {
            if (this.mode === "edit" && this.saveButton.length) {
                this.saveButton.on("click", () => this.saveBpmn());
            }
        }

        async saveBpmn() {
            const button = this.saveButton;
            const original = button.text();

            button.prop("disabled", true).text("Saving...");

            try {
                const { xml } = await this.bpmnInstance.saveXML({ format: true });

                const formData = new FormData();
                formData.append("action", "moox_bpmn_save");
                formData.append("mediaId", this.mediaId);
                formData.append("bpmnContent", xml);
                formData.append("nonce", mooxBpmn.nonce);

                const res = await fetch(mooxBpmn.ajaxUrl, {
                    method: "POST",
                    body: formData
                });

                const json = await res.json();
                button.text(json.success ? "Saved!" : "Error");
            } catch (err) {
                console.error("Save failed:", err);
                button.text("Error");
            } finally {
                setTimeout(() => {
                    button.text(original).prop("disabled", false);
                }, 1500);
            }
        }

        hideLoading() {
            this.container.find(".moox-bpmn-loading").hide();
        }

        showError(msg) {
            this.container.html(`<div class="moox-bpmn-error">${msg}</div>`);
        }
    }


    // AUTO-INSTANTIATE
    $(function () {
        $(".moox-bpmn-viewer").each(function () {
            new MooxBpmnViewer(this);
        });
    });

})(jQuery);
