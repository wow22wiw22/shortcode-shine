import { useEffect, useState } from 'react';
import { X, Trash2, Power } from 'lucide-react';
import { WPMemory, getMemoriesWP, addMemoryWP, updateMemoryWP, deleteMemoryWP, toggleMemoryWP, getWPUserId, getWPPersonaId, isWPAdmin, isWordPress } from '@/lib/wp-api';
import { addCloudMemory, deleteCloudMemory, listCloudMemories, toggleCloudMemory, updateCloudMemory } from '@/lib/cloud-memory';
import { useAuth } from '@/hooks/useAuth';
import { toast } from 'sonner';

interface MemoryDrawerProps {
  open: boolean;
  onClose: () => void;
}

/**
 * Admin-only memory manager (v12.3 plugin feature).
 * Memory CRUD endpoints require `manage_options` + the admin nonce.
 */
export function MemoryDrawer({ open, onClose }: MemoryDrawerProps) {
  const [memories, setMemories] = useState<WPMemory[]>([]);
  const [loading, setLoading] = useState(false);
  const [draft, setDraft] = useState('');
  const { user } = useAuth();
  const wpMode = isWordPress();
  const userId = getWPUserId();

  const refresh = async () => {
    if (!wpMode && !user?.id) return;
    if (wpMode && !userId) return;
    setLoading(true);
    try {
      setMemories(wpMode ? await getMemoriesWP(userId) : await listCloudMemories(user.id));
    } catch (e: any) {
      toast.error(e.message || 'Failed to load memories');
    }
    setLoading(false);
  };

  useEffect(() => { if (open) refresh(); /* eslint-disable-next-line */ }, [open]);

  if (!open) return null;

  const add = async () => {
    const t = draft.trim();
    if (!t) return;
    try {
      if (wpMode) await addMemoryWP(userId, getWPPersonaId(), t);
      else if (user?.id) await addCloudMemory(user.id, t);
      setDraft('');
      refresh();
      toast.success('Memory saved');
    } catch (e: any) { toast.error(e.message); }
  };

  const isPreviewMock = typeof window !== 'undefined' && !!(window as any)?.versace22_chat?.ajaxurl?.includes('/wp-mock/');
  const canManage = userId > 0 && isWPAdmin();
  const canUsePreviewMemories = isPreviewMock && userId > 0;
  const canAdd = wpMode ? canManage || canUsePreviewMemories : !!user;

  return (
    <aside className="fixed top-0 right-0 z-50 h-dvh w-full sm:w-[340px] bg-card border-l border-border flex flex-col shadow-2xl animate-in slide-in-from-right duration-200">
      <header className="flex items-center justify-between px-4 py-4 border-b border-border shrink-0">
        <h3 className="text-sm font-semibold text-foreground">🧠 Memories</h3>
        <button onClick={onClose} className="p-2 rounded-md hover:bg-muted"><X className="w-4 h-4" /></button>
      </header>

      <div className="p-3 border-b border-border shrink-0 space-y-2">
        <div className="flex items-center gap-2">
          <input
            value={draft}
            onChange={(e) => setDraft(e.target.value)}
            placeholder="e.g. I prefer Python"
            className="flex-1 h-10 text-sm bg-muted/40 border border-border rounded-xl px-3 py-2 focus:outline-none focus:border-primary/40"
          />
          <button
            onClick={add}
            disabled={!draft.trim() || !canAdd}
            className="h-10 px-4 rounded-xl bg-primary text-primary-foreground text-sm font-medium hover:bg-primary/90 disabled:opacity-50"
          >
            + Add
          </button>
        </div>
        {!canAdd && (
          <p className="text-xs text-muted-foreground">
            {userId > 0
              ? 'Memories require an admin account (manage_options). Sign in as the WordPress admin to add memories.'
              : 'Sign in to WordPress to save memories.'}
          </p>
        )}
        <textarea
          value={draft}
          onChange={(e) => setDraft(e.target.value)}
          placeholder="e.g. The user prefers concise answers in Turkish."
          rows={3}
          className="hidden"
        />
      </div>

      <div className="flex-1 overflow-y-auto p-3 space-y-2">
        {loading && <p className="text-xs text-muted-foreground">Loading…</p>}
        {!loading && memories.length === 0 && (
          <p className="text-xs text-muted-foreground">No memories yet. Add one above.</p>
        )}
        {memories.map(m => (
          <div key={m.id} className={`rounded-md border border-border p-2.5 text-sm ${m.enabled ? '' : 'opacity-50'}`}>
            <textarea
              defaultValue={m.memory_text}
              onBlur={async (e) => {
                if (e.target.value !== m.memory_text) {
                  try {
                    if (wpMode) await updateMemoryWP(m.id, e.target.value);
                    else if (user?.id) await updateCloudMemory(user.id, String(m.id), e.target.value);
                    refresh();
                  }
                  catch (err: any) { toast.error(err.message); }
                }
              }}
              rows={2}
              className="w-full bg-transparent text-sm focus:outline-none resize-none"
            />
            <div className="flex justify-end gap-1 mt-1">
              <button
                onClick={async () => {
                  if (wpMode) await toggleMemoryWP(m.id);
                  else if (user?.id) await toggleCloudMemory(user.id, String(m.id));
                  refresh();
                }}
                title="Toggle"
                className="p-1.5 rounded hover:bg-muted text-muted-foreground"
              >
                <Power className="w-3.5 h-3.5" />
              </button>
              <button
                onClick={async () => {
                  if (wpMode) await deleteMemoryWP(m.id);
                  else if (user?.id) await deleteCloudMemory(user.id, String(m.id));
                  refresh();
                  toast.success('Deleted');
                }}
                title="Delete"
                className="p-1.5 rounded hover:bg-destructive/10 text-destructive"
              >
                <Trash2 className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        ))}
      </div>
    </aside>
  );
}
