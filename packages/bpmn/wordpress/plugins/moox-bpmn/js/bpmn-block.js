import { registerBlockType } from "@wordpress/blocks";
import {
    MediaUpload,
    MediaUploadCheck,
    InspectorControls,
    useBlockProps,
} from "@wordpress/block-editor";
import {
    PanelBody,
    SelectControl,
    Button,
    TextControl,
    Placeholder,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useState, useEffect } from "@wordpress/element";
import { useSelect } from "@wordpress/data";

registerBlockType("moox/bpmn-viewer", {
    title: __("BPMN Viewer", "moox-bpmn"),
    icon: "chart-line",
    category: "media",
    description: __("Upload, view and edit BPMN 2.0 models", "moox-bpmn"),

    attributes: {
        mediaId: {
            type: "number",
            default: 0,
        },
        mode: {
            type: "string",
            default: "view",
        },
        height: {
            type: "string",
            default: "500px",
        },
    },

    edit: function (props) {
        const { attributes, setAttributes } = props;
        const { mediaId, mode, height } = attributes;

        const [bpmnContent, setBpmnContent] = useState("");
        const [isEditing, setIsEditing] = useState(false);

        const media = useSelect(
            (select) => {
                if (!mediaId) return null;
                return select("core").getMedia(mediaId);
            },
            [mediaId]
        );

        // Load BPMN content when media is selected
        useEffect(() => {
            if (media && media.source_url) {
                fetch(media.source_url)
                    .then((response) => response.text())
                    .then((content) => setBpmnContent(content))
                    .catch((error) =>
                        console.error("Error loading BPMN content:", error)
                    );
            }
        }, [media]);

        const onSelectMedia = (media) => {
            setAttributes({ mediaId: media.id });
        };

        const onSaveBpmn = () => {
            if (!mediaId || !bpmnContent) return;

            const formData = new FormData();
            formData.append("action", "moox_bpmn_save");
            formData.append("mediaId", mediaId);
            formData.append("bpmnContent", bpmnContent);
            formData.append("nonce", mooxBpmnBlock.nonce);

            fetch(mooxBpmnBlock.ajaxUrl, {
                method: "POST",
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert(__("BPMN file saved successfully", "moox-bpmn"));
                        setIsEditing(false);
                    } else {
                        alert(__("Error saving BPMN file", "moox-bpmn"));
                    }
                })
                .catch((error) => {
                    console.error("Error saving BPMN:", error);
                    alert(__("Error saving BPMN file", "moox-bpmn"));
                });
        };

        const blockProps = useBlockProps({
            className: "moox-bpmn-block-editor",
        });

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__("BPMN Settings", "moox-bpmn")}>
                        <SelectControl
                            label={__("Mode", "moox-bpmn")}
                            value={mode}
                            options={[
                                {
                                    label: __("View Only", "moox-bpmn"),
                                    value: "view",
                                },
                                {
                                    label: __("Edit", "moox-bpmn"),
                                    value: "edit",
                                },
                            ]}
                            onChange={(value) => setAttributes({ mode: value })}
                        />
                        <TextControl
                            label={__("Height", "moox-bpmn")}
                            value={height}
                            onChange={(value) =>
                                setAttributes({ height: value })
                            }
                            help={__(
                                "CSS height value (e.g., 500px, 50vh)",
                                "moox-bpmn"
                            )}
                        />
                    </PanelBody>
                </InspectorControls>

                {!mediaId ? (
                    <Placeholder
                        icon="chart-line"
                        label={__("BPMN Viewer", "moox-bpmn")}
                        instructions={__(
                            "Select a BPMN file to display",
                            "moox-bpmn"
                        )}
                    >
                        <MediaUploadCheck>
                            <MediaUpload
                                onSelect={onSelectMedia}
                                allowedTypes={["application/xml"]}
                                value={mediaId}
                                render={({ open }) => (
                                    <Button variant="primary" onClick={open}>
                                        {__("Select BPMN File", "moox-bpmn")}
                                    </Button>
                                )}
                            />
                        </MediaUploadCheck>
                    </Placeholder>
                ) : (
                    <div className="moox-bpmn-preview">
                        <div className="moox-bpmn-preview-header">
                            <div className="moox-bpmn-file-info">
                                <strong>
                                    {media?.title?.rendered || media?.filename}
                                </strong>
                                {mode === "edit" && (
                                    <Button
                                        variant={
                                            isEditing ? "primary" : "secondary"
                                        }
                                        onClick={() => setIsEditing(!isEditing)}
                                    >
                                        {isEditing
                                            ? __("Preview", "moox-bpmn")
                                            : __("Edit", "moox-bpmn")}
                                    </Button>
                                )}
                            </div>
                            <Button
                                variant="secondary"
                                onClick={() => setAttributes({ mediaId: 0 })}
                            >
                                {__("Change File", "moox-bpmn")}
                            </Button>
                        </div>

                        <div className="moox-bpmn-preview-content">
                            {bpmnContent ? (
                                <div
                                    id="moox-bpmn-editor-{mediaId}"
                                    className="moox-bpmn-editor"
                                    data-bpmn-content={bpmnContent}
                                    data-mode={isEditing ? "edit" : "view"}
                                />
                            ) : (
                                <div className="moox-bpmn-loading">
                                    {__("Loading BPMN content...", "moox-bpmn")}
                                </div>
                            )}
                        </div>

                        {isEditing && mode === "edit" && (
                            <div className="moox-bpmn-editor-toolbar">
                                <Button variant="primary" onClick={onSaveBpmn}>
                                    {__("Save Changes", "moox-bpmn")}
                                </Button>
                            </div>
                        )}
                    </div>
                )}
            </div>
        );
    },

    save: function () {
        return null; // Dynamic block
    },
});
