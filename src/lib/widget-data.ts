import type { Persona, Conversation, User } from "./widget-types";

export const PERSONAS: Persona[] = [
  { id: "p1", name: "Versace AI", avatar: "👑", tagline: "Luxury fashion advisor", category: "Fashion" },
  { id: "p2", name: "Code Mentor", avatar: "💻", tagline: "Senior dev pair-programmer", category: "Tech" },
  { id: "p3", name: "Chef Marco", avatar: "👨‍🍳", tagline: "Italian cuisine expert", category: "Food" },
  { id: "p4", name: "Dr. Mind", avatar: "🧠", tagline: "Mental wellness coach", category: "Health" },
  { id: "p5", name: "Trader Pro", avatar: "📈", tagline: "Market & crypto analyst", category: "Finance" },
  { id: "p6", name: "Travel Sage", avatar: "✈️", tagline: "Trip planner & local guide", category: "Travel" },
  { id: "p7", name: "Poet Laura", avatar: "📜", tagline: "Verses, lyrics & prose", category: "Creative" },
  { id: "p8", name: "Fit Coach", avatar: "💪", tagline: "Workout & nutrition plans", category: "Health" },
];

export const SEED_USER: User = {
  id: "u_demo",
  username: "guest_user",
  email: "guest@versace22.ai",
  avatar: "🦊",
  bio: "Exploring the Versace22 AI universe.",
  joinedAt: Date.now() - 1000 * 60 * 60 * 24 * 12,
  messageCount: 47,
  referralCode: "VRS-GUEST-22",
  referredCount: 3,
};

export const SEED_CONVERSATIONS: Conversation[] = [
  {
    id: "c1",
    title: "Spring outfit ideas",
    personaId: "p1",
    pinned: true,
    updatedAt: Date.now() - 1000 * 60 * 30,
    messages: [
      { id: "m1", role: "user", content: "What should I wear to a Milan rooftop dinner?", ts: Date.now() - 1000 * 60 * 32 },
      { id: "m2", role: "assistant", content: "For a Milan rooftop dinner, lean into elegant minimalism: a tailored linen blazer in stone or ivory over a fine-knit tee, slim trousers, and Italian loafers. Add a slim leather belt and a vintage watch.", ts: Date.now() - 1000 * 60 * 31 },
    ],
  },
  {
    id: "c2",
    title: "Refactor my React hook",
    personaId: "p2",
    pinned: false,
    updatedAt: Date.now() - 1000 * 60 * 60 * 3,
    messages: [
      { id: "m3", role: "user", content: "How do I memoize this expensive selector?", ts: Date.now() - 1000 * 60 * 60 * 3 },
      { id: "m4", role: "assistant", content: "Wrap it in `useMemo` and key it on the minimal inputs. If the selector touches a store, prefer a library like Reselect.", ts: Date.now() - 1000 * 60 * 60 * 3 + 30000 },
    ],
  },
  {
    id: "c3",
    title: "Carbonara — authentic recipe",
    personaId: "p3",
    pinned: false,
    updatedAt: Date.now() - 1000 * 60 * 60 * 26,
    messages: [
      { id: "m5", role: "user", content: "Real carbonara, no cream right?", ts: Date.now() - 1000 * 60 * 60 * 26 },
      { id: "m6", role: "assistant", content: "Correct — guanciale, egg yolks, pecorino romano, black pepper. Never cream.", ts: Date.now() - 1000 * 60 * 60 * 26 + 20000 },
    ],
  },
];

export const LEADERBOARD_SEED = [
  { rank: 1, username: "luna_42", avatar: "🌙", points: 12480, badge: "Diamond" },
  { rank: 2, username: "neo_dev", avatar: "🤖", points: 9870, badge: "Platinum" },
  { rank: 3, username: "amira_k", avatar: "🌸", points: 8420, badge: "Platinum" },
  { rank: 4, username: "marco_p", avatar: "🍷", points: 7110, badge: "Gold" },
  { rank: 5, username: "yuki", avatar: "❄️", points: 6540, badge: "Gold" },
  { rank: 6, username: "guest_user", avatar: "🦊", points: 4720, badge: "Silver" },
  { rank: 7, username: "kai_s", avatar: "🌊", points: 3980, badge: "Silver" },
  { rank: 8, username: "rosa", avatar: "🌹", points: 2110, badge: "Bronze" },
];

// Mock AI replies — in production these come from your PHP `aicpp_send_message` endpoint.
export function mockReply(personaId: string, prompt: string): string {
  const p = PERSONAS.find((x) => x.id === personaId);
  const intros: Record<string, string> = {
    p1: "Darling, here's my take —",
    p2: "Quick technical answer:",
    p3: "Mamma mia, listen carefully:",
    p4: "Take a deep breath. Here's a perspective:",
    p5: "Looking at the chart,",
    p6: "Pack light and",
    p7: "Let me weave you a thought:",
    p8: "Let's get to work —",
  };
  const intro = intros[personaId] ?? "Here's what I think:";
  return `${intro} ${prompt.slice(0, 80)}${prompt.length > 80 ? "…" : ""}. As ${p?.name ?? "your AI"}, I'd suggest exploring this step by step. (This is a UI preview; in production this reply comes from your plugin's \`aicpp_send_message\` endpoint.)`;
}