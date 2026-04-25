import { PERSONAS } from "@/lib/widget-data";
import { cn } from "@/lib/utils";

type Props = {
  selectedId: string | null;
  onSelect: (id: string) => void;
  compact?: boolean;
};

export function PersonaGallery({ selectedId, onSelect, compact }: Props) {
  return (
    <div className={cn("grid gap-3", compact ? "grid-cols-4" : "grid-cols-2 md:grid-cols-3 lg:grid-cols-4")}>
      {PERSONAS.map((p) => {
        const active = p.id === selectedId;
        return (
          <button
            key={p.id}
            onClick={() => onSelect(p.id)}
            className={cn(
              "group relative flex flex-col items-center gap-2 rounded-xl border p-4 text-center transition-all",
              active
                ? "border-primary bg-card shadow-[var(--shadow-glow)]"
                : "border-border bg-card hover:border-primary/50 hover:bg-accent"
            )}
          >
            <div className={cn(
              "flex h-14 w-14 items-center justify-center rounded-full text-3xl transition-transform group-hover:scale-110",
              active ? "bg-[image:var(--gradient-primary)]" : "bg-secondary"
            )}>
              {p.avatar}
            </div>
            <div>
              <div className="text-sm font-semibold text-foreground">{p.name}</div>
              {!compact && <div className="mt-0.5 text-[11px] text-muted-foreground line-clamp-2">{p.tagline}</div>}
            </div>
            <span className={cn(
              "absolute right-2 top-2 rounded-full px-1.5 py-0.5 text-[9px] font-medium uppercase tracking-wider",
              active ? "bg-primary text-primary-foreground" : "bg-muted text-muted-foreground"
            )}>
              {p.category}
            </span>
          </button>
        );
      })}
    </div>
  );
}