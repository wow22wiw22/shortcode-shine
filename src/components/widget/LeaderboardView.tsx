import { Card } from "@/components/ui/card";
import { LEADERBOARD_SEED } from "@/lib/widget-data";
import { t } from "@/lib/widget-i18n";
import type { Lang, User } from "@/lib/widget-types";
import { Trophy, Medal } from "lucide-react";
import { cn } from "@/lib/utils";

const BADGE_COLORS: Record<string, string> = {
  Diamond: "text-cyan-300 bg-cyan-500/15",
  Platinum: "text-zinc-200 bg-zinc-400/15",
  Gold: "text-primary bg-primary/15",
  Silver: "text-slate-300 bg-slate-400/15",
  Bronze: "text-orange-300 bg-orange-500/15",
};

export function LeaderboardView({ lang, user }: { lang: Lang; user: User | null }) {
  return (
    <div className="mx-auto max-w-2xl px-4 py-8">
      <div className="mb-6 flex items-center gap-3">
        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[image:var(--gradient-primary)] text-primary-foreground shadow-[var(--shadow-glow)]">
          <Trophy className="h-5 w-5" />
        </div>
        <div>
          <h2 className="text-xl font-bold">{t(lang, "leaderboard")}</h2>
          <p className="text-xs text-muted-foreground">Top community members this month</p>
        </div>
      </div>

      <Card className="overflow-hidden">
        <ul className="divide-y divide-border">
          {LEADERBOARD_SEED.map((row) => {
            const isYou = user?.username === row.username;
            return (
              <li
                key={row.rank}
                className={cn(
                  "flex items-center gap-3 px-4 py-3",
                  isYou && "bg-primary/10 ring-1 ring-inset ring-primary/30"
                )}
              >
                <div className={cn(
                  "flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold",
                  row.rank === 1 ? "bg-primary text-primary-foreground" :
                  row.rank <= 3 ? "bg-secondary text-foreground" : "bg-muted text-muted-foreground"
                )}>
                  {row.rank <= 3 ? <Medal className="h-4 w-4" /> : row.rank}
                </div>
                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-secondary text-lg">{row.avatar}</div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <span className="truncate text-sm font-medium">{row.username}</span>
                    {isYou && <span className="text-[9px] uppercase rounded bg-primary text-primary-foreground px-1 py-0.5">You</span>}
                  </div>
                  <span className={cn("inline-block mt-0.5 rounded px-1.5 py-0.5 text-[9px] uppercase tracking-wider", BADGE_COLORS[row.badge])}>
                    {row.badge}
                  </span>
                </div>
                <div className="text-right">
                  <div className="text-sm font-bold text-primary">{row.points.toLocaleString()}</div>
                  <div className="text-[9px] uppercase tracking-wider text-muted-foreground">{t(lang, "points")}</div>
                </div>
              </li>
            );
          })}
        </ul>
      </Card>
    </div>
  );
}