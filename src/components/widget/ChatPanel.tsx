import { useEffect, useRef, useState } from "react";
import { Send, Volume2, VolumeX, Download, Bell, BellOff, Languages } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { PERSONAS, mockReply } from "@/lib/widget-data";
import { PersonaGallery } from "./PersonaGallery";
import { t } from "@/lib/widget-i18n";
import type { Conversation, Lang, Message } from "@/lib/widget-types";
import { cn } from "@/lib/utils";
import { toast } from "sonner";
import { aicpp, isOnline } from "@/lib/aicpp";

type Props = {
  lang: Lang;
  setLang: (l: Lang) => void;
  conversation: Conversation | null;
  onAppend: (m: Message) => void;
  onPickPersona: (id: string) => void;
  notifications: boolean;
  setNotifications: (b: boolean) => void;
};

export function ChatPanel({ lang, setLang, conversation, onAppend, onPickPersona, notifications, setNotifications }: Props) {
  const [input, setInput] = useState("");
  const [busy, setBusy] = useState(false);
  const [speakingId, setSpeakingId] = useState<string | null>(null);
  const scrollerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    scrollerRef.current?.scrollTo({ top: scrollerRef.current.scrollHeight, behavior: "smooth" });
  }, [conversation?.messages.length]);

  const persona = conversation ? PERSONAS.find((p) => p.id === conversation.personaId) : null;

  async function send() {
    if (!input.trim() || !conversation || busy) return;
    const text = input.trim();
    const msg: Message = { id: `m_${Date.now()}`, role: "user", content: text, ts: Date.now() };
    onAppend(msg);
    setInput("");
    setBusy(true);

    let replyText: string;
    if (isOnline()) {
      const res = await aicpp<{ reply: string; message?: string }>("aicpp_send_message", {
        conversation_id: conversation.id,
        persona_id: conversation.personaId,
        message: text,
      });
      replyText = res.ok
        ? (res.data.reply ?? res.data.message ?? "")
        : `⚠️ ${res.error}`;
    } else {
      await new Promise((r) => setTimeout(r, 700));
      replyText = mockReply(conversation.personaId, text);
    }

    const reply: Message = {
      id: `m_${Date.now() + 1}`,
      role: "assistant",
      content: replyText,
      ts: Date.now(),
    };
    onAppend(reply);
    setBusy(false);
    if (notifications && document.hidden && "Notification" in window && Notification.permission === "granted") {
      new Notification(`${persona?.name ?? "AI"} replied`, { body: reply.content.slice(0, 80) });
    }
  }

  async function speak(m: Message) {
    if (!("speechSynthesis" in window)) {
      toast.error("Text-to-speech not supported in this browser");
      return;
    }
    if (speakingId === m.id) {
      window.speechSynthesis.cancel();
      setSpeakingId(null);
      return;
    }
    window.speechSynthesis.cancel();

    // Try server-side TTS first (OpenAI via aicpp_speak). Falls back to
    // the browser's SpeechSynthesis if the endpoint is unavailable.
    if (isOnline()) {
      const res = await aicpp<{ audio_url?: string; audio_base64?: string; mime?: string }>(
        "aicpp_speak",
        { text: m.content, lang }
      );
      if (res.ok && (res.data.audio_url || res.data.audio_base64)) {
        const src = res.data.audio_url
          ? res.data.audio_url
          : `data:${res.data.mime ?? "audio/mpeg"};base64,${res.data.audio_base64}`;
        const audio = new Audio(src);
        setSpeakingId(m.id);
        audio.onended = () => setSpeakingId(null);
        audio.onerror = () => setSpeakingId(null);
        await audio.play().catch(() => setSpeakingId(null));
        return;
      }
    }

    const u = new SpeechSynthesisUtterance(m.content);
    const langMap: Record<Lang, string> = { en: "en-US", ar: "ar-SA", fr: "fr-FR", es: "es-ES" };
    u.lang = langMap[lang];
    u.onend = () => setSpeakingId(null);
    setSpeakingId(m.id);
    window.speechSynthesis.speak(u);
  }

  function exportConversation() {
    if (!conversation) return;
    const md = [
      `# ${conversation.title}`,
      `_Persona: ${persona?.name ?? "Unknown"}_`,
      "",
      ...conversation.messages.map((m) =>
        `**${m.role === "user" ? "You" : persona?.name ?? "AI"}** — ${new Date(m.ts).toLocaleString()}\n\n${m.content}\n`
      ),
    ].join("\n");
    const blob = new Blob([md], { type: "text/markdown" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `${conversation.title.replace(/[^\w]+/g, "_")}.md`;
    a.click();
    URL.revokeObjectURL(url);
    toast.success("Conversation exported");
  }

  async function toggleNotifications() {
    if (!notifications) {
      if (!("Notification" in window)) {
        toast.error("Notifications not supported");
        return;
      }
      const perm = await Notification.requestPermission();
      if (perm === "granted") {
        setNotifications(true);
        toast.success("Notifications enabled");
      }
    } else {
      setNotifications(false);
      toast.message("Notifications disabled");
    }
  }

  return (
    <section className="flex flex-1 flex-col bg-background min-w-0">
      {/* Header */}
      <header className="flex items-center justify-between border-b border-border px-4 py-3">
        <div className="flex items-center gap-3 min-w-0">
          {persona ? (
            <>
              <div className="flex h-9 w-9 items-center justify-center rounded-full bg-secondary text-xl">{persona.avatar}</div>
              <div className="min-w-0">
                <div className="truncate text-sm font-semibold">{persona.name}</div>
                <div className="truncate text-[11px] text-muted-foreground">{persona.tagline}</div>
              </div>
            </>
          ) : (
            <div className="text-sm text-muted-foreground">{t(lang, "pickPersona")}</div>
          )}
        </div>
        <div className="flex items-center gap-1">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button size="icon" variant="ghost" title={t(lang, "language")}>
                <Languages className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              {(["en", "ar", "fr", "es"] as Lang[]).map((l) => (
                <DropdownMenuItem key={l} onClick={() => setLang(l)}>
                  {l === "en" ? "English" : l === "ar" ? "العربية" : l === "fr" ? "Français" : "Español"}
                  {l === lang && " ✓"}
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>
          <Button size="icon" variant="ghost" onClick={toggleNotifications} title={t(lang, "notify")}>
            {notifications ? <Bell className="h-4 w-4 text-primary" /> : <BellOff className="h-4 w-4" />}
          </Button>
          <Button size="icon" variant="ghost" onClick={exportConversation} disabled={!conversation} title={t(lang, "export")}>
            <Download className="h-4 w-4" />
          </Button>
        </div>
      </header>

      {/* Body */}
      <div ref={scrollerRef} className="flex-1 overflow-y-auto">
        {!conversation || !persona ? (
          <div className="mx-auto max-w-3xl px-4 py-8">
            <h2 className="mb-1 text-lg font-semibold">{t(lang, "pickPersona")}</h2>
            <p className="mb-6 text-sm text-muted-foreground">Select an AI persona below to start a new conversation.</p>
            <PersonaGallery selectedId={null} onSelect={onPickPersona} />
          </div>
        ) : conversation.messages.length === 0 ? (
          <div className="flex h-full items-center justify-center px-4">
            <div className="text-center">
              <div className="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-[image:var(--gradient-primary)] text-3xl shadow-[var(--shadow-glow)]">
                {persona.avatar}
              </div>
              <h3 className="font-semibold">{persona.name}</h3>
              <p className="mt-1 text-sm text-muted-foreground">{persona.tagline}</p>
            </div>
          </div>
        ) : (
          <div className="mx-auto max-w-3xl space-y-4 px-4 py-6">
            {conversation.messages.map((m) => (
              <div key={m.id} className={cn("flex gap-3", m.role === "user" && "flex-row-reverse")}>
                <div className={cn(
                  "flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-base",
                  m.role === "user" ? "bg-secondary" : "bg-[image:var(--gradient-primary)]"
                )}>
                  {m.role === "user" ? "🧑" : persona.avatar}
                </div>
                <div className={cn(
                  "group max-w-[80%] rounded-2xl px-4 py-2.5 text-sm",
                  m.role === "user"
                    ? "bg-primary text-primary-foreground rounded-tr-sm"
                    : "bg-card text-card-foreground border border-border rounded-tl-sm"
                )}>
                  <p className="whitespace-pre-wrap leading-relaxed">{m.content}</p>
                  {m.role === "assistant" && (
                    <button
                      onClick={() => speak(m)}
                      className="mt-1.5 flex items-center gap-1 text-[10px] text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity hover:text-foreground"
                    >
                      {speakingId === m.id ? <VolumeX className="h-3 w-3" /> : <Volume2 className="h-3 w-3" />}
                      {speakingId === m.id ? t(lang, "stopSpeak") : t(lang, "speak")}
                    </button>
                  )}
                </div>
              </div>
            ))}
            {busy && (
              <div className="flex gap-3">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[image:var(--gradient-primary)] text-base">{persona.avatar}</div>
                <div className="rounded-2xl border border-border bg-card px-4 py-3">
                  <div className="flex gap-1">
                    <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:0ms]" />
                    <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:150ms]" />
                    <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:300ms]" />
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Composer */}
      <div className="border-t border-border bg-card/50 p-3">
        <div className="mx-auto flex max-w-3xl items-end gap-2">
          <Textarea
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                send();
              }
            }}
            placeholder={t(lang, "typeMessage")}
            disabled={!conversation}
            rows={1}
            className="min-h-[44px] resize-none bg-background"
          />
          <Button onClick={send} disabled={!conversation || !input.trim() || busy} className="h-11 bg-[image:var(--gradient-primary)] text-primary-foreground hover:opacity-90">
            <Send className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </section>
  );
}