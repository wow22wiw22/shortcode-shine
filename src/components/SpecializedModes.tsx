import { Heart, FlaskConical, TrendingUp, Code2, GraduationCap, Briefcase } from 'lucide-react';

export interface SpecializedMode {
  id: string;
  label: string;
  icon: React.ElementType;
  color: string;
  systemPrefix: string;
}

export const SPECIALIZED_MODES: SpecializedMode[] = [
  {
    id: 'general',
    label: 'General',
    icon: Briefcase,
    color: 'bg-primary/15 text-primary border-primary/40',
    systemPrefix: '',
  },
  {
    id: 'health',
    label: 'Health',
    icon: Heart,
    color: 'bg-secondary text-foreground border-border',
    systemPrefix: '[MODE: Health & Wellness] Respond as a health advisor. Provide evidence-based health information. Always recommend consulting a doctor for medical decisions.',
  },
  {
    id: 'research',
    label: 'Research',
    icon: FlaskConical,
    color: 'bg-secondary text-foreground border-border',
    systemPrefix: '[MODE: Research & Analysis] Respond as a research analyst. Cite sources when possible, present multiple perspectives, and provide structured analysis.',
  },
  {
    id: 'finance',
    label: 'Finance',
    icon: TrendingUp,
    color: 'bg-secondary text-foreground border-border',
    systemPrefix: '[MODE: Finance & Business] Respond as a financial advisor. Provide data-driven insights, market analysis, and business strategy. Disclaimer: not financial advice.',
  },
  {
    id: 'code',
    label: 'Code',
    icon: Code2,
    color: 'bg-secondary text-foreground border-border',
    systemPrefix: '[MODE: Software Engineering] Respond as a senior developer. Provide clean, well-documented code with explanations. Follow best practices.',
  },
  {
    id: 'education',
    label: 'Learn',
    icon: GraduationCap,
    color: 'bg-secondary text-foreground border-border',
    systemPrefix: '[MODE: Education & Tutoring] Respond as a patient tutor. Break down complex concepts, use examples, and check understanding step by step.',
  },
];

interface SpecializedModesProps {
  activeMode: string;
  onSelectMode: (mode: SpecializedMode) => void;
}

export function SpecializedModesBar({ activeMode, onSelectMode }: SpecializedModesProps) {
  return (
    <div className="flex items-center gap-1.5 overflow-x-auto scrollbar-hide px-1 py-1">
      {SPECIALIZED_MODES.map((mode) => {
        const Icon = mode.icon;
        const isActive = activeMode === mode.id;
        return (
          <button
            key={mode.id}
            onClick={() => onSelectMode(mode)}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                       border whitespace-nowrap transition-all duration-200 active:scale-[0.97]
                       ${isActive
                          ? `${mode.color} shadow-[0_0_0_1px_hsl(var(--primary)/0.35)]`
                          : 'bg-secondary/60 text-muted-foreground border-border hover:bg-muted hover:text-foreground'
                       }`}
          >
            <Icon className="w-3.5 h-3.5" />
            {mode.label}
          </button>
        );
      })}
    </div>
  );
}
