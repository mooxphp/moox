import { useCreateBlockNote } from "@blocknote/react";
import { BlockNoteView } from "@blocknote/shadcn";
import "@blocknote/shadcn/style.css";

import {
  BlockNoteSchema,
  defaultBlockSpecs,
  filterSuggestionItems,
  insertOrUpdateBlock,
} from "@blocknote/core";

import {
  SuggestionMenuController,
  getDefaultReactSlashMenuItems,
} from "@blocknote/react";

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
  // Schema: H1 verbieten, H2–H6 erlauben
  const customHeadingSpec = {
    ...defaultBlockSpecs.heading,
    config: {
      ...defaultBlockSpecs.heading.config,
      propSchema: {
        ...defaultBlockSpecs.heading.config.propSchema,
        level: {
          ...defaultBlockSpecs.heading.config.propSchema.level,
          default: 2,
          values: [2, 3, 4, 5, 6] as const,
        },
      },
    },
  };

  const schema = BlockNoteSchema.create({
    blockSpecs: {
      ...defaultBlockSpecs,
      heading: customHeadingSpec,
      ...(mode === "web" ? WebBlocks : MailBlocks),
    },
  });

  const editor = useCreateBlockNote({
    schema,
    initialContent:
      mode === "mail"
        ? [
            { type: "paragraph", content: [{ type: "text", text: "This is the first paragraph of the brand new Moox Mail Editor. It's a new way to create content for your email templates. The Block Editor is developed using BlockNote, React, Tailwind, and ShadCN. There is TipTap behind the scenes. We also use Maizzle for email development.", styles: {} }] },
            { type: "heading", props: { level: 2 }, content: [{ type: "text", text: "BlockNote and React 🚀", styles: {} }] },
          ]
        : [
            { type: "paragraph", content: [{ type: "text", text: "This is the first paragraph of the brand new Moox Block Editor. It's a new way to create content for your website. The Block Editor is developed using BlockNote, React, Tailwind, and ShadCN. There is TipTap behind the scenes.", styles: {} }] },
            { type: "heading", props: { level: 2 }, content: [{ type: "text", text: "BlockNote and React 🚀", styles: {} }] },
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

  const isMac = typeof navigator !== "undefined" && /Mac|iP(hone|ad|od)/.test(navigator.platform);

  return (
    <div className="h-full min-h-screen">
      <TopBarComponent onSave={() => {}} onPublish={() => {}} onDelete={() => {}} />

      <EditorShellComponent pagePanels={pagePanels} blockPanels={[]} hasSelection={false}>
        <div className="pt-12 pr-12 pb-6 pl-16">
          <input
            className="mb-4 ml-2 w-full text-5xl font-semibold tracking-tight bg-transparent border-0 outline-none focus:ring-0 text-zinc-100 placeholder-zinc-500"
            placeholder="Add title"
            value={docTitle}
            onChange={(e) => setDocTitle(e.target.value)}
          />
        </div>

        <div
          className="min-h-[50vh] pl-5"
          onKeyDownCapture={(e) => {
            if (e.altKey && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
              const n = Number(e.key);
              if (n >= 2 && n <= 6) {
                e.preventDefault();
                insertOrUpdateBlock(editor, {
                  type: "heading",
                  props: { level: n as 2 | 3 | 4 | 5 | 6 },
                });
              }
            }
          }}
        >
          <BlockNoteView editor={editor} slashMenu={false}>
            <SuggestionMenuController
              triggerCharacter="/"
              getItems={async (query) => {
                const defaults = getDefaultReactSlashMenuItems(editor).filter((item) => {
                  const text = `${item.title ?? ""} ${(item.aliases ?? []).join(" ")}`.toLowerCase();
                  const isHeading = /(^|\s)h[1-6](\s|$)/.test(text) || /heading\s*[1-6]/.test(text);
                  return !isHeading;
                });

                const HBadge = ({ lvl }: { lvl: number }) => (
                  <span className="inline-flex justify-center items-center w-8 h-6 text-xs font-semibold rounded-md border border-zinc-700/60 bg-zinc-800/60">
                    H{lvl}
                  </span>
                );

                const headings = ([2, 3, 4, 5, 6] as const).map((lvl) => ({
                  title: `Heading ${lvl}`,
                  aliases: [`h${lvl}`, `heading ${lvl}`],
                  group: "Headings",
                  icon: <HBadge lvl={lvl} />,
                  subtext:
                    lvl === 2 ? "Key section heading" :
                    lvl === 3 ? "Subsection and group heading" :
                                 "Smaller subsection heading",
                  shortcut: isMac ? `⌥-${lvl}` : `Alt-${lvl}`,
                  onItemClick: () =>
                    insertOrUpdateBlock(editor, { type: "heading", props: { level: lvl } }),
                }));

                return filterSuggestionItems([...headings, ...defaults], query);
              }}
            />
          </BlockNoteView>
        </div>
      </EditorShellComponent>
    </div>
  );
}
