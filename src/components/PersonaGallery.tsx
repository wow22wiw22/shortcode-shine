import { Sparkles } from 'lucide-react';
import { Persona, MainCharacter } from '@/lib/types';

interface PersonaGalleryProps {
  personas: Persona[];
  selectedPersona: Persona | null;
  onSelectPersona: (persona: Persona) => void;
  mainCharacter?: MainCharacter | null;
  isMainChatMode?: boolean;
  onSelectMainCharacter?: () => void;
  onBack: () => void;
}

export function PersonaGallery({
  personas,
  selectedPersona,
  onSelectPersona,
  mainCharacter,
  isMainChatMode,
  onSelectMainCharacter,
  onBack,
}: PersonaGalleryProps) {
  return (
    <div className="flex-1 flex items-center justify-center px-6 text-center">
      <div className="space-y-4" style={{ animation: 'fade-up 0.4s cubic-bezier(0.16,1,0.3,1) both' }}>
        <div className="flex justify-center">
          <Sparkles className="w-8 h-8 text-primary" />
        </div>
        <div className="space-y-2">
          <h2 className="text-4xl font-extrabold text-primary">Personas</h2>
          <p className="max-w-md text-sm text-muted-foreground">Browse and assign AI personas powered by AI Chat Persona Pro.</p>
        </div>
        <button
          onClick={onBack}
          className="rounded-xl bg-muted px-5 py-3 text-sm font-semibold text-foreground hover:bg-secondary"
        >
          Back to chat
        </button>
      </div>
    </div>
  );
}
