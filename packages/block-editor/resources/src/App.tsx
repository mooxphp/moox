import { useCreateBlockNote } from "@blocknote/react";
import { BlockNoteView } from "@blocknote/shadcn";
import "@blocknote/shadcn/style.css";

import { BlockNoteSchema, defaultBlockSpecs } from "@blocknote/core";
import { InspectorPanel } from "./editor/InspectorSidebarWeb";
import { EditorShellWeb } from "./editor/InspectorSidebarWeb";
import { EditorShellMail } from "./editor/InspectorSidebarMail";
import { TopBarWeb } from "./editor/TopBarWeb";
import { TopBarMail } from "./editor/TopBarMail";
import { WebBlocks } from "./blocks/WebBlocks";
import { MailBlocks } from "./blocks/MailBlocks";
import { useMemo, useState } from "react";

interface AppProps {
  mode?: "web" | "mail";
}

export function App({ mode = "web" }: AppProps) {
  const schema = BlockNoteSchema.create({
    blockSpecs: {
      ...defaultBlockSpecs,
      ...(mode === "web" ? WebBlocks : MailBlocks),
    },
  });

  const editor = useCreateBlockNote({
    schema,
    initialContent: mode === "mail" ? [
      {
        type: "paragraph",
        content: [{ type: "text", text: "This is the first paragraph of the brand new Moox Mail Editor. It's a new way to create content for your email templates. The Block Editor is developed using BlockNote, React, Tailwind, and ShadCN. There is TipTap behind the scenes. We also use Maizzle for email development.", styles: {} }],
      },
      {
        type: "heading",
        props: { level: 2 },
        content: [{ type: "text", text: "BlockNote and React 🚀", styles: {} }],
      },
    ] : [
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

  const [docTitle, setDocTitle] = useState<string>(mode === "mail" ? "Moox Mail Editor" : "Moox Block Editor");

  const pagePanels: InspectorPanel[] = useMemo(() => [
    {
      id: "document",
      label: mode === "mail" ? "Mail" : "Document",
      controls: [
        { id: "title", label: "Title", kind: "text", getValue: () => docTitle, setValue: setDocTitle },
      ],
    },
  ], [docTitle, mode]);

  const TopBarComponent = mode === "web" ? TopBarWeb : TopBarMail;
  const EditorShellComponent = mode === "web" ? EditorShellWeb : EditorShellMail;

  return (
    <div className="h-full min-h-screen">
      <TopBarComponent onSave={() => {}} onPublish={() => {}} onDelete={() => {}} />
      <EditorShellComponent pagePanels={pagePanels} blockPanels={[]} hasSelection={false}>
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
      </EditorShellComponent>
    </div>
  );
}
