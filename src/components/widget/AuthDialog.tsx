import { useState } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { t } from "@/lib/widget-i18n";
import type { Lang, User } from "@/lib/widget-types";
import { SEED_USER } from "@/lib/widget-data";
import { toast } from "sonner";

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

  function submit(e: React.FormEvent) {
    e.preventDefault();
    if (!email || !password || (mode === "register" && !username)) {
      toast.error("Please fill all fields");
      return;
    }
    // In production: POST to aicpp_login_user / aicpp_register_user
    const user: User = {
      ...SEED_USER,
      id: `u_${Date.now()}`,
      email,
      username: mode === "register" ? username : email.split("@")[0],
      referralCode: `VRS-${(username || email.split("@")[0]).toUpperCase().slice(0, 6)}-22`,
    };
    onAuth(user);
    onOpenChange(false);
    toast.success(mode === "login" ? "Welcome back!" : "Account created!");
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
          <Button type="submit" className="w-full bg-[image:var(--gradient-primary)] text-primary-foreground hover:opacity-90">
            {t(lang, "continue")}
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