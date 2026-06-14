/**
 * MISS C — single source of truth for the active conversation id.
 * Chat sends should call setActiveConversation(data.conversation_id);
 * artifact/search panels read getActiveConversation() to scope their queries.
 */
let activeId = 0;
const subs = new Set<(id: number) => void>();

export function getActiveConversation(): number {
  return activeId;
}

export function setActiveConversation(id: number): void {
  activeId = Number(id) || 0;
  subs.forEach((f) => f(activeId));
}

export function onActiveConversation(f: (id: number) => void): () => void {
  subs.add(f);
  return () => { subs.delete(f); };
}