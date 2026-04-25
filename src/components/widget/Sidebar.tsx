import { Crown, MessageSquarePlus, History, Trophy, Gift, User as UserIcon, LogOut, Search, Pin, Pencil, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ScrollArea } from "@/components/ui/scroll-area";
import { cn } from "@/lib/utils";
import { t } from "@/lib/widget-i18n";
import type { Conversation, Lang, User } from "@/lib/widget-types";
import { useMemo, useState } from "react";

type View = "chat" | "profile" | "leaderboard" | "refer";

type Props = {
  lang: Lang;
  user: User | null;
  view: View;
  onView: (v: View) => void;
  conversations: Conversation[];
  activeId: string | null;
  onSelect: (id: string) => void;
  onNewChat: () => void;
  onRename: (id: string, title: string) => void;
  onDelete: (id: string) => void;
  onPin: (id: string) => void;
  onSignIn: () => void;
  onSignOut: () => void;
};

export function WidgetSidebar({
  lang, user, view, onView, conversations, activeId,
  onSelect, onNewChat, onRename, onDelete, onPin, onSignIn, onSignOut,
}: Props) {
  const [q, setQ] = useState("");
  const [renaming, setRenaming] = useState<string | null>(null);
  const [draft, setDraft] = useState("");

  const filtered = useMemo(() => {
    const list = conversations.filter((c) =>
      c.title.toLowerCase().includes(q.toLowerCase()) ||
      c.messages.some((m) => m.content.toLowerCase().includes(q.toLowerCase()))
    );
    return [...list].sort((a, b) => {
      if (a.pinned !== b.pinned) return a.pinned ? -1 : 1;
      return b.updatedAt - a.updatedAt;
    });
  }, [conversations, q]);

  return (
    <aside className="flex h-full w-72 shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground">
      {/* Brand */}
      <div className="flex items-center gap-2 px-4 py-4 border-b border-sidebar-border">
        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-[image:var(--gradient-primary)] text-primary-foreground shadow-[var(--shadow-glow)]">
          <Crown className="h-5 w-5" />
        </div>
        <div>
          <div className="text-sm font-semibold">{t(lang, "appName")}</div>
          <div className="text-[10px] uppercase tracking-wider text-muted-foreground">v2 · preview</div>
        </div>
      </div>

      {/* New chat */}
      <div className="px-3 pt-3">
        <Button onClick={onNewChat} className="w-full justify-start gap-2 bg-[image:var(--gradient-primary)] text-primary-foreground hover:opacity-90">
          <MessageSquarePlus className="h-4 w-4" />
          {t(lang, "newChat")}
        </Button>
      </div>

      {/* Nav */}
      <nav className="px-2 pt-3 space-y-0.5">
        <NavItem icon={<History className="h-4 w-4" />} label={t(lang, "history")} active={view === "chat"} onClick={() => onView("chat")} />
        <NavItem icon={<UserIcon className="h-4 w-4" />} label={t(lang, "profile")} active={view === "profile"} onClick={() => onView("profile")} />
        <NavItem icon={<Trophy className="h-4 w-4" />} label={t(lang, "leaderboard")} active={view === "leaderboard"} onClick={() => onView("leaderboard")} />
        <NavItem icon={<Gift className="h-4 w-4" />} label={t(lang, "refer")} active={view === "refer"} onClick={() => onView("refer")} />
      </nav>

      {/* Search */}
      <div className="px-3 pt-4">
        <div className="relative">
          <Search className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
          <Input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder={t(lang, "search")}
            className="h-8 pl-8 bg-sidebar-accent border-sidebar-border text-xs"
          />
        </div>
      </div>

      {/* Conversations */}
      <ScrollArea className="flex-1 px-2 py-2 mt-1">
        {filtered.length === 0 && (
          <div className="px-3 py-8 text-center text-xs text-muted-foreground">{t(lang, "noChats")}</div>
        )}
        <ul className="space-y-0.5">
          {filtered.map((c) => (
            <li key={c.id}>
              <div
                className={cn(
                  "group flex items-center gap-1 rounded-md px-2 py-1.5 text-sm cursor-pointer transition-colors",
                  activeId === c.id ? "bg-sidebar-accent text-sidebar-accent-foreground" : "hover:bg-sidebar-accent/60"
                )}
                onClick={() => onSelect(c.id)}
              >
                {c.pinned && <Pin className="h-3 w-3 shrink-0 text-primary fill-primary" />}
                {renaming === c.id ? (
                  <Input
                    autoFocus
                    value={draft}
                    onChange={(e) => setDraft(e.target.value)}
                    onBlur={() => { onRename(c.id, draft || c.title); setRenaming(null); }}
                    onKeyDown={(e) => {
                      if (e.key === "Enter") { onRename(c.id, draft || c.title); setRenaming(null); }
                      if (e.key === "Escape") setRenaming(null);
                    }}
                    className="h-6 px-1 text-xs"
                    onClick={(e) => e.stopPropagation()}
                  />
                ) : (
                  <span className="flex-1 truncate">{c.title}</span>
                )}
                <div className="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                  <IconBtn title={c.pinned ? t(lang, "unpin") : t(lang, "pin")} onClick={(e) => { e.stopPropagation(); onPin(c.id); }}>
                    <Pin className="h-3 w-3" />
                  </IconBtn>
                  <IconBtn title={t(lang, "rename")} onClick={(e) => { e.stopPropagation(); setRenaming(c.id); setDraft(c.title); }}>
                    <Pencil className="h-3 w-3" />
                  </IconBtn>
                  <IconBtn title={t(lang, "delete")} onClick={(e) => { e.stopPropagation(); onDelete(c.id); }}>
                    <Trash2 className="h-3 w-3" />
                  </IconBtn>
                </div>
              </div>
            </li>
          ))}
        </ul>
      </ScrollArea>

      {/* User footer */}
      <div className="border-t border-sidebar-border p-3">
        {user ? (
          <div className="flex items-center gap-2">
            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-sidebar-accent text-lg">{user.avatar}</div>
            <div className="min-w-0 flex-1">
              <div className="truncate text-sm font-medium">{user.username}</div>
              <div className="truncate text-[10px] text-muted-foreground">{user.email}</div>
            </div>
            <IconBtn title={t(lang, "signOut")} onClick={onSignOut}>
              <LogOut className="h-3.5 w-3.5" />
            </IconBtn>
          </div>
        ) : (
          <Button onClick={onSignIn} variant="outline" className="w-full bg-sidebar-accent border-sidebar-border">
            {t(lang, "signIn")}
          </Button>
        )}
      </div>
    </aside>
  );
}

function NavItem({ icon, label, active, onClick }: { icon: React.ReactNode; label: string; active: boolean; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className={cn(
        "flex w-full items-center gap-2 rounded-md px-3 py-1.5 text-sm transition-colors",
        active ? "bg-sidebar-accent text-sidebar-accent-foreground" : "text-sidebar-foreground/80 hover:bg-sidebar-accent/60"
      )}
    >
      {icon}
      <span>{label}</span>
    </button>
  );
}

function IconBtn({ children, title, onClick }: { children: React.ReactNode; title: string; onClick: (e: React.MouseEvent) => void }) {
  return (
    <button
      title={title}
      onClick={onClick}
      className="flex h-6 w-6 items-center justify-center rounded text-muted-foreground hover:bg-sidebar-accent-foreground/10 hover:text-sidebar-foreground"
    >
      {children}
    </button>
  );
}