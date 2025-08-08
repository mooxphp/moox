import { createBlockSpec } from "@blocknote/core";

export const TwoColumnBlock = createBlockSpec(
  {
    type: "twoColumn",
    propSchema: {},
    content: "none",
    slots: ["left", "right"] as const,
  },
  {
    render: () => {
      const dom = document.createElement("div");
      dom.className = "grid grid-cols-2 gap-4 p-4 bg-gray-50 border";
      return { dom };
    },
  }
);
