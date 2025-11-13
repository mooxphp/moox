import React, { useCallback, useMemo, useRef, useState } from "react";
import type { JSX } from "react";

export type SidebarTab = "page" | "block";

export type TextControl = {
  id: string;
  label: string;
  kind: "text";
  getValue: () => string;
  setValue: (v: string) => void;
};

export type ToggleControl = {
  id: string;
  label: string;
  kind: "toggle";
  getValue: () => boolean;
  setValue: (v: boolean) => void;
};

export type SelectControl = {
  id: string;
  label: string;
  kind: "select";
  options: { label: string; value: string }[];
  getValue: () => string;
  setValue: (v: string) => void;
};

export type ColorControl = {
  id: string;
  label: string;
  kind: "color";
  getValue: () => string;
  setValue: (v: string) => void;
};

export type RangeControl = {
  id: string;
  label: string;
  kind: "range";
  min: number;
  max: number;
  step?: number;
  getValue: () => number;
  setValue: (v: number) => void;
};

export type InspectorControl =
  | TextControl
  | ToggleControl
  | SelectControl
  | ColorControl
  | RangeControl;

export type InspectorPanel = {
  id: string;
  label: string;
  controls: InspectorControl[];
};

export type InspectorSidebarWebProps = {
  pagePanels: InspectorPanel[];
  blockPanels: InspectorPanel[];
  hasSelection: boolean;
  onClose?: () => void;
};

export function EditorShellWeb(props: React.PropsWithChildren<InspectorSidebarWebProps>): JSX.Element {
  const { children, pagePanels, blockPanels, hasSelection, onClose } = props;
  const [sidebarOpen, setSidebarOpen] = useState<boolean>(true);
  const [activeTab, setActiveTab] = useState<SidebarTab>("page");
  const [sidebarWidth, setSidebarWidth] = useState<number>(380);
  const resizingRef = useRef<boolean>(false);
  const startXRef = useRef<number>(0);
  const startWidthRef = useRef<number>(0);
  const minWidth = 300;
  const maxWidth = 560;

  const onMouseMove = useCallback((e: MouseEvent) => {
    if (!resizingRef.current) return;
    const dx = startXRef.current - e.clientX;
    let next = startWidthRef.current + dx;
    if (next < minWidth) next = minWidth;
    if (next > maxWidth) next = maxWidth;
    setSidebarWidth(next);
  }, []);

  const onMouseUp = useCallback(() => {
    if (!resizingRef.current) return;
    resizingRef.current = false;
    window.removeEventListener("mousemove", onMouseMove);
    window.removeEventListener("mouseup", onMouseUp);
  }, [onMouseMove]);

  const beginResize = useCallback((e: React.MouseEvent<HTMLDivElement>) => {
    resizingRef.current = true;
    startXRef.current = e.clientX;
    startWidthRef.current = sidebarWidth;
    window.addEventListener("mousemove", onMouseMove);
    window.addEventListener("mouseup", onMouseUp);
  }, [onMouseMove, onMouseUp, sidebarWidth]);

  const tabTriggers = useMemo(() => (
    <div className="flex gap-1 p-1 rounded-md bg-zinc-800/60">
      <button
        className={
          "px-3 py-1 text-xs rounded-md transition-colors " +
          (activeTab === "page" ? "bg-zinc-900 shadow-sm" : "hover:bg-zinc-800")
        }
        onClick={() => setActiveTab("page")}
      >
        Page
      </button>
      <button
        className={
          "px-3 py-1 text-xs rounded-md transition-colors disabled:opacity-50 " +
          (activeTab === "block" ? "bg-zinc-900 shadow-sm" : "hover:bg-zinc-800")
        }
        onClick={() => hasSelection && setActiveTab("block")}
        disabled={!hasSelection}
      >
        Block
      </button>
    </div>
  ), [activeTab, hasSelection]);

  return (
    <div className="flex h-full">
      <div className="flex-1 min-w-0 h-full">
        {children}
      </div>
      {sidebarOpen && (
        <div className="relative border-l border-zinc-800 bg-zinc-900" style={{ width: sidebarWidth }}>
          <div className="flex justify-between items-center px-3 py-2 border-b backdrop-blur border-zinc-800 bg-zinc-900/80">
            {tabTriggers}
            <button className="px-2 py-1 text-xs rounded border border-zinc-800 hover:bg-zinc-800" onClick={() => { setSidebarOpen(false); onClose && onClose(); }}>Hide</button>
          </div>
          <div className="absolute top-0 left-0 w-1 h-full cursor-col-resize" onMouseDown={beginResize} />
          <div className="h-[calc(100%-41px)] overflow-auto p-3">
            {activeTab === "page" ? (
              <PanelsRenderer panels={pagePanels} />
            ) : (
              <PanelsRenderer panels={blockPanels} />
            )}
          </div>
        </div>
      )}
      {!sidebarOpen && (
        <button className="px-2 text-xs border-l border-zinc-800 hover:bg-zinc-800" onClick={() => setSidebarOpen(true)}>Show</button>
      )}
    </div>
  );
}

