import { useCreateBlockNote } from "@blocknote/react";
import { BlockNoteView } from "@blocknote/shadcn";
import "@blocknote/shadcn/style.css";

import { BlockNoteSchema, defaultBlockSpecs } from "@blocknote/core";
import { TwoColumnBlock } from "./blocks/TwoColumns";

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
        type: "heading",
        props: { level: 2 },
        content: [{ type: "text", text: "Moox Block Editor 🚀", styles: {} }],
      },
      {
        type: "paragraph",
        content: [{ type: "text", text: "Tailwind v4 + shadcn + BlockNote.", styles: {} }],
      },
    ],
  });

  return <BlockNoteView editor={editor} />;
}
