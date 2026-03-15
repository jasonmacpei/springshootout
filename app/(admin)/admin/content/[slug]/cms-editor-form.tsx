"use client";

import { useState } from "react";

type EditorSection = {
  id?: string;
  heading: string;
  body: string;
  sortOrder: number;
};

type EditableSection = {
  key: string;
  heading: string;
  body: string;
};

function toEditableSection(section: EditorSection, index: number): EditableSection {
  return {
    key: section.id ?? `section-${index}`,
    heading: section.heading,
    body: section.body,
  };
}

function createBlankSection(index: number): EditableSection {
  return {
    key: `new-section-${index}-${Date.now()}`,
    heading: "",
    body: "",
  };
}

export function CmsEditorFormSections({ initialSections }: { initialSections: EditorSection[] }) {
  const [sections, setSections] = useState<EditableSection[]>(() => initialSections.map(toEditableSection));

  function updateSection(key: string, field: "heading" | "body", value: string) {
    setSections((current) =>
      current.map((section) => (section.key === key ? { ...section, [field]: value } : section)),
    );
  }

  function addSection() {
    setSections((current) => [...current, createBlankSection(current.length)]);
  }

  function removeSection(key: string) {
    setSections((current) => current.filter((section) => section.key !== key));
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-4">
        <p className="text-sm text-[#c7d5eb]">Add, remove, and reorder copy by editing the sections below.</p>
        <button
          className="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/16"
          onClick={addSection}
          type="button"
        >
          Add section
        </button>
      </div>
      {sections.length ? (
        sections.map((section, index) => (
          <div className="rounded-3xl border border-white/12 bg-[#121d31] p-4" key={section.key}>
            <div className="flex items-center justify-between gap-4">
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-[#9fb2ce]">Section {index + 1}</p>
              <button
                className="text-sm font-semibold text-rose-200 transition hover:text-rose-100"
                onClick={() => removeSection(section.key)}
                type="button"
              >
                Delete section
              </button>
            </div>
            <div className="mt-4 grid gap-4">
              <label className="grid gap-2 text-sm font-medium text-white">
                Heading
                <input
                  className="rounded-2xl border border-white/12 bg-[#0d1525] px-4 py-3 text-white"
                  name="sectionHeading"
                  onChange={(event) => updateSection(section.key, "heading", event.target.value)}
                  value={section.heading}
                />
              </label>
              <label className="grid gap-2 text-sm font-medium text-white">
                Body
                <textarea
                  className="min-h-32 rounded-2xl border border-white/12 bg-[#0d1525] px-4 py-3 text-white"
                  name="sectionBody"
                  onChange={(event) => updateSection(section.key, "body", event.target.value)}
                  value={section.body}
                />
              </label>
            </div>
          </div>
        ))
      ) : (
        <div className="rounded-3xl border border-dashed border-white/16 bg-white/5 px-5 py-6 text-sm text-[#c7d5eb]">
          No sections yet. Use <strong>Add section</strong> to create the first entry.
        </div>
      )}
    </div>
  );
}