function PanelsRenderer({ panels }: { panels: InspectorPanel[] }): JSX.Element {
  const [open, setOpen] = useState<Record<string, boolean>>({});
  return (
    <div className="space-y-2 w-full">
      {panels.map(panel => (
        <div key={panel.id} className="rounded-md border border-zinc-800 bg-zinc-900">
          <button
            className="flex justify-between items-center px-3 py-2 w-full text-left"
            onClick={() => setOpen(prev => ({ ...prev, [panel.id]: !prev[panel.id] }))}
          >
            <span className="text-sm font-medium text-zinc-100">{panel.label}</span>
            <span className="text-xs text-zinc-400">{open[panel.id] ? "−" : "+"}</span>
          </button>
          {open[panel.id] && (
            <div className="px-3 pb-3 space-y-4">
              {panel.controls.map(control => (
                <InspectorControlRenderer key={control.id} control={control} />
              ))}
            </div>
          )}
        </div>
      ))}
    </div>
  );
}

function InspectorControlRenderer({ control }: { control: InspectorControl }): JSX.Element {
  if (control.kind === "text") {
    return (
      <div className="space-y-2">
        <label className="block text-xs tracking-wide uppercase text-zinc-400">{control.label}</label>
        <input
          className="px-2 py-1 w-full rounded border border-zinc-800 bg-zinc-900 text-zinc-100"
          value={control.getValue()}
          onChange={e => control.setValue(e.target.value)}
        />
      </div>
    );
  }
  if (control.kind === "toggle") {
    return (
      <label className="flex gap-2 items-center text-sm text-zinc-200">
        <input type="checkbox" checked={control.getValue()} onChange={e => control.setValue(e.target.checked)} />
        {control.label}
      </label>
    );
  }
  if (control.kind === "select") {
    return (
      <div className="space-y-2">
        <label className="block text-xs tracking-wide uppercase text-zinc-400">{control.label}</label>
        <select
          className="px-2 py-1 w-full rounded border border-zinc-800 bg-zinc-900 text-zinc-100"
          value={control.getValue()}
          onChange={e => control.setValue(e.target.value)}
        >
          {control.options.map(o => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>
      </div>
    );
  }
  if (control.kind === "color") {
    return (
      <div className="space-y-2">
        <label className="block text-xs tracking-wide uppercase text-zinc-400">{control.label}</label>
        <input className="p-0 w-10 h-6 rounded border border-zinc-800" type="color" value={control.getValue()} onChange={e => control.setValue(e.target.value)} />
      </div>
    );
  }
  return (
    <div className="space-y-2">
      <label className="block text-xs tracking-wide uppercase text-zinc-400">{(control as RangeControl).label}</label>
      <input
        className="w-full"
        type="range"
        min={(control as RangeControl).min}
        max={(control as RangeControl).max}
        step={(control as RangeControl).step ?? 1}
        value={(control as RangeControl).getValue()}
        onChange={e => (control as RangeControl).setValue(Number(e.target.value))}
      />
    </div>
  );
}
