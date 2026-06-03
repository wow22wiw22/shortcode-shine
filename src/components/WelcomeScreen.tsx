import { Pencil, Lightbulb, Search, BookOpen } from 'lucide-react';

interface WelcomeScreenProps {
  personaName: string;
  onSendSuggestion: (text: string) => void;
}

const categories = [
  { icon: Pencil, label: 'Write' },
  { icon: Lightbulb, label: 'Plan' },
  { icon: Search, label: 'Research' },
  { icon: BookOpen, label: 'Learn' },
];

const suggestions: Record<string, string[]> = {
  Write: [
    'Write a professional email for me',
    'Help me draft a blog post',
    'Create a social media caption',
  ],
  Plan: [
    'Help me plan a weekly schedule',
    'Create a project roadmap',
    'Plan a marketing strategy',
  ],
  Research: [
    'Summarize the latest trends in AI',
    'Compare different approaches to...',
    'Find key facts about a topic',
  ],
  Learn: [
    'Explain a complex concept simply',
    'Quiz me on a topic',
    'Teach me something new today',
  ],
};

export function WelcomeScreen({ personaName, onSendSuggestion }: WelcomeScreenProps) {
  return (
    <div className="flex-1 flex flex-col items-center justify-center px-4 pb-8">
      <div
        className="text-center space-y-4"
        style={{ animation: 'fade-up 0.6s cubic-bezier(0.16,1,0.3,1) 0.1s both' }}
      >
        <h1 className="text-[56px] leading-none sm:text-[72px] font-extrabold uppercase text-primary">
          VERSACE22 AI
        </h1>
        <h2
          className="text-[28px] sm:text-[38px] font-extrabold text-foreground leading-[1.05]"
          style={{ textWrap: 'balance' } as React.CSSProperties}
        >
          World's smartest AIs,
          <br />
          <span className="text-primary">side-by-side</span> with you
        </h2>
      </div>

      {/* Category chips */}
      <div
        className="mt-8 flex flex-wrap justify-center gap-3"
        style={{ animation: 'fade-up 0.6s cubic-bezier(0.16,1,0.3,1) 0.2s both' }}
      >
        {categories.map((cat) => (
          <button
            key={cat.label}
            onClick={() => onSendSuggestion(suggestions[cat.label][0])}
            className="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium
                       border border-border bg-secondary/70 text-foreground transition-all duration-200
                       hover:bg-secondary active:scale-[0.97]"
          >
            <cat.icon className="w-4 h-4" />
            {cat.label}
          </button>
        ))}
      </div>

      {/* Suggestion prompts */}
      <div
        className="mt-4 flex flex-wrap justify-center gap-3 max-w-3xl"
        style={{ animation: 'fade-up 0.6s cubic-bezier(0.16,1,0.3,1) 0.35s both' }}
      >
        {['What can you help me with today?', 'Tell me about your expertise', 'Help me brainstorm an idea'].map((s) => (
          <button
            key={s}
            onClick={() => onSendSuggestion(s)}
            className="px-5 py-3 rounded-full text-sm bg-secondary/70 text-secondary-foreground
                       border border-border hover:bg-muted hover:border-primary/20
                       transition-all duration-200 active:scale-[0.97]"
          >
            {s}
          </button>
        ))}
      </div>
    </div>
  );
}
