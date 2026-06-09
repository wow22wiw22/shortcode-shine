import { useEffect, useState } from 'react';
import { ArrowLeft, FileText, Search, FolderKanban, Users, Volume2, Trash2, Plus, Save, Paperclip } from 'lucide-react';
import { toast } from 'sonner';
import {
  can,
  listArtifactsWP, saveArtifactWP, getArtifactWP, deleteArtifactWP,
  searchMessagesWP,
  speakWP,
  getProjectsWP, updateProjectWP, attachProjectFileWP, detachProjectFileWP,
  savePersonaWP, deletePersonaWP, getPersonaWP, assignPersonaWP, unassignPersonaWP,
  bulkAssignPersonaWP, getUserPersonasWP, getPersonaUsersWP, searchUsersWP,
  getMyPersonasFromWP,
  WPProject,
} from '@/lib/wp-api';

type Tab = 'artifacts' | 'search' | 'projects' | 'personas';

export function StudioView({ onBackToChat }: { onBackToChat: () => void }) {
  const [tab, setTab] = useState<Tab>('artifacts');
  const tabs: Array<{ id: Tab; label: string; icon: any; show: boolean }> = [
    { id: 'artifacts', label: 'Artifacts', icon: FileText, show: can('artifacts') },
    { id: 'search',    label: 'Search',    icon: Search,   show: true },
    { id: 'projects',  label: 'Projects',  icon: FolderKanban, show: can('create_project') },
    { id: 'personas',  label: 'Persona Studio', icon: Users, show: can('admin') },
  ];
  const visible = tabs.filter((t) => t.show);
  const current = visible.find((t) => t.id === tab) ? tab : visible[0]?.id;

  return (
    <div className="flex-1 flex flex-col overflow-hidden">
      <div className="px-4 py-3 border-b border-border flex items-center gap-3">
        <button onClick={onBackToChat} className="p-1.5 rounded-lg hover:bg-muted">
          <ArrowLeft className="w-4 h-4" />
        </button>
        <h2 className="text-lg font-bold text-foreground">Studio</h2>
      </div>
      <div className="px-4 py-2 border-b border-border flex gap-2 overflow-x-auto">
        {visible.map((t) => (
          <button
            key={t.id}
            onClick={() => setTab(t.id)}
            className={`px-3 py-1.5 rounded-lg text-sm flex items-center gap-1.5 whitespace-nowrap ${
              current === t.id ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80'
            }`}
          >
            <t.icon className="w-3.5 h-3.5" />
            {t.label}
          </button>
        ))}
      </div>
      <div className="flex-1 overflow-y-auto px-4 py-4">
        {current === 'artifacts' && <ArtifactsTab />}
        {current === 'search'    && <SearchTab />}
        {current === 'projects'  && <ProjectsTab />}
        {current === 'personas'  && <PersonaStudioTab />}
      </div>
    </div>
  );
}

