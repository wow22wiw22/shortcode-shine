import { useEffect, useState } from "react";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { t } from "@/lib/widget-i18n";
import type { Lang, User } from "@/lib/widget-types";
import { Gift, Copy, Check, Share2, Users } from "lucide-react";
import { toast } from "sonner";
import { aicpp, isOnline } from "@/lib/aicpp";

export function ReferView({ lang, user }: { lang: Lang; user: User }) {
  const [copied, setCopied] = useState(false);
  const [code, setCode] = useState(user.referralCode);
  const [count, setCount] = useState(user.referredCount);
  const link = `${typeof window !== "undefined" ? window.location.origin : ""}/?ref=${code}`;

  useEffect(() => {
    if (!isOnline()) return;
    let cancelled = false;
    (async () => {
      const res = await aicpp<{ referral_code: string; referred_count: number }>("aicpp_get_referral_data");
      if (!cancelled && res.ok) {
        if (res.data.referral_code) setCode(res.data.referral_code);
        if (typeof res.data.referred_count === "number") setCount(res.data.referred_count);
      }
    })();
    return () => { cancelled = true; };
  }, []);

  function copy() {
    navigator.clipboard.writeText(link);
    setCopied(true);
    toast.success(t(lang, "copied"));
    setTimeout(() => setCopied(false), 2000);
  }

  function share() {
    if (navigator.share) {
      navigator.share({ title: t(lang, "appName"), text: "Join me on Versace22 AI", url: link }).catch(() => {});
    } else {
      copy();
    }
  }

  return (
    <div className="mx-auto max-w-2xl px-4 py-8 space-y-4">
      <Card className="relative overflow-hidden p-6 text-center">
        <div className="absolute inset-0 bg-[image:var(--gradient-primary)] opacity-10" />
        <div className="relative">
          <div className="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[image:var(--gradient-primary)] text-primary-foreground shadow-[var(--shadow-glow)]">
            <Gift className="h-7 w-7" />
          </div>
          <h2 className="text-xl font-bold">{t(lang, "refer")}</h2>
          <p className="mt-1 text-sm text-muted-foreground">Earn 100 credits for every friend who signs up</p>
        </div>
      </Card>

      <Card className="p-5">
        <div className="text-xs uppercase tracking-wider text-muted-foreground">{t(lang, "yourCode")}</div>
        <div className="mt-2 flex items-center gap-2">
          <div className="flex-1 rounded-md border border-dashed border-primary/40 bg-secondary px-3 py-2.5 font-mono text-sm font-bold text-primary">
            {code}
          </div>
          <Button onClick={copy} variant="outline" size="icon">
            {copied ? <Check className="h-4 w-4 text-primary" /> : <Copy className="h-4 w-4" />}
          </Button>
          <Button onClick={share} className="bg-[image:var(--gradient-primary)] text-primary-foreground hover:opacity-90">
            <Share2 className="mr-1.5 h-4 w-4" />
            Share
          </Button>
        </div>
        <div className="mt-3 truncate text-[11px] text-muted-foreground">{link}</div>
      </Card>

      <Card className="p-5">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-secondary text-primary">
            <Users className="h-5 w-5" />
          </div>
          <div className="flex-1">
            <div className="text-2xl font-bold">{count}</div>
            <div className="text-xs text-muted-foreground">{t(lang, "invitedFriends")}</div>
          </div>
          <div className="text-right">
            <div className="text-2xl font-bold text-primary">{count * 100}</div>
            <div className="text-xs text-muted-foreground">credits earned</div>
          </div>
        </div>
      </Card>
    </div>
  );
}