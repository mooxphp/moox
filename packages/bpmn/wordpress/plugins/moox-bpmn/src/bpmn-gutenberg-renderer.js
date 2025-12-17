(function () {

    const resizeHandlers = new WeakMap();

    function getConstructor(mode) {
        if (mode === "edit" && typeof window.BpmnModeler === "function") {
            return window.BpmnModeler;
        }
        if (typeof window.BpmnJS?.NavigatedViewer === "function") {
            return window.BpmnJS.NavigatedViewer;
        }
        if (typeof window.BpmnViewer === "function") return window.BpmnViewer;
        if (typeof window.BpmnJS === "function") return window.BpmnJS;
        return null;
    }

    window.renderGutenbergBpmn = async function (el, xml, mode) {
        if (!el || !xml) return;

        // DESTROY previous instance first
        if (el.__bpmnInstance) {
            try {
                el.__bpmnInstance.destroy();
            } catch (e) {}
        }

        // REMOVE old resize handler
        const oldResize = resizeHandlers.get(el);
        if (oldResize) {
            window.removeEventListener("resize", oldResize);
            resizeHandlers.delete(el);
        }

        el.innerHTML = "";

        const Constructor = getConstructor(mode);

        if (!Constructor) {
            el.innerHTML = `<div class="moox-bpmn-error">BPMN engine not loaded</div>`;
            return;
        }

        let instance;
        try {
            instance = new Constructor({
                container: el,
                keyboard: { bindTo: document }
            });
        } catch (err) {
            console.error(err);
            el.innerHTML = `<div class="moox-bpmn-error">Failed to initialize BPMN viewer</div>`;
            return;
        }

        el.__bpmnInstance = instance;

        try {
            await instance.importXML(xml);
        } catch (err) {
            console.error(err);
            el.innerHTML = `<div class="moox-bpmn-error">Invalid BPMN XML</div>`;
            return;
        }

        const canvas = instance.get("canvas");
        if (canvas?.zoom) {
            const resize = () => canvas.zoom("fit-viewport");
            resizeHandlers.set(el, resize);
            window.addEventListener("resize", resize);
            resize();
        }

        // Zoom UI
        if (instance.get('zoomScroll')) {
            const controls = document.createElement("div");
            controls.className = "moox-bpmn-zoom-controls";
            controls.innerHTML = `
                <button class="zoom-in">+</button>
                <button class="zoom-out">−</button>
                <button class="zoom-reset">Reset</button>
            `;
            el.appendChild(controls);

            const zoomScroll = instance.get('zoomScroll');

            controls.querySelector(".zoom-in").onclick = () => zoomScroll.stepZoom(1);
            controls.querySelector(".zoom-out").onclick = () => zoomScroll.stepZoom(-1);
            controls.querySelector(".zoom-reset").onclick = () => canvas.zoom("fit-viewport");
        }

        return instance;
    };

})();