/* -------------------- Artifacts -------------------- */
function ArtifactsTab() {
  const [items, setItems] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [title, setTitle] = useState('');
  const [type, setType] = useState('markdown');
  const [content, setContent] = useState('');
  const [openId, setOpenId] = useState<number | null>(null);
  const [openBody, setOpenBody] = useState<any>(null);

  const reload = async () => {
    setLoading(true);
    try { const d = await listArtifactsWP(); setItems(d?.artifacts || d || []); }
    catch (e: any) { toast.error(e?.message || 'Failed to list artifacts'); }
    finally { setLoading(false); }
  };
  useEffect(() => { reload(); }, []);

  const save = async () => {
    if (!title.trim() || !content.trim()) return toast.error('Title and content required');
    try { await saveArtifactWP({ title, type, content }); toast.success('Saved'); setTitle(''); setContent(''); reload(); }
    catch (e: any) { toast.error(e?.message || 'Save failed'); }
  };
  const open = async (id: number) => {
    try { const d = await getArtifactWP(id); setOpenId(id); setOpenBody(d?.artifact || d); }
    catch (e: any) { toast.error(e?.message || 'Load failed'); }
  };
  const remove = async (id: number) => {
    try { await deleteArtifactWP(id); toast.success('Deleted'); if (openId === id) { setOpenId(null); setOpenBody(null); } reload(); }
    catch (e: any) { toast.error(e?.message || 'Delete failed'); }
  };

  return (
    <div className="space-y-4">
      <div className="space-y-2 p-3 rounded-xl border border-border bg-card">
        <p className="text-sm font-semibold">New artifact</p>
        <input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Title"
          className="w-full px-3 py-2 rounded-lg bg-background border border-border text-sm" />
        <select value={type} onChange={(e) => setType(e.target.value)}
          className="w-full px-3 py-2 rounded-lg bg-background border border-border text-sm">
          {['markdown','html','svg','react','css','js'].map((t) => <option key={t}>{t}</option>)}
        </select>
        <textarea value={content} onChange={(e) => setContent(e.target.value)} rows={4} placeholder="Content"
          className="w-full px-3 py-2 rounded-lg bg-background border border-border text-sm font-mono" />
        <button onClick={save} className="px-3 py-2 rounded-lg bg-primary text-primary-foreground text-sm font-semibold inline-flex items-center gap-1.5">
          <Save className="w-3.5 h-3.5" /> Save artifact
        </button>
      </div>
      <div>
        <p className="text-sm font-semibold mb-2">{loading ? 'Loading…' : `${items.length} saved`}</p>
        <ul className="space-y-2">
          {items.map((a: any) => (
            <li key={a.id} className="px-3 py-2 rounded-lg border border-border bg-card flex items-center gap-2">
              <FileText className="w-4 h-4 text-primary" />
              <button className="flex-1 text-left text-sm truncate" onClick={() => open(a.id)}>{a.title || `Artifact #${a.id}`}</button>
              <span className="text-xs text-muted-foreground">{a.type}</span>
              <button onClick={() => remove(a.id)} className="p-1 rounded hover:bg-muted"><Trash2 className="w-3.5 h-3.5" /></button>
            </li>
          ))}
        </ul>
        {openBody && (
          <pre className="mt-3 p-3 rounded-lg bg-muted text-xs overflow-auto max-h-64">
            {typeof openBody === 'string' ? openBody : JSON.stringify(openBody, null, 2)}
          </pre>
        )}
      </div>
    </div>
  );
}

/* -------------------- Search + TTS -------------------- */
function SearchTab() {
  const [q, setQ] = useState('');
  const [results, setResults] = useState<any[]>([]);
  const [busy, setBusy] = useState(false);
  const [ttsText, setTtsText] = useState('Hello from VERSACE22');

  const run = async () => {
    if (!q.trim()) return;
    setBusy(true);
    try { const d = await searchMessagesWP(q); setResults(d?.results || d?.messages || d || []); }
    catch (e: any) { toast.error(e?.message || 'Search failed'); }
    finally { setBusy(false); }
  };
  const speak = async () => {
    try {
      const audio = await speakWP(ttsText);
      if (audio) new Audio(audio.startsWith('data:') ? audio : `data:audio/mpeg;base64,${audio}`).play();
    } catch (e: any) { toast.error(e?.message || 'TTS failed'); }
  };

  return (
    <div className="space-y-5">
      <div>
        <p className="text-sm font-semibold mb-2">Search messages</p>
        <div className="flex gap-2">
          <input value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && run()}
            placeholder="Search across your conversations"
            className="flex-1 px-3 py-2 rounded-lg bg-background border border-border text-sm" />
          <button onClick={run} disabled={busy} className="px-3 py-2 rounded-lg bg-primary text-primary-foreground text-sm">
            {busy ? '…' : 'Search'}
          </button>
        </div>
        <ul className="mt-3 space-y-2">
          {results.map((r: any, i: number) => (
            <li key={i} className="p-2 rounded-lg border border-border bg-card text-sm">
              <span className="text-xs text-muted-foreground mr-2">{r.role || ''}</span>
              {r.content || r.text || JSON.stringify(r)}
            </li>
          ))}
        </ul>
      </div>
      {can('voice') && (
        <div className="space-y-2 p-3 rounded-xl border border-border bg-card">
          <p className="text-sm font-semibold flex items-center gap-2"><Volume2 className="w-4 h-4" /> Text-to-Speech</p>
          <textarea value={ttsText} onChange={(e) => setTtsText(e.target.value)} rows={2}
            className="w-full px-3 py-2 rounded-lg bg-background border border-border text-sm" />
          <button onClick={speak} className="px-3 py-2 rounded-lg bg-primary text-primary-foreground text-sm">
            Speak
          </button>
        </div>
      )}
    </div>
  );
}

/* -------------------- Projects (update/attach/detach) -------------------- */
function ProjectsTab() {
  const [projects, setProjects] = useState<WPProject[]>([]);
  const [editing, setEditing] = useState<WPProject | null>(null);

  const reload = async () => {
    try { setProjects(await getProjectsWP()); }
    catch (e: any) { toast.error(e?.message || 'Failed to load projects'); }
  };
  useEffect(() => { reload(); }, []);

  const save = async () => {
    if (!editing) return;
    try {
      await updateProjectWP(editing.id, {
        name: editing.name, description: editing.description, custom_instructions: editing.custom_instructions,
      });
      toast.success('Project updated'); setEditing(null); reload();
    } catch (e: any) { toast.error(e?.message || 'Update failed'); }
  };

  const onAttach = async (projectId: number, file: File) => {
    try { await attachProjectFileWP(projectId, file); toast.success('File attached'); reload(); }
    catch (e: any) { toast.error(e?.message || 'Attach failed'); }
  };
  const onDetach = async (projectId: number, fileId: number) => {
    try { await detachProjectFileWP(projectId, fileId); toast.success('Detached'); reload(); }
    catch (e: any) { toast.error(e?.message || 'Detach failed'); }
  };

  return (
    <div className="space-y-3">
      {projects.length === 0 && <p className="text-sm text-muted-foreground">No projects yet.</p>}
      {projects.map((p) => (
        <div key={p.id} className="p-3 rounded-xl border border-border bg-card space-y-2">
          {editing?.id === p.id ? (
            <>
              <input value={editing.name} onChange={(e) => setEditing({ ...editing, name: e.target.value })}
                className="w-full px-2 py-1.5 rounded bg-background border border-border text-sm" />
              <textarea value={editing.description} onChange={(e) => setEditing({ ...editing, description: e.target.value })}
                placeholder="Description" rows={2}
                className="w-full px-2 py-1.5 rounded bg-background border border-border text-sm" />
              <textarea value={editing.custom_instructions} onChange={(e) => setEditing({ ...editing, custom_instructions: e.target.value })}
                placeholder="Custom instructions" rows={2}
                className="w-full px-2 py-1.5 rounded bg-background border border-border text-sm" />
              <div className="flex gap-2">
                <button onClick={save} className="px-3 py-1.5 rounded bg-primary text-primary-foreground text-sm">Save</button>
                <button onClick={() => setEditing(null)} className="px-3 py-1.5 rounded bg-muted text-sm">Cancel</button>
              </div>
            </>
          ) : (
            <>
              <div className="flex items-center justify-between">
                <p className="font-semibold text-sm">{p.name}</p>
                <button onClick={() => setEditing(p)} className="text-xs text-primary">Edit</button>
              </div>
              {p.description && <p className="text-xs text-muted-foreground">{p.description}</p>}
              <label className="flex items-center gap-2 text-xs cursor-pointer text-primary">
                <Paperclip className="w-3.5 h-3.5" /> Attach file
                <input type="file" className="hidden" onChange={(e) => e.target.files?.[0] && onAttach(p.id, e.target.files[0])} />
              </label>
              {(p as any).files?.map?.((f: any) => (
                <div key={f.id} className="flex items-center justify-between text-xs">
                  <span>{f.name}</span>
                  <button onClick={() => onDetach(p.id, f.id)} className="text-destructive">Remove</button>
                </div>
              ))}
            </>
          )}
        </div>
      ))}
    </div>
  );
}

/* -------------------- Persona Studio (admin only) -------------------- */
function PersonaStudioTab() {
  const [personas, setPersonas] = useState<any[]>([]);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [form, setForm] = useState({ id: 0, name: '', description: '', model: 'gpt-4', visibility: 'private' as 'public'|'private', system_prompt: '' });
  const [userQuery, setUserQuery] = useState('');
  const [users, setUsers] = useState<any[]>([]);
  const [personaUsers, setPersonaUsers] = useState<any[]>([]);
  const [userPersonas, setUserPersonas] = useState<any[]>([]);

  const reload = async () => {
    const { personas: list } = await getMyPersonasFromWP();
    setPersonas(list);
  };
  useEffect(() => { reload(); }, []);

  const loadPersona = async (id: number) => {
    setSelectedId(id);
    try {
      const d = await getPersonaWP(id);
      const p = d?.persona || d || {};
      setForm({
        id, name: p.name || '', description: p.description || '', model: p.model || 'gpt-4',
        visibility: (p.visibility as any) || 'private', system_prompt: p.system_prompt || '',
      });
      const pu = await getPersonaUsersWP(id);
      setPersonaUsers(pu?.users || []);
    } catch (e: any) { toast.error(e?.message || 'Load failed'); }
  };
  const save = async () => {
    try { const r = await savePersonaWP(form); toast.success('Saved'); reload(); if (r?.id) setSelectedId(r.id); }
    catch (e: any) { toast.error(e?.message || 'Save failed'); }
  };
  const remove = async (id: number) => {
    try { await deletePersonaWP(id); toast.success('Deleted'); reload(); if (selectedId === id) setSelectedId(null); }
    catch (e: any) { toast.error(e?.message || 'Delete failed'); }
  };
  const search = async () => {
    try { const d = await searchUsersWP(userQuery); setUsers(d?.users || d || []); }
    catch (e: any) { toast.error(e?.message || 'Search failed'); }
  };
  const assign = async (uid: number) => {
    if (!selectedId) return;
    try { await assignPersonaWP(selectedId, uid); toast.success('Assigned'); loadPersona(selectedId); }
    catch (e: any) { toast.error(e?.message || 'Assign failed'); }
  };
  const unassign = async (uid: number) => {
    if (!selectedId) return;
    try { await unassignPersonaWP(selectedId, uid); toast.success('Unassigned'); loadPersona(selectedId); }
    catch (e: any) { toast.error(e?.message || 'Unassign failed'); }
  };
  const bulk = async () => {
    if (!selectedId || users.length === 0) return;
    try { await bulkAssignPersonaWP(selectedId, users.map((u) => u.id || u.user_id)); toast.success('Bulk assigned'); loadPersona(selectedId); }
    catch (e: any) { toast.error(e?.message || 'Bulk failed'); }
  };
  const loadUserPersonas = async (uid: number) => {
    try { const d = await getUserPersonasWP(uid); setUserPersonas(d?.personas || []); }
    catch (e: any) { toast.error(e?.message || 'Failed'); }
  };

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-3">
        <div className="space-y-1">
          <p className="text-sm font-semibold">Personas</p>
          <ul className="space-y-1 max-h-48 overflow-y-auto">
            {personas.map((p) => (
              <li key={p.id} className="flex items-center gap-1">
                <button onClick={() => loadPersona(p.id)}
                  className={`flex-1 text-left px-2 py-1.5 rounded text-sm ${selectedId === p.id ? 'bg-primary text-primary-foreground' : 'bg-muted'}`}>
                  {p.name}
                </button>
                <button onClick={() => remove(p.id)} className="p-1 text-destructive hover:bg-muted rounded">
                  <Trash2 className="w-3 h-3" />
                </button>
              </li>
            ))}
          </ul>
          <button onClick={() => { setSelectedId(null); setForm({ id: 0, name: '', description: '', model: 'gpt-4', visibility: 'private', system_prompt: '' }); }}
            className="text-xs text-primary inline-flex items-center gap-1"><Plus className="w-3 h-3" /> New persona</button>
        </div>
        <div className="space-y-2">
          <p className="text-sm font-semibold">{form.id ? `Edit #${form.id}` : 'New persona'}</p>
          <input placeholder="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })}
            className="w-full px-2 py-1.5 rounded bg-background border border-border text-sm" />
          <input placeholder="Model" value={form.model} onChange={(e) => setForm({ ...form, model: e.target.value })}
            className="w-full px-2 py-1.5 rounded bg-background border border-border text-sm" />
          <select value={form.visibility} onChange={(e) => setForm({ ...form, visibility: e.target.value as any })}
            className="w-full px-2 py-1.5 rounded bg-background border border-border text-sm">
            <option value="private">private</option><option value="public">public</option>
          </select>
          <textarea placeholder="Description" rows={2} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })}
            className="w-full px-2 py-1.5 rounded bg-background border border-border text-sm" />
          <textarea placeholder="System prompt" rows={3} value={form.system_prompt} onChange={(e) => setForm({ ...form, system_prompt: e.target.value })}
            className="w-full px-2 py-1.5 rounded bg-background border border-border text-sm" />
          <button onClick={save} className="px-3 py-1.5 rounded bg-primary text-primary-foreground text-sm">Save persona</button>
        </div>
      </div>

      {selectedId && (
        <div className="space-y-3 p-3 rounded-xl border border-border bg-card">
          <p className="text-sm font-semibold">Assignments for persona #{selectedId}</p>
          <div className="flex gap-2">
            <input value={userQuery} onChange={(e) => setUserQuery(e.target.value)} placeholder="Search users"
              className="flex-1 px-2 py-1.5 rounded bg-background border border-border text-sm" />
            <button onClick={search} className="px-3 py-1.5 rounded bg-muted text-sm">Find</button>
            <button onClick={bulk} className="px-3 py-1.5 rounded bg-primary text-primary-foreground text-sm">Bulk assign</button>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <p className="text-xs text-muted-foreground mb-1">Search results</p>
              <ul className="space-y-1 max-h-40 overflow-y-auto">
                {users.map((u: any) => (
                  <li key={u.id || u.user_id} className="flex items-center justify-between text-xs px-2 py-1 bg-muted rounded">
                    <button onClick={() => loadUserPersonas(u.id || u.user_id)} className="truncate text-left">
                      {u.display_name || u.user_login || u.email}
                    </button>
                    <button onClick={() => assign(u.id || u.user_id)} className="text-primary">+</button>
                  </li>
                ))}
              </ul>
            </div>
            <div>
              <p className="text-xs text-muted-foreground mb-1">Currently assigned</p>
              <ul className="space-y-1 max-h-40 overflow-y-auto">
                {personaUsers.map((u: any) => (
                  <li key={u.id || u.user_id} className="flex items-center justify-between text-xs px-2 py-1 bg-muted rounded">
                    <span className="truncate">{u.display_name || u.user_login || u.email}</span>
                    <button onClick={() => unassign(u.id || u.user_id)} className="text-destructive">×</button>
                  </li>
                ))}
              </ul>
            </div>
          </div>
          {userPersonas.length > 0 && (
            <div className="text-xs">
              <p className="text-muted-foreground mb-1">Personas for selected user:</p>
              <p className="text-foreground">{userPersonas.map((p: any) => p.name).join(', ')}</p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}