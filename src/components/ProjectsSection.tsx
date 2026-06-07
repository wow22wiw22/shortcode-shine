import { useEffect, useState } from 'react';
import { ChevronDown, ChevronRight, FolderPlus, Folder, Trash2 } from 'lucide-react';
import {
  WPProject, getProjectsWP, createProjectWP, deleteProjectWP,
  isWordPress,
} from '@/lib/wp-api';
import { toast } from 'sonner';

/**
 * Sidebar section that lists Projects (v12.3 plugin feature).
 * Admin-only because the underlying CRUD endpoints require manage_options.
 */
interface ProjectsSectionProps {
  isLoggedIn?: boolean;
}

export function ProjectsSection({ isLoggedIn = false }: ProjectsSectionProps) {
  const [open, setOpen] = useState(true);
  const [projects, setProjects] = useState<WPProject[]>([]);
  const [name, setName] = useState('');
  const [instructions, setInstructions] = useState('');
  const [loading, setLoading] = useState(false);

  if (!isWordPress()) return null;

  const refresh = async () => {
    setLoading(true);
    try { setProjects(await getProjectsWP()); }
    catch (e: any) { toast.error(e.message || 'Failed to load projects'); }
    setLoading(false);
  };

  useEffect(() => {
    if (!isLoggedIn) {
      setProjects([]);
      return;
    }
    refresh();
  }, [isLoggedIn]);

  if (!isLoggedIn) return null;

  const handleCreate = async () => {
    const n = name.trim();
    if (!n) return;
    try {
      await createProjectWP(n, '', instructions.trim());
      setName('');
      setInstructions('');
      refresh();
      toast.success('Project created');
    } catch (e: any) { toast.error(e.message); }
  };

  const handleDelete = async (id: number) => {
    try {
      await deleteProjectWP(id);
      refresh();
    } catch (e: any) { toast.error(e.message); }
  };

  return (
    <div className="aicpp-projects-section px-3 mt-2">
      <div className="flex items-center justify-between px-1 mb-1">
        <button
          onClick={() => setOpen(!open)}
          className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground hover:text-foreground transition-colors"
        >
          {open ? <ChevronDown className="w-3 h-3" /> : <ChevronRight className="w-3 h-3" />}
          Projects
        </button>
        <button
          onClick={() => setOpen(true)}
          title="New project"
          className="p-1 rounded hover:bg-sidebar-accent transition-colors"
        >
          <FolderPlus className="w-3.5 h-3.5 text-muted-foreground" />
        </button>
      </div>

      {open && (
        <div className="space-y-0.5">
          <div className="mb-3 rounded-2xl border border-sidebar-border bg-sidebar-accent/60 p-2.5 space-y-2">
            <input
              value={name}
              onChange={(e) => setName(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && handleCreate()}
              placeholder="Project name"
              className="w-full px-3 py-2 text-xs bg-secondary rounded-xl border border-border focus:outline-none focus:border-primary/40 text-foreground placeholder:text-muted-foreground"
            />
            <input
              value={instructions}
              onChange={(e) => setInstructions(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && handleCreate()}
              placeholder="Project instructions"
              className="w-full px-3 py-2 text-xs bg-secondary rounded-xl border border-border focus:outline-none focus:border-primary/40 text-foreground placeholder:text-muted-foreground"
            />
            <button
              onClick={handleCreate}
              className="w-full rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90 transition-colors"
            >
              Create project
            </button>
          </div>

          {loading && <p className="px-2 text-[11px] text-muted-foreground">Loading…</p>}
          {!loading && projects.length === 0 && (
            <p className="px-2 text-[11px] text-muted-foreground">No projects yet.</p>
          )}
          {projects.map(p => (
            <div
              key={p.id}
              className="group flex items-center gap-2 px-2 py-1.5 rounded-md text-sm text-sidebar-foreground hover:bg-sidebar-accent/60 transition-colors"
            >
              <Folder className="w-3.5 h-3.5 text-primary shrink-0" />
              <span className="flex-1 truncate">{p.name}</span>
              <button
                onClick={() => handleDelete(p.id)}
                className="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-destructive/10 text-destructive transition-all"
                title="Delete project"
              >
                <Trash2 className="w-3 h-3" />
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
