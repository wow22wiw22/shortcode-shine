import { useState } from "react";
import { Toaster } from "@/components/ui/sonner";
import { WidgetSidebar } from "./Sidebar";
import { ChatPanel } from "./ChatPanel";
import { AuthDialog } from "./AuthDialog";
import { ProfileView } from "./ProfileView";
import { LeaderboardView } from "./LeaderboardView";
import { ReferView } from "./ReferView";
import { SEED_CONVERSATIONS, SEED_USER, PERSONAS } from "@/lib/widget-data";
import type { Conversation, Lang, Message, User } from "@/lib/widget-types";

type View = "chat" | "profile" | "leaderboard" | "refer";

export function Widget() {
  const [user, setUser] = useState<User | null>(SEED_USER);
  const [authOpen, setAuthOpen] = useState(false);
  const [lang, setLang] = useState<Lang>("en");
  const [view, setView] = useState<View>("chat");
  const [notifications, setNotifications] = useState(false);
  const [conversations, setConversations] = useState<Conversation[]>(SEED_CONVERSATIONS);
  const [activeId, setActiveId] = useState<string | null>(SEED_CONVERSATIONS[0]?.id ?? null);

  const conversation = conversations.find((c) => c.id === activeId) ?? null;

  function newChat() {
    const personaId = PERSONAS[0].id;
    const c: Conversation = {
      id: `c_${Date.now()}`,
      title: "New conversation",
      personaId,
      pinned: false,
      updatedAt: Date.now(),
      messages: [],
    };
    setConversations((prev) => [c, ...prev]);
    setActiveId(c.id);
    setView("chat");
  }

  function appendMessage(m: Message) {
    if (!activeId) return;
    setConversations((prev) =>
      prev.map((c) => {
        if (c.id !== activeId) return c;
        const nextTitle = c.messages.length === 0 && m.role === "user" ? m.content.slice(0, 40) : c.title;
        return { ...c, messages: [...c.messages, m], updatedAt: Date.now(), title: nextTitle };
      })
    );
  }

  function pickPersona(id: string) {
    if (!activeId) {
      const c: Conversation = {
        id: `c_${Date.now()}`,
        title: "New conversation",
        personaId: id,
        pinned: false,
        updatedAt: Date.now(),
        messages: [],
      };
      setConversations((prev) => [c, ...prev]);
      setActiveId(c.id);
    } else {
      setConversations((prev) => prev.map((c) => (c.id === activeId ? { ...c, personaId: id } : c)));
    }
  }

  function rename(id: string, title: string) {
    setConversations((prev) => prev.map((c) => (c.id === id ? { ...c, title } : c)));
  }

  function del(id: string) {
    setConversations((prev) => prev.filter((c) => c.id !== id));
    if (activeId === id) setActiveId(null);
  }

  function pin(id: string) {
    setConversations((prev) => prev.map((c) => (c.id === id ? { ...c, pinned: !c.pinned } : c)));
  }

  const dir = lang === "ar" ? "rtl" : "ltr";

  return (
    <div dir={dir} className="dark flex h-screen w-full overflow-hidden bg-background text-foreground">
      <WidgetSidebar
        lang={lang}
        user={user}
        view={view}
        onView={(v) => setView(v)}
        conversations={conversations}
        activeId={activeId}
        onSelect={(id) => { setActiveId(id); setView("chat"); }}
        onNewChat={newChat}
        onRename={rename}
        onDelete={del}
        onPin={pin}
        onSignIn={() => setAuthOpen(true)}
        onSignOut={() => setUser(null)}
      />

      <main className="flex flex-1 flex-col min-w-0">
        {view === "chat" && (
          <ChatPanel
            lang={lang}
            setLang={setLang}
            conversation={conversation}
            onAppend={appendMessage}
            onPickPersona={pickPersona}
            notifications={notifications}
            setNotifications={setNotifications}
          />
        )}
        {view === "profile" && user && <ProfileView lang={lang} user={user} onUpdate={setUser} />}
        {view === "profile" && !user && (
          <EmptyState lang={lang} message="Please sign in to view your profile" onSignIn={() => setAuthOpen(true)} />
        )}
        {view === "leaderboard" && <LeaderboardView lang={lang} user={user} />}
        {view === "refer" && user && <ReferView lang={lang} user={user} />}
        {view === "refer" && !user && (
          <EmptyState lang={lang} message="Please sign in to see your referral code" onSignIn={() => setAuthOpen(true)} />
        )}
      </main>

      <AuthDialog open={authOpen} onOpenChange={setAuthOpen} lang={lang} onAuth={setUser} />
      <Toaster theme="dark" />
    </div>
  );
}

function EmptyState({ message, onSignIn }: { lang: Lang; message: string; onSignIn: () => void }) {
  return (
    <div className="flex flex-1 items-center justify-center p-8">
      <div className="text-center">
        <p className="mb-4 text-muted-foreground">{message}</p>
        <button onClick={onSignIn} className="rounded-md bg-[image:var(--gradient-primary)] px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
          Sign in
        </button>
      </div>
    </div>
  );
}