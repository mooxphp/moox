import { useEffect, useRef, useState } from "react";
import type { JSX } from "react";

export type TopBarWebProps = {
  onSave?: () => void;
  onPublish?: () => void;
  onDelete?: () => void;
};

export function TopBarWeb({ onSave, onPublish, onDelete }: TopBarWebProps): JSX.Element {
  const [menuOpen, setMenuOpen] = useState<boolean>(false);
  const menuRef = useRef<HTMLDivElement | null>(null);
  const logoSrc = `${import.meta.env.BASE_URL}images/logo.png`;

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (!menuRef.current) return;
      if (!menuRef.current.contains(e.target as Node)) setMenuOpen(false);
    };
    window.addEventListener("click", handler);
    return () => window.removeEventListener("click", handler);
  }, []);

  return (
    <div className="sticky top-0 z-20 w-full h-16 border-b backdrop-blur border-zinc-800 bg-zinc-900/80">
      <div className="flex justify-between items-center px-6 h-full">
        <div className="flex gap-3 items-center">
          <img src={logoSrc} alt="Moox" className="w-auto h-[1.6rem]" />
        </div>
        <div className="flex gap-3 items-center">
          <button
            className="px-4 py-2 text-sm font-medium text-white bg-violet-500 rounded-md shadow-sm hover:bg-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-400"
            onClick={onSave}
          >
            Save
          </button>
          <button
            className="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-md shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400"
            onClick={onPublish}
          >
            Publish
          </button>
          <div className="relative" ref={menuRef}>
            <button
              className="px-3 py-2 text-sm rounded-md border shadow-sm border-zinc-800 bg-zinc-800 text-zinc-100 hover:bg-zinc-700"
              onClick={(e) => { e.stopPropagation(); setMenuOpen((v) => !v); }}
            >
              …
            </button>
            {menuOpen && (
              <div className="absolute right-0 p-1 mt-2 w-44 text-sm rounded-md border shadow-lg border-zinc-800 bg-zinc-900">
                <button
                  className="px-3 py-2 w-full text-left text-red-300 rounded hover:bg-zinc-800"
                  onClick={onDelete}
                >
                  Delete
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
