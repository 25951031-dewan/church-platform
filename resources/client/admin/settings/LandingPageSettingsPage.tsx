import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { CheckCircle, Plus, GripVertical, Trash2, ChevronDown, ChevronUp } from 'lucide-react';

interface LandingSection {
  id?: number;
  name: string;
  sort_order: number;
  is_visible: boolean;
  config: Record<string, any>;
}

const SECTION_TEMPLATES = [
  { name: 'hero-simple-centered', label: 'Hero - Simple Centered' },
  { name: 'hero-split-with-screenshot', label: 'Hero - Split with Screenshot' },
  { name: 'hero-with-background-image', label: 'Hero - Background Image' },
  { name: 'features-grid', label: 'Features Grid' },
  { name: 'feature-with-screenshot', label: 'Feature with Screenshot' },
  { name: 'cta-simple-centered', label: 'CTA - Simple Centered' },
  { name: 'pricing', label: 'Pricing Table' },
  { name: 'footer', label: 'Footer' },
];

function SectionAccordion({ section, index, onUpdate, onDelete, onMove }: {
  section: LandingSection;
  index: number;
  onUpdate: (data: Partial<LandingSection>) => void;
  onDelete: () => void;
  onMove: (direction: 'up' | 'down') => void;
}) {
  const [isOpen, setIsOpen] = useState(false);

  const template = SECTION_TEMPLATES.find(t => t.name === section.name);
  const label = template?.label || section.name;

  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl mb-3">
      <div className="flex items-center gap-3 p-4">
        <button type="button" className="text-gray-500 cursor-move hover:text-white">
          <GripVertical size={16} />
        </button>
        <button
          type="button"
          onClick={() => setIsOpen(!isOpen)}
          className="flex-1 flex items-center justify-between text-left"
        >
          <span className="text-sm font-medium text-white">{label}</span>
          {isOpen ? <ChevronUp size={16} className="text-gray-400" /> : <ChevronDown size={16} className="text-gray-400" />}
        </button>
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => onMove('up')}
            disabled={index === 0}
            className="text-xs text-gray-400 hover:text-white disabled:opacity-30"
          >
            ↑
          </button>
          <button
            type="button"
            onClick={() => onMove('down')}
            className="text-xs text-gray-400 hover:text-white disabled:opacity-30"
          >
            ↓
          </button>
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              checked={section.is_visible}
              onChange={e => onUpdate({ is_visible: e.target.checked })}
              className="w-4 h-4 rounded bg-[#0C0E12] border-white/10"
            />
            <span className="text-xs text-gray-400">Visible</span>
          </label>
          <button
            type="button"
            onClick={onDelete}
            className="text-red-400 hover:text-red-300"
          >
            <Trash2 size={14} />
          </button>
        </div>
      </div>
      {isOpen && (
        <div className="border-t border-white/5 p-4">
          <p className="text-xs text-gray-500 mb-3">
            Configure section settings here. Full editor coming soon.
          </p>
          <textarea
            value={JSON.stringify(section.config, null, 2)}
            onChange={e => {
              try {
                const config = JSON.parse(e.target.value);
                onUpdate({ config });
              } catch {}
            }}
            rows={6}
            className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-xs font-mono focus:outline-none focus:border-indigo-500 resize-none"
            placeholder='{"title": "Welcome", "subtitle": "Build amazing things"}'
          />
        </div>
      )}
    </div>
  );
}

export function LandingPageSettingsPage() {
  const qc = useQueryClient();
  const [sections, setSections] = useState<LandingSection[]>([]);
  const [saved, setSaved] = useState(false);
  const [showAddMenu, setShowAddMenu] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['landing-page-admin'],
    queryFn: () => apiClient.get<{ sections: LandingSection[] }>('landing-page-admin').then(r => r.data.sections),
  });

  useEffect(() => {
    if (data) setSections(data);
  }, [data]);

  const mutation = useMutation({
    mutationFn: (values: LandingSection[]) => apiClient.put('landing-page', { sections: values }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['landing-page-admin'] });
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    },
  });

  const addSection = (templateName: string) => {
    const newSection: LandingSection = {
      name: templateName,
      sort_order: sections.length,
      is_visible: true,
      config: {},
    };
    setSections([...sections, newSection]);
    setShowAddMenu(false);
  };

  const updateSection = (index: number, data: Partial<LandingSection>) => {
    const updated = [...sections];
    updated[index] = { ...updated[index], ...data };
    setSections(updated);
  };

  const deleteSection = (index: number) => {
    setSections(sections.filter((_, i) => i !== index));
  };

  const moveSection = (index: number, direction: 'up' | 'down') => {
    const newIndex = direction === 'up' ? index - 1 : index + 1;
    if (newIndex < 0 || newIndex >= sections.length) return;

    const updated = [...sections];
    [updated[index], updated[newIndex]] = [updated[newIndex], updated[index]];
    setSections(updated);
  };

  const handleSave = () => {
    const withOrder = sections.map((s, i) => ({ ...s, sort_order: i }));
    mutation.mutate(withOrder);
  };

  if (isLoading) return <div className="text-gray-400 text-sm">Loading…</div>;

  return (
    <div className="max-w-4xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">Landing Page Builder</h2>
        <div className="flex items-center gap-3">
          {saved && <span className="flex items-center gap-1 text-xs text-green-400"><CheckCircle size={13} /> Saved</span>}
          <button
            type="button"
            onClick={handleSave}
            disabled={mutation.isPending}
            className="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            {mutation.isPending ? 'Saving…' : 'Save changes'}
          </button>
        </div>
      </div>

      {mutation.isError && (
        <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
          Failed to save. Please try again.
        </div>
      )}

      <div className="mb-4">
        <div className="relative">
          <button
            type="button"
            onClick={() => setShowAddMenu(!showAddMenu)}
            className="flex items-center gap-2 px-4 py-2 bg-[#161920] border border-white/10 rounded-lg text-sm text-white hover:border-indigo-500 transition-colors"
          >
            <Plus size={14} />
            Add Section
          </button>
          {showAddMenu && (
            <>
              <div className="fixed inset-0 z-10" onClick={() => setShowAddMenu(false)} />
              <div className="absolute top-full left-0 mt-2 w-64 bg-[#161920] border border-white/10 rounded-lg shadow-xl z-20 max-h-80 overflow-y-auto">
                {SECTION_TEMPLATES.map(template => (
                  <button
                    key={template.name}
                    type="button"
                    onClick={() => addSection(template.name)}
                    className="w-full px-4 py-2 text-left text-sm text-white hover:bg-white/5 transition-colors first:rounded-t-lg last:rounded-b-lg"
                  >
                    {template.label}
                  </button>
                ))}
              </div>
            </>
          )}
        </div>
      </div>

      {sections.length === 0 ? (
        <div className="bg-[#161920] border border-white/5 rounded-xl p-8 text-center">
          <p className="text-gray-400 text-sm mb-2">No sections yet</p>
          <p className="text-gray-500 text-xs">Click "Add Section" to build your landing page</p>
        </div>
      ) : (
        <div>
          {sections.map((section, index) => (
            <SectionAccordion
              key={index}
              section={section}
              index={index}
              onUpdate={data => updateSection(index, data)}
              onDelete={() => deleteSection(index)}
              onMove={direction => moveSection(index, direction)}
            />
          ))}
        </div>
      )}
    </div>
  );
}
