import { useCreateBlockNote } from "@blocknote/react";
import { BlockNoteView } from "@blocknote/shadcn";
import "@blocknote/shadcn/style.css";

import { BlockNoteSchema, defaultBlockSpecs } from "@blocknote/core";
import { TwoColumnBlock } from "./blocks/TwoColumns";
import { EditorShell, InspectorPanel } from "./editor/InspectorSidebar";
import { useMemo, useState } from "react";
import { TopBar } from "./editor/TopBar";

const schema = BlockNoteSchema.create({
  blockSpecs: {
    ...defaultBlockSpecs,
    twoColumn: TwoColumnBlock,
  },
});

export function App() {
  const editor = useCreateBlockNote({
    schema,
    initialContent: [
      {
        type: "paragraph",
        content: [{ type: "text", text: "This is the first paragraph of the brand new Moox Block Editor. It's a new way to create content for your website. The Block Editor is developed using BlockNote, React, Tailwind, and ShadCN. There is TipTap behind the scenes.", styles: {} }],
      },
      {
        type: "heading",
        props: { level: 2 },
        content: [{ type: "text", text: "BlockNote and React 🚀", styles: {} }],
      },
    ],
  });

  const [docTitle, setDocTitle] = useState<string>("Moox Block Editor");
  const [slug, setSlug] = useState<string>("moox-block-editor");
  const [wideContent, setWideContent] = useState<boolean>(false);
  const [theme, setTheme] = useState<string>("light");
  const [opacity, setOpacity] = useState<number>(100);

  const pagePanels: InspectorPanel[] = useMemo(() => [
    {
      id: "document",
      label: "Document",
      controls: [
        { id: "title", label: "Title", kind: "text", getValue: () => docTitle, setValue: setDocTitle },
        { id: "slug", label: "Slug", kind: "text", getValue: () => slug, setValue: setSlug },
        { id: "theme", label: "Theme", kind: "select", options: [
          { label: "Light", value: "light" },
          { label: "Dark", value: "dark" },
        ], getValue: () => theme, setValue: setTheme },
      ],
    },
  ], [docTitle, slug, theme]);

  const blockPanels: InspectorPanel[] = useMemo(() => [
    {
      id: "layout",
      label: "Layout",
      controls: [
        { id: "wide", label: "Wide content", kind: "toggle", getValue: () => wideContent, setValue: setWideContent },
        { id: "opacity", label: "Opacity", kind: "range", min: 0, max: 100, getValue: () => opacity, setValue: setOpacity },
      ],
    },
  ], [wideContent, opacity]);

  const hasSelection = true;

  return (
    <div className="h-full min-h-screen">
      <TopBar onSave={() => {}} onPublish={() => {}} onDelete={() => {}} />
      <EditorShell pagePanels={pagePanels} blockPanels={blockPanels} hasSelection={hasSelection}>
        <div className="pt-12 pr-12 pb-6 pl-16">
          <input
            className="mb-4 ml-2 w-full text-5xl font-semibold tracking-tight bg-transparent border-0 outline-none focus:ring-0 text-zinc-100 placeholder-zinc-500"
            placeholder="Add title"
            value={docTitle}
            onChange={e => setDocTitle(e.target.value)}
          />

        </div>
        <div className="min-h-[50vh] pl-5">
            <BlockNoteView editor={editor} />
          </div>
      </EditorShell>
    </div>
  );
}
