import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card } from "@/components/ui/card";
import { t } from "@/lib/widget-i18n";
import type { Lang, User } from "@/lib/widget-types";
import { MessageSquare, Calendar, Trophy, Pencil } from "lucide-react";
import { toast } from "sonner";
import { aicpp, isOnline } from "@/lib/aicpp";

type Props = { lang: Lang; user: User; onUpdate: (u: User) => void };

export function ProfileView({ lang, user, onUpdate }: Props) {
  const [editing, setEditing] = useState(false);
  const [draft, setDraft] = useState(user);
  const [busy, setBusy] = useState(false);

  async function save() {
    setBusy(true);
    try {
      if (isOnline()) {
        const res = await aicpp("aicpp_update_profile", {
          username: draft.username,
          bio: draft.bio,
          avatar: draft.avatar,
        });
        if (!res.ok) {
          toast.error(res.error);
          return;
        }
      }
      onUpdate(draft);
      setEditing(false);
      toast.success("Profile updated");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="mx-auto max-w-2xl px-4 py-8 space-y-4">
      <Card className="p-6">
        <div className="flex items-start gap-4">
          <div className="flex h-20 w-20 items-center justify-center rounded-full bg-[image:var(--gradient-primary)] text-4xl shadow-[var(--shadow-glow)]">
            {user.avatar}
          </div>
          <div className="flex-1 min-w-0">
            {editing ? (
              <div className="space-y-3">
                <div>
                  <Label className="text-xs">{t(lang, "username")}</Label>
                  <Input value={draft.username} onChange={(e) => setDraft({ ...draft, username: e.target.value })} />
                </div>
                <div>
                  <Label className="text-xs">Bio</Label>
                  <Textarea rows={2} value={draft.bio} onChange={(e) => setDraft({ ...draft, bio: e.target.value })} />
                </div>
                <div>
                  <Label className="text-xs">Avatar (emoji)</Label>
                  <Input maxLength={2} value={draft.avatar} onChange={(e) => setDraft({ ...draft, avatar: e.target.value })} />
                </div>
                <div className="flex gap-2">
                  <Button onClick={save} disabled={busy} className="bg-[image:var(--gradient-primary)] text-primary-foreground">
                    {busy ? "…" : t(lang, "save")}
                  </Button>
                  <Button variant="ghost" onClick={() => { setDraft(user); setEditing(false); }}>{t(lang, "cancel")}</Button>
                </div>
              </div>
            ) : (
              <>
                <h2 className="text-xl font-bold">{user.username}</h2>
                <p className="text-sm text-muted-foreground">{user.email}</p>
                <p className="mt-2 text-sm">{user.bio}</p>
                <Button variant="outline" size="sm" className="mt-3" onClick={() => setEditing(true)}>
                  <Pencil className="mr-1.5 h-3 w-3" />
                  {t(lang, "edit")}
                </Button>
              </>
            )}
          </div>
        </div>
      </Card>

      <div className="grid grid-cols-3 gap-3">
        <StatCard icon={<MessageSquare className="h-4 w-4" />} label="Messages" value={user.messageCount} />
        <StatCard icon={<Calendar className="h-4 w-4" />} label="Member days" value={Math.floor((Date.now() - user.joinedAt) / 86400000)} />
        <StatCard icon={<Trophy className="h-4 w-4" />} label={t(lang, "invitedFriends")} value={user.referredCount} />
      </div>
    </div>
  );
}

function StatCard({ icon, label, value }: { icon: React.ReactNode; label: string; value: number }) {
  return (
    <Card className="p-4 text-center">
      <div className="mx-auto mb-1.5 flex h-8 w-8 items-center justify-center rounded-full bg-secondary text-primary">{icon}</div>
      <div className="text-xl font-bold">{value}</div>
      <div className="text-[10px] uppercase tracking-wider text-muted-foreground">{label}</div>
    </Card>
  );
}