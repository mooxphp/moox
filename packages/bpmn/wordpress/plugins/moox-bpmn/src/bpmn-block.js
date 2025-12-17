
(function () {
    const { __ } = wp.i18n;
    const { useState, useEffect, useRef } = wp.element;
    const { useSelect } = wp.data;

    const {
        InspectorControls,
        MediaUpload,
        MediaUploadCheck,
        useBlockProps,
    } = wp.blockEditor;

    const {
        PanelBody,
        Button,
        TextControl,
        SelectControl,
        Placeholder,
        Spinner,
        __experimentalVStack: VStack,
    } = wp.components;

    wp.blocks.registerBlockType("moox/bpmn-viewer", {
        title: __("BPMN Viewer", "moox-bpmn"),
        icon: "chart-line",
        category: "media",
        description: __("Upload, view, and edit BPMN 2.0 models", "moox-bpmn"),

        attributes: {
            mediaId: { type: "number", default: 0 },
            mode: { type: "string", default: "view" },
            height: { type: "string", default: "500px" },
        },

        edit: function (props) {
            const { attributes, setAttributes } = props;
            const { mediaId, mode, height } = attributes;

            const [bpmnContent, setBpmnContent] = useState("");
            const [isEditing, setIsEditing] = useState(false);
            const [isLoading, setIsLoading] = useState(false);
            const previewRef = useRef(null);
            const modelerRef = useRef(null);

            // Load media
            const media = useSelect(
                (select) =>
                    mediaId
                        ? select("core").getEntityRecord("postType", "attachment", mediaId, { context: "view" })
                        : null,
                [mediaId]
            );

            // Fetch BPMN XML
            useEffect(() => {
                if (!media?.source_url) return;
                setIsLoading(true);

                fetch(media.source_url)
                    .then((res) => res.text())
                    .then((content) => {
                        setBpmnContent(content);
                        setIsLoading(false);
                    })
                    .catch((err) => {
                        console.error("Error loading BPMN:", err);
                        setIsLoading(false);
                    });
            }, [media]);

            // Render BPMN
            useEffect(() => {
                if (!previewRef.current || !bpmnContent || typeof window.renderGutenbergBpmn !== "function") return;

                previewRef.current.innerHTML = "";
                const renderMode = isEditing && mode === "edit" ? "edit" : "view";

                window.renderGutenbergBpmn(previewRef.current, bpmnContent, renderMode)
                    .then((instance) => {
                        modelerRef.current = instance;
                        
                        if (isEditing) {
                            try {
                                instance.get("commandStack").clear();
                            } catch (e) {}
                        }
                    })
                    .catch((err) => console.error("Renderer error:", err));
            }, [bpmnContent, isEditing, mode]);

            // Save BPMN

            const onSaveBpmn = async () => {
                if (!mediaId || !modelerRef.current) return;
            
                setIsLoading(true);
            
                try {
                    const { xml } = await modelerRef.current.saveXML({ format: true });
            
                    setBpmnContent(xml);
            
                    const formData = new FormData();
                    formData.append("action", "moox_bpmn_save");
                    formData.append("mediaId", mediaId);
                    formData.append("bpmnContent", xml);
                    formData.append("nonce", mooxBpmnBlock.nonce);
            
                    const response = await fetch(mooxBpmnBlock.ajaxUrl, {
                        method: "POST",
                        body: formData,
                    });
            
                    const data = await response.json();
            
                    if (data.success) {
                        wp.data.dispatch("core/notices").createNotice(
                            "success",
                            __("BPMN file saved successfully", "moox-bpmn"),
                            { type: "snackbar" }
                        );
                        setIsEditing(false);
                    } else {
                        wp.data.dispatch("core/notices").createNotice(
                            "error",
                            __("Error saving BPMN file", "moox-bpmn"),
                            { type: "snackbar" }
                        );
                    }
                } catch (err) {
                    wp.data.dispatch("core/notices").createNotice(
                        "error",
                        __("Failed exporting BPMN", "moox-bpmn"),
                        { type: "snackbar" }
                    );
                    console.error(err);
                }
            
                setIsLoading(false);
            };
            

        const blockProps = useBlockProps({
            className: "moox-bpmn-block-editor",
        });

            return wp.element.createElement(
                "div",
                blockProps,
                // Inspector
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(
                        PanelBody,
                        { title: __("BPMN Settings", "moox-bpmn") },
                        wp.element.createElement(
                            VStack,
                            { spacing: 3 },
                            wp.element.createElement(SelectControl, {
                                label: __("Mode", "moox-bpmn"),
                                value: mode,
                                options: [
                                    { label: __("View Only", "moox-bpmn"), value: "view" },
                                    { label: __("Edit", "moox-bpmn"), value: "edit" },
                                ],
                                onChange: (v) => setAttributes({ mode: v }),
                            }),
                            wp.element.createElement(TextControl, {
                                label: __("Height", "moox-bpmn"),
                                value: height,
                                onChange: (v) => setAttributes({ height: v }),
                            })
                        )
                    )
                ),

                // No file
                !mediaId &&
                    wp.element.createElement(
                        Placeholder,
                        {
                            icon: "chart-line",
                            label: __("BPMN Viewer", "moox-bpmn"),
                            instructions: __("Select a BPMN (.bpmn or .xml) file", "moox-bpmn"),
                        },
                        wp.element.createElement(
                            MediaUploadCheck,
                            null,
                            wp.element.createElement(MediaUpload, {
                                onSelect: (file) => setAttributes({ mediaId: file.id }),
                                allowedTypes: ["application/xml", "text/xml"],
                                value: mediaId,
                                render: ({ open }) =>
                                    wp.element.createElement(Button, { variant: "primary", onClick: open }, __("Select BPMN File", "moox-bpmn"))
                            })
                        )
                    ),

                // File selected
                mediaId &&
                    wp.element.createElement(
                        "div",
                        { className: "moox-bpmn-preview" },
                        wp.element.createElement(
                            "div",
                            { className: "moox-bpmn-preview-header" },
                            wp.element.createElement(
                                "div",
                                { className: "moox-bpmn-file-info" },
                                wp.element.createElement("strong", null, media?.title?.rendered || media?.filename || __("Selected File", "moox-bpmn")),
                                mode === "edit" &&
                                    wp.element.createElement(Button, { variant: isEditing ? "primary" : "secondary", onClick: () => setIsEditing(!isEditing) }, isEditing ? __("Preview", "moox-bpmn") : __("Edit", "moox-bpmn"))
                            ),
                            wp.element.createElement(Button, { variant: "secondary", onClick: () => setAttributes({ mediaId: 0 }) }, __("Change File", "moox-bpmn"))
                        ),
                        wp.element.createElement(
                            "div",
                            { className: "moox-bpmn-preview-content", style: { height } },
                            isLoading ? wp.element.createElement(Spinner, null) : wp.element.createElement("div", { ref: previewRef, style: { width: "100%", height: "100%" } })
                        ),
                        isEditing && mode === "edit" &&
                            wp.element.createElement(
                                "div",
                                { className: "moox-bpmn-editor-toolbar" },
                                wp.element.createElement(Button, { variant: "primary", onClick: onSaveBpmn }, __("Save Changes", "moox-bpmn"))
                            )
                    )
            );
        },

        save: () => null,
    });
})();
