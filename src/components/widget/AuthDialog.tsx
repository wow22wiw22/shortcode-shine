import { useState } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { t } from "@/lib/widget-i18n";
import type { Lang, User } from "@/lib/widget-types";
import { SEED_USER } from "@/lib/widget-data";
import { toast } from "sonner";
import { aicpp, isOnline } from "@/lib/aicpp";

type Props = {
  open: boolean;
  onOpenChange: (b: boolean) => void;
  lang: Lang;
  onAuth: (u: User) => void;
};

export function AuthDialog({ open, onOpenChange, lang, onAuth }: Props) {
  const [mode, setMode] = useState<"login" | "register">("login");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [username, setUsername] = useState("");
  const [referral, setReferral] = useState("");
  const [busy, setBusy] = useState(false);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    if (!email || !password || (mode === "register" && !username)) {
      toast.error("Please fill all fields");
      return;
    }
    setBusy(true);
    try {
      if (isOnline()) {
        const action = mode === "login" ? "aicpp_login_user" : "aicpp_register_user";
        const res = await aicpp<any>(action, {
          email,
          password,
          username: mode === "register" ? username : undefined,
          referral_code: mode === "register" ? referral || undefined : undefined,
        });
        if (!res.ok) {
          toast.error(res.error);
          return;
        }
        const d = res.data ?? {};
        const user: User = {
          id: String(d.user_id ?? d.id ?? `u_${Date.now()}`),
          username: d.username ?? username ?? email.split("@")[0],
          email: d.email ?? email,
          avatar: d.avatar ?? "🦊",
          bio: d.bio ?? "",
          joinedAt: d.joined_at ? Number(d.joined_at) * 1000 : Date.now(),
          messageCount: Number(d.message_count ?? 0),
          referralCode: d.referral_code ?? `VRS-${(d.username || email.split("@")[0]).toUpperCase().slice(0, 6)}-22`,
          referredCount: Number(d.referred_count ?? 0),
        };
        onAuth(user);
      } else {
        // Preview-mode fallback
        const user: User = {
          ...SEED_USER,
          id: `u_${Date.now()}`,
          email,
          username: mode === "register" ? username : email.split("@")[0],
          referralCode: `VRS-${(username || email.split("@")[0]).toUpperCase().slice(0, 6)}-22`,
        };
        onAuth(user);
      }
      onOpenChange(false);
      toast.success(mode === "login" ? "Welcome back!" : "Account created!");
    } finally {
      setBusy(false);
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[400px]">
        <DialogHeader>
          <DialogTitle>{mode === "login" ? t(lang, "welcome") : t(lang, "createAccount")}</DialogTitle>
          <DialogDescription>
            {mode === "login" ? t(lang, "signIn") : t(lang, "signUp")} — {t(lang, "appName")}
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={submit} className="space-y-3">
          {mode === "register" && (
            <div className="space-y-1.5">
              <Label>{t(lang, "username")}</Label>
              <Input value={username} onChange={(e) => setUsername(e.target.value)} />
            </div>
          )}
          <div className="space-y-1.5">
            <Label>{t(lang, "email")}</Label>
            <Input type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
          <div className="space-y-1.5">
            <Label>{t(lang, "password")}</Label>
            <Input type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
          </div>
          {mode === "register" && (
            <div className="space-y-1.5">
              <Label>Referral code (optional)</Label>
              <Input value={referral} onChange={(e) => setReferral(e.target.value)} placeholder="VRS-FRIEND-22" />
            </div>
          )}
          <Button type="submit" disabled={busy} className="w-full bg-[image:var(--gradient-primary)] text-primary-foreground hover:opacity-90">
            {busy ? "…" : t(lang, "continue")}
          </Button>
          <button
            type="button"
            onClick={() => setMode(mode === "login" ? "register" : "login")}
            className="w-full text-center text-xs text-muted-foreground hover:text-foreground"
          >
            {mode === "login" ? t(lang, "needAccount") : t(lang, "haveAccount")}
          </button>
        </form>
      </DialogContent>
    </Dialog>
  );
}