/**
 * WordPress AJAX API bridge with a local preview mock for Lovable.
 * Keeps the v12 public API shape while allowing the interface to render here.
 */

interface WPConfig {
  ajaxurl: string;
  nonce: string;
  personaId: number;
  sessionId: string;
  userId: number;
  isAdmin: boolean;
  adminNonce?: string;
  loginNonce?: string;
  registerNonce?: string;
  googleLoginUrl?: string;
}

// v12 manifest types
export interface WPEndpointEntry { action: string; nonce: string; nopriv: boolean }
export type WPEndpointGroup = Record<string, WPEndpointEntry>;
export type WPEndpointMap = Record<string, WPEndpointGroup>;
export type WPCapabilityKey =
  | 'chat' | 'upload' | 'voice' | 'history' | 'admin'
  | 'create_project' | 'memories' | 'artifacts'
  | 'referrals' | 'leaderboard' | 'login' | 'register';

export function getEndpoints(): WPEndpointMap {
  const w = window as any;
  return (w?.versace22_chat?.endpoints as WPEndpointMap) || {};
}

export function getEndpoint(group: string, key: string): WPEndpointEntry | null {
  return getEndpoints()?.[group]?.[key] || null;
}

export function getNonces(): Record<string, string> {
  const w = window as any;
  return (w?.versace22_chat?.nonces as Record<string, string>) || {};
}

export function can(cap: WPCapabilityKey): boolean {
  const w = window as any;
  const map = w?.versace22_chat?.can;
  if (map && typeof map === 'object') return !!map[cap];
  // Sensible fallbacks for v11/earlier handshakes
  switch (cap) {
    case 'admin': return !!w?.versace22_chat?.is_admin;
    case 'login':
    case 'register': return !w?.versace22_chat?.user_logged_in;
    case 'chat': return true;
    default: return !!w?.versace22_chat?.user_logged_in;
  }
}

interface MockWPUser {
  user_id: number;
  username: string;
  display_name: string;
  email: string;
  avatar: string;
  is_admin: boolean;
}

interface MockWPMemoryStoreItem extends WPMemory {
  user_id: number;
}

interface MockWPConversationStoreItem extends WPConversation {
  messages: WPMessage[];
  session_id: string;
  user_id: number;
}

interface MockWPStore {
  users: Array<MockWPUser & { password: string }>;
  memories: MockWPMemoryStoreItem[];
  projects: WPProject[];
  conversations: MockWPConversationStoreItem[];
  conversationId: number;
}

export interface ParsedArtifact {
  id?: number;
  type: string;
  title: string;
  content: string;
}

export interface WPChatResponse {
  message: string;
  conversation_id?: number;
  artifacts?: ParsedArtifact[];
}

export interface WPPersonaInfo {
  id: number;
  name: string;
  description: string;
  avatar_initials: string;
  avatar_color: string;
  model: string;
  visibility: 'public' | 'private';
}

export interface WPMainCharacter {
  name: string;
  description: string;
  avatar_initials: string;
  avatar_color: string;
  model: string;
}

export interface WPConversation {
  id: number;
  title: string;
  token_count: number;
  persona_id: number | null;
  is_main_chat: number;
  persona_name: string | null;
  avatar_initials: string | null;
  avatar_color: string | null;
  created_at: string;
  updated_at: string;
  pinned?: number;
}

export interface WPMessage {
  role: 'user' | 'assistant';
  content: string;
}

export interface WPMemory {
  id: number;
  persona_id: number;
  memory_text: string;
  enabled: number;
}

export interface WPProject {
  id: number;
  name: string;
  description: string;
  custom_instructions: string;
}

export interface WPORModel {
  id: string;
  name: string;
  context_length?: number;
  pricing?: { prompt: string; completion: string };
}

const mockPersonas: WPPersonaInfo[] = [
  {
    id: 1,
    name: 'General Assistant',
    description: 'Helpful AI assistant for any task',
    avatar_initials: 'GA',
    avatar_color: '22 85% 42%',
    model: 'gpt-4',
    visibility: 'public',
  },
  {
    id: 2,
    name: 'Code Wizard',
    description: 'Expert programmer and software architect',
    avatar_initials: 'CW',
    avatar_color: '200 90% 48%',
    model: 'gpt-4',
    visibility: 'public',
  },
  {
    id: 3,
    name: 'Creative Writer',
    description: 'Storyteller and content creator',
    avatar_initials: 'CR',
    avatar_color: '285 85% 58%',
    model: 'claude-3-opus',
    visibility: 'private',
  },
];

const mockMainCharacter: WPMainCharacter = {
  name: 'VERSACE22 AI',
  description: 'Main chat persona for the Integrate v12 WP API #18 interface.',
  avatar_initials: 'V2',
  avatar_color: '22 85% 42%',
  model: 'gpt-4',
};

const DEFAULT_MOCK_STORE: MockWPStore = {
  conversationId: 3,
  memories: [
    { id: 1, user_id: 1, persona_id: 1, memory_text: 'User prefers exact version matching.', enabled: 1 },
  ],
  users: [
  {
    user_id: 1,
    username: 'lorenzo',
    email: 'lorenzo@example.com',
    password: 'password123',
    display_name: 'lorenzo',
    avatar: '',
    is_admin: true,
  },
  ],
  projects: [
    { id: 1, name: 'Integrated v12 WP API #18', description: '', custom_instructions: '' },
  ],
  conversations: [
  {
    id: 1,
    title: 'Welcome to v12',
    token_count: 42,
    persona_id: null,
    is_main_chat: 1,
    persona_name: null,
    avatar_initials: 'V2',
    avatar_color: '22 85% 42%',
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    pinned: 0,
    session_id: 'sess_lovable_preview',
    user_id: 0,
    messages: [
      { role: 'assistant', content: 'This Lovable preview is showing the Integrate v12 WP API #18 interface.' },
    ],
  },
  {
    id: 2,
    title: 'Code Wizard demo',
    token_count: 38,
    persona_id: 2,
    is_main_chat: 0,
    persona_name: 'Code Wizard',
    avatar_initials: 'CW',
    avatar_color: '200 90% 48%',
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    pinned: 1,
    session_id: 'sess_lovable_preview',
    user_id: 0,
    messages: [
      { role: 'user', content: 'Show me the v12 interface.' },
      { role: 'assistant', content: 'You are looking at the v12 WordPress interface preview.' },
    ],
  },
  ],
};

const mockModels: WPORModel[] = [
  { id: 'openrouter/auto', name: 'OpenRouter Auto', context_length: 128000 },
  { id: 'meta-llama/llama-3.1-8b-instruct:free', name: 'Llama 3.1 8B Free', context_length: 131072 },
];

function cloneMockStore(): MockWPStore {
  return JSON.parse(JSON.stringify(DEFAULT_MOCK_STORE));
}

function getMockStore(): MockWPStore {
  if (typeof window === 'undefined') return cloneMockStore();
  const w = window as any;
  if (w.__versace22MockStore) return w.__versace22MockStore as MockWPStore;

  try {
    const stored = localStorage.getItem('versace22-mock-store');
    if (stored) {
      w.__versace22MockStore = JSON.parse(stored) as MockWPStore;
      return w.__versace22MockStore;
    }
  } catch {}

  w.__versace22MockStore = cloneMockStore();
  return w.__versace22MockStore;
}

function saveMockStore(store: MockWPStore) {
  if (typeof window === 'undefined') return;
  const w = window as any;
  w.__versace22MockStore = store;
  try {
    localStorage.setItem('versace22-mock-store', JSON.stringify(store));
  } catch {}
}

function setMockWPUser(user: MockWPUser | null) {
  const w = window as any;
  if (!w.versace22_chat) return;

  try {
    if (user) localStorage.setItem('versace22-mock-user', JSON.stringify(user));
    else localStorage.removeItem('versace22-mock-user');
  } catch {}

  w.versace22_chat.user_logged_in = !!user;
  w.versace22_chat.user_id = user?.user_id || 0;
  w.versace22_chat.user_display_name = user?.display_name || '';
  w.versace22_chat.user_email = user?.email || '';
  w.versace22_chat.user_avatar = user?.avatar || '';
  w.versace22_chat.is_admin = !!user?.is_admin;
}

function getWPConfig(): WPConfig | null {
  const w = window as any;
  if (!w.versace22_chat) return null;
  if (w.versace22_chat.ajaxurl?.includes('/wp-mock/')) {
    try {
      const stored = localStorage.getItem('versace22-mock-user');
      if (stored) {
        setMockWPUser(JSON.parse(stored) as MockWPUser);
      }
    } catch {}
  }
  return {
    ajaxurl: w.versace22_chat.ajaxurl,
    nonce: w.versace22_chat.nonce,
    personaId: parseInt(w.versace22_chat.persona_id, 10) || 1,
    sessionId: w.versace22_chat.session_id || 'sess_' + crypto.randomUUID(),
    userId: parseInt(w.versace22_chat.user_id, 10) || 0,
    isAdmin: !!w.versace22_chat.is_admin,
    adminNonce: w.versace22_chat.admin_nonce || '',
    loginNonce: w.versace22_chat.login_nonce || '',
    registerNonce: w.versace22_chat.register_nonce || '',
    googleLoginUrl: w.versace22_chat.google_login_url || '',
  };
}

function isMockWP(config: WPConfig | null): boolean {
  return !!config?.ajaxurl?.includes('/wp-mock/');
}

function mockDelay<T>(value: T, ms = 80): Promise<T> {
  return new Promise((resolve) => setTimeout(() => resolve(value), ms));
}

function getConversationTitle(message: string): string {
  const text = message.trim().replace(/\s+/g, ' ');
  return text.slice(0, 40) + (text.length > 40 ? '...' : '');
}

function ensureMockConversation(sessionId: string, personaId: number | null, isMainChat: boolean, seedTitle: string, userId: number) {
  const store = getMockStore();
  let conversation = store.conversations.find(
    (item) =>
      item.session_id === sessionId &&
      item.user_id === userId &&
      item.is_main_chat === (isMainChat ? 1 : 0) &&
      item.persona_id === personaId,
  );
  if (!conversation) {
    conversation = {
      id: ++store.conversationId,
      title: getConversationTitle(seedTitle),
      token_count: 0,
      persona_id: personaId,
      is_main_chat: isMainChat ? 1 : 0,
      persona_name: isMainChat ? null : mockPersonas.find((p) => p.id === personaId)?.name || 'Persona',
      avatar_initials: isMainChat ? mockMainCharacter.avatar_initials : mockPersonas.find((p) => p.id === personaId)?.avatar_initials || 'AI',
      avatar_color: isMainChat ? mockMainCharacter.avatar_color : mockPersonas.find((p) => p.id === personaId)?.avatar_color || '22 85% 42%',
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
      pinned: 0,
      session_id: sessionId,
      user_id: userId,
      messages: [],
    };
    store.conversations = [conversation, ...store.conversations];
    saveMockStore(store);
  }
  return conversation;
}

function buildMockReply(message: string, name: string) {
  const clean = message.replace(/^You are .*?\n\n/s, '').trim();
  return `${name} preview reply for Integrate v12 WP API #18:\n\n${clean || 'Ready.'}`;
}

function extractArtifacts(content: string): ParsedArtifact[] {
  const artifacts: ParsedArtifact[] = [];
  const artifactTag = /<artifact\s+type="([^"]+)"(?:\s+title="([^"]*)")?\s*>([\s\S]*?)<\/artifact>/gi;
  const fence = /```(html|svg|markdown|md|react|jsx|css|js)\s*([\s\S]*?)```/gi;
  let match: RegExpExecArray | null;

  while ((match = artifactTag.exec(content))) {
    artifacts.push({
      id: artifacts.length + 1,
      type: match[1].toLowerCase(),
      title: match[2] || `${match[1]} artifact`,
      content: match[3].trim(),
    });
  }

  while ((match = fence.exec(content))) {
    const type = match[1].toLowerCase() === 'md' ? 'markdown' : match[1].toLowerCase() === 'jsx' ? 'react' : match[1].toLowerCase();
    artifacts.push({
      id: artifacts.length + 1,
      type,
      title: `${type.toUpperCase()} artifact`,
      content: match[2].trim(),
    });
  }

  return artifacts;
}

export function parseArtifactsFromContent(content: string): ParsedArtifact[] {
  return extractArtifacts(content);
}

async function wpFetch(action: string, fields: Record<string, string | Blob | number>, useAdminNonce = false) {
  const config = getWPConfig();
  if (!config) throw new Error('WordPress config not available');

  if (isMockWP(config)) {
    switch (action) {
      case 'aicpp_or_free_models':
      case 'aicpp_or_refresh_free':
        return mockDelay({ models: mockModels });
      case 'aicpp_get_projects':
        return mockDelay({ projects: getMockStore().projects });
      case 'aicpp_create_project': {
        const store = getMockStore();
        const id = (store.projects.at(-1)?.id || 0) + 1;
        store.projects = [...store.projects, { id, name: String(fields.name || 'Project'), description: '', custom_instructions: '' }];
        saveMockStore(store);
        return mockDelay({ id });
      }
      case 'aicpp_delete_project':
        {
          const store = getMockStore();
          store.projects = store.projects.filter((p) => p.id !== Number(fields.project_id));
          saveMockStore(store);
        }
        return mockDelay({ ok: true });
      case 'aicpp_get_memories':
        return mockDelay({ memories: getMockStore().memories.filter((m) => m.user_id === Number(fields.user_id || 0)) });
      case 'aicpp_add_memory': {
        const store = getMockStore();
        const id = (store.memories.at(-1)?.id || 0) + 1;
        store.memories = [
          ...store.memories,
          { id, user_id: Number(fields.user_id || 0), persona_id: Number(fields.persona_id || 1), memory_text: String(fields.memory_text || ''), enabled: 1 },
        ];
        saveMockStore(store);
        return mockDelay({ id });
      }
      case 'aicpp_update_memory':
        {
          const store = getMockStore();
          store.memories = store.memories.map((m) => (m.id === Number(fields.memory_id) ? { ...m, memory_text: String(fields.memory_text || '') } : m));
          saveMockStore(store);
        }
        return mockDelay({ ok: true });
      case 'aicpp_delete_memory':
        {
          const store = getMockStore();
          store.memories = store.memories.filter((m) => m.id !== Number(fields.memory_id));
          saveMockStore(store);
        }
        return mockDelay({ ok: true });
      case 'aicpp_toggle_memory':
        {
          const store = getMockStore();
          store.memories = store.memories.map((m) => (m.id === Number(fields.memory_id) ? { ...m, enabled: m.enabled ? 0 : 1 } : m));
          saveMockStore(store);
        }
        return mockDelay({ ok: true });
      case 'aicpp_pin_conversation': {
        const store = getMockStore();
        const id = Number(fields.conversation_id);
        store.conversations = store.conversations.map((c) => (c.id === id ? { ...c, pinned: Number(fields.pinned ?? (c.pinned ? 0 : 1)) } : c));
        saveMockStore(store);
        return mockDelay({ conversation_id: id, pinned: store.conversations.find((c) => c.id === id)?.pinned || 0 });
      }
      default:
        if (useAdminNonce) return mockDelay({ ok: true });
    }
  }

  // v12: resolve nonce from the manifest when possible
  const nonceFromManifest = resolveNonceForAction(action);
  const nonce = nonceFromManifest
    || (useAdminNonce ? (config.adminNonce || config.nonce) : config.nonce);

  const formData = new FormData();
  formData.append('action', action);
  formData.append('nonce', nonce);
  for (const [key, value] of Object.entries(fields)) {
    if (value === undefined || value === null) continue;
    formData.append(key, typeof value === 'number' ? String(value) : (value as any));
  }

  const response = await fetch(config.ajaxurl, { method: 'POST', body: formData });
  // MISS D: nonce-stale auto-recovery. WP returns 403 or text "-1" when expired.
  const text = await response.text();
  if ((response.status === 403 || text.trim() === '-1') && !(fields as any).__retried) {
    try { sessionStorage.setItem('aicpp_nonce_stale', '1'); } catch {}
    // Reload once to let WP re-localize fresh nonces via the bridge.
    if (!sessionStorage.getItem('aicpp_reloaded_for_nonce')) {
      sessionStorage.setItem('aicpp_reloaded_for_nonce', '1');
      location.reload();
      throw new Error('Refreshing session…');
    }
  }
  if (!response.ok) throw new Error(`Server error: ${response.status}`);
  let result: any;
  try { result = JSON.parse(text); } catch { throw new Error('Bad response'); }
  if (!result.success) throw new Error(result.data?.message || `${action} failed`);
  // Clear the reload guard on the first successful call
  try { sessionStorage.removeItem('aicpp_reloaded_for_nonce'); } catch {}
  return result.data;
}

/**
 * Walk the v12 endpoint manifest to find which nonce group an action belongs to,
 * then return the matching nonce from window.versace22_chat.nonces.
 */
function resolveNonceForAction(action: string): string {
  const eps = getEndpoints();
  const nonces = getNonces();
  for (const group of Object.keys(eps)) {
    for (const key of Object.keys(eps[group])) {
      const entry = eps[group][key];
      if (entry?.action === action && entry.nonce && nonces[entry.nonce]) {
        return nonces[entry.nonce];
      }
    }
  }
  return '';
}

export function isWordPress(): boolean {
  return getWPConfig() !== null;
}

export function isPreviewMock(): boolean {
  return isMockWP(getWPConfig());
}

export function isWPAdmin(): boolean {
  return !!getWPConfig()?.isAdmin;
}

export function getWPPersonaId(): number {
  return getWPConfig()?.personaId ?? 1;
}

export function getWPSessionId(): string {
  // MISS B: persist session_id across reloads so conversations continue.
  try {
    const SESSION_KEY = 'aicpp_session_id';
    let s = localStorage.getItem(SESSION_KEY);
    if (!s) {
      s = getWPConfig()?.sessionId
        || ('sess_' + Math.random().toString(36).slice(2) + Date.now().toString(36));
      localStorage.setItem(SESSION_KEY, s);
    }
    return s;
  } catch {
    return getWPConfig()?.sessionId ?? '';
  }
}

/** Start a fresh session (clears persistence). */
export function newWPSession(): string {
  const s = 'sess_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
  try { localStorage.setItem('aicpp_session_id', s); } catch {}
  return s;
}

export function getWPUserId(): number {
  return getWPConfig()?.userId ?? 0;
}

export function isWPPreviewMock(): boolean {
  return isMockWP(getWPConfig());
}

export function hasWPGoogleLogin(): boolean {
  const config = getWPConfig();
  return !!config && (isMockWP(config) || !!config.googleLoginUrl);
}

export async function signInWithGoogleWP(): Promise<void> {
  const config = getWPConfig();
  if (!config) throw new Error('WordPress config not available');

  if (isMockWP(config)) {
    const store = getMockStore();
    let googleUser = store.users.find((user) => user.email.toLowerCase() === 'google.user@example.com');

    if (!googleUser) {
      googleUser = {
        user_id: store.users.length + 1,
        username: 'google.user',
        email: 'google.user@example.com',
        password: '__google_oauth__',
        display_name: 'Google User',
        avatar: '',
        is_admin: false,
      };
      store.users = [...store.users, googleUser];
      saveMockStore(store);
    }

    setMockWPUser(googleUser);
    return mockDelay(undefined);
  }

  if (!config.googleLoginUrl) {
    throw new Error('Google sign-in is not configured in WordPress yet');
  }

  window.location.href = config.googleLoginUrl;
}

export async function sendMessageToWP(
  message: string,
  attachment?: { url: string; type: string; data?: string } | null,
): Promise<string> {
  const result = await sendPersonaChatToWP(message, getWPPersonaId(), getWPSessionId(), attachment);
  return result.message;
}

export async function sendMainChatToWP(
  message: string,
  sessionId: string,
  _attachment?: { url: string; type: string; data?: string } | null,
): Promise<WPChatResponse> {
  const config = getWPConfig();
  if (!config) throw new Error('WordPress config not available');
  if (isMockWP(config)) {
    const conversation = ensureMockConversation(sessionId, null, true, message, config.userId || 0);
    conversation.messages.push({ role: 'user', content: message });
    const reply = buildMockReply(message, mockMainCharacter.name);
    conversation.messages.push({ role: 'assistant', content: reply });
    conversation.updated_at = new Date().toISOString();
    conversation.token_count += message.length + reply.length;
    saveMockStore(getMockStore());
    return mockDelay({ message: reply, conversation_id: conversation.id, artifacts: extractArtifacts(reply) });
  }

  const formData = new FormData();
  formData.append('action', 'aicpp_chat_main');
  formData.append('nonce', config.nonce);
  formData.append('message', message);
  formData.append('session_id', sessionId);
  const response = await fetch(config.ajaxurl, { method: 'POST', body: formData });
  if (!response.ok) throw new Error(`Server error: ${response.status}`);
  const result = await response.json();
  if (!result.success) throw new Error(result.data?.message || 'Main chat request failed');
  return result.data;
}

export async function sendPersonaChatToWP(
  message: string,
  personaId: number,
  sessionId: string,
  _attachment?: { url: string; type: string; data?: string } | null,
): Promise<WPChatResponse> {
  const config = getWPConfig();
  if (!config) throw new Error('WordPress config not available');
  if (isMockWP(config)) {
    const persona = mockPersonas.find((item) => item.id === personaId) || mockPersonas[0];
    const conversation = ensureMockConversation(sessionId, persona.id, false, message, config.userId || 0);
    conversation.messages.push({ role: 'user', content: message });
    const reply = buildMockReply(message, persona.name);
    conversation.messages.push({ role: 'assistant', content: reply });
    conversation.updated_at = new Date().toISOString();
    conversation.token_count += message.length + reply.length;
    saveMockStore(getMockStore());
    return mockDelay({ message: reply, conversation_id: conversation.id, artifacts: extractArtifacts(reply) });
  }

  const formData = new FormData();
  formData.append('action', 'aicpp_chat');
  formData.append('nonce', config.nonce);
  formData.append('persona_id', String(personaId));
  formData.append('message', message);
  formData.append('session_id', sessionId);
  const response = await fetch(config.ajaxurl, { method: 'POST', body: formData });
  if (!response.ok) throw new Error(`Server error: ${response.status}`);
  const result = await response.json();
  if (!result.success) throw new Error(result.data?.message || 'Chat request failed');
  return result.data;
}

export async function getMyPersonasFromWP(): Promise<{ personas: WPPersonaInfo[]; main_character: WPMainCharacter | null }> {
  const config = getWPConfig();
  if (!config) return { personas: [], main_character: null };
  if (isMockWP(config)) return mockDelay({ personas: mockPersonas, main_character: mockMainCharacter });
  const formData = new FormData();
  formData.append('action', 'aicpp_get_my_personas');
  formData.append('nonce', config.nonce);
  const response = await fetch(config.ajaxurl, { method: 'POST', body: formData });
  const result = await response.json();
  return result.success ? { personas: result.data.personas || [], main_character: result.data.main_character || null } : { personas: [], main_character: null };
}

export async function uploadFileToWP(file: File): Promise<{ file_url: string; file_name: string; file_type: string; file_data?: string }> {
  const config = getWPConfig();
  if (!config) throw new Error('WordPress config not available');
  if (isMockWP(config)) {
    return mockDelay({ file_url: URL.createObjectURL(file), file_name: file.name, file_type: file.type || 'application/octet-stream' });
  }
  return wpFetch('aicpp_upload_file', { file });
}

export async function transcribeAudioWP(_audioBlob: Blob): Promise<string> {
  const config = getWPConfig();
  if (!config) throw new Error('WordPress config not available');
  if (isMockWP(config)) return mockDelay('Preview transcription for the v12 interface.');
  const data = await wpFetch('aicpp_transcribe_audio', { audio: new File([_audioBlob], 'recording.webm') });
  return data.text;
}

export async function speakTextWP(text: string): Promise<string> {
  const config = getWPConfig();
  if (!config) throw new Error('WordPress config not available');
  if (isMockWP(config)) throw new Error('Audio playback is not available in the local preview.');
  const data = await wpFetch('aicpp_speak', { text, voice: 'alloy' });
  return data.audio as string;
}

export async function getConversationsFromWP(): Promise<WPConversation[]> {
  const config = getWPConfig();
  if (!config) return [];
  if (isMockWP(config)) {
    return mockDelay(
      getMockStore().conversations
        .filter((item) => item.user_id === config.userId || item.user_id === 0)
        .map(({ messages, session_id, user_id, ...conversation }) => ({ ...conversation }))
        .sort(
        (a, b) => +new Date(b.updated_at) - +new Date(a.updated_at),
      ),
    );
  }
  const data = await wpFetch('aicpp_get_conversations', { session_id: config.sessionId });
  return data.conversations || [];
}

export async function loadConversationFromWP(conversationId: number): Promise<{ messages: WPMessage[]; session_id: string; persona_id: number; is_main_chat: number } | null> {
  const config = getWPConfig();
  if (!config) return null;
  if (isMockWP(config)) {
    const conversation = getMockStore().conversations.find((item) => item.id === conversationId && (item.user_id === config.userId || item.user_id === 0));
    return mockDelay(
      conversation
        ? {
            messages: conversation.messages,
            session_id: conversation.session_id,
            persona_id: conversation.persona_id || 0,
            is_main_chat: conversation.is_main_chat,
          }
        : null,
    );
  }
  try {
    return await wpFetch('aicpp_load_conversation', { conversation_id: conversationId });
  } catch {
    return null;
  }
}

export async function deleteConversationFromWP(conversationId: number): Promise<boolean> {
  const config = getWPConfig();
  if (!config) return false;
  if (isMockWP(config)) {
    const store = getMockStore();
    store.conversations = store.conversations.filter((item) => item.id !== conversationId);
    saveMockStore(store);
    return mockDelay(true);
  }
  try {
    await wpFetch('aicpp_delete_conversation', { conversation_id: conversationId });
    return true;
  } catch {
    return false;
  }
}

export async function pinConversationWP(conversationId: number, pinned?: boolean): Promise<{ conversation_id: number; pinned: number }> {
  return wpFetch('aicpp_pin_conversation', { conversation_id: conversationId, pinned: pinned ? 1 : 0 });
}

export async function registerUserWP(data: { username: string; email: string; password: string; display_name?: string }): Promise<{ user_id: number; display_name: string }> {
  const config = getWPConfig();
  if (!config) throw new Error('WordPress config not available');
  if (isMockWP(config)) {
    const store = getMockStore();
    const normalizedUsername = data.username.trim().toLowerCase();
    const normalizedEmail = data.email.trim().toLowerCase();
    if (!normalizedUsername || !normalizedEmail || !data.password.trim()) {
      throw new Error('Please complete all required fields');
    }
    if (data.password.trim().length < 8) {
      throw new Error('Password must be at least 8 characters');
    }
    if (store.users.some((user) => user.username.toLowerCase() === normalizedUsername)) {
      throw new Error('That username is already registered');
    }
    if (store.users.some((user) => user.email.toLowerCase() === normalizedEmail)) {
      throw new Error('That email is already registered');
    }

    const newUser = {
      user_id: store.users.length + 1,
      username: data.username.trim(),
      email: data.email.trim(),
      password: data.password,
      display_name: data.display_name?.trim() || data.username.trim(),
      avatar: '',
      is_admin: store.users.length === 0,
    };

    store.users = [...store.users, newUser];
    saveMockStore(store);
    setMockWPUser(newUser);

    return mockDelay({ user_id: newUser.user_id, display_name: newUser.display_name });
  }
  const formData = new FormData();
  formData.append('action', 'aicpp_register_user');
  formData.append('nonce', config.registerNonce || config.nonce);
  formData.append('username', data.username);
  formData.append('email', data.email);
  formData.append('password', data.password);
  if (data.display_name) formData.append('display_name', data.display_name);
  const response = await fetch(config.ajaxurl, { method: 'POST', body: formData });
  if (!response.ok) throw new Error(`Registration error: ${response.status}`);
  const result = await response.json();
  if (!result.success) throw new Error(result.data?.message || 'Registration failed');
  return result.data;
}

export async function loginUserWP(data: { login: string; password: string }): Promise<{ user_id: number; display_name: string; message: string }> {
  const config = getWPConfig();
  if (!config) throw new Error('WordPress config not available');
  if (isMockWP(config)) {
    const login = data.login.trim().toLowerCase();
    const user = getMockStore().users.find(
      (item) => item.username.toLowerCase() === login || item.email.toLowerCase() === login,
    );
    if (!user || user.password !== data.password) {
      throw new Error('Invalid username/email or password');
    }
    setMockWPUser(user);
    return mockDelay({ user_id: user.user_id, display_name: user.display_name, message: 'Signed in' });
  }
  const formData = new FormData();
  formData.append('action', 'aicpp_login_user');
  formData.append('nonce', config.loginNonce || config.nonce);
  formData.append('login', data.login);
  formData.append('password', data.password);
  const response = await fetch(config.ajaxurl, { method: 'POST', body: formData });
  if (!response.ok) throw new Error(`Login error: ${response.status}`);
  const result = await response.json();
  if (!result.success) throw new Error(result.data?.message || 'Login failed');
  return result.data;
}

export async function getMemoriesWP(userId: number): Promise<WPMemory[]> {
  const data = await wpFetch('aicpp_get_memories', { user_id: userId }, true);
  return data.memories || [];
}

export async function addMemoryWP(userId: number, personaId: number, text: string): Promise<number> {
  const data = await wpFetch('aicpp_add_memory', { user_id: userId, persona_id: personaId, memory_text: text }, true);
  return data.id as number;
}

export async function updateMemoryWP(memoryId: number, text: string): Promise<void> {
  await wpFetch('aicpp_update_memory', { memory_id: memoryId, memory_text: text }, true);
}

export async function deleteMemoryWP(memoryId: number): Promise<void> {
  await wpFetch('aicpp_delete_memory', { memory_id: memoryId }, true);
}

export async function toggleMemoryWP(memoryId: number): Promise<void> {
  await wpFetch('aicpp_toggle_memory', { memory_id: memoryId }, true);
}

export async function getProjectsWP(): Promise<WPProject[]> {
  const data = await wpFetch('aicpp_get_projects', {}, true);
  return data.projects || [];
}

export async function createProjectWP(name: string, description = '', customInstructions = ''): Promise<number> {
  const data = await wpFetch('aicpp_create_project', { name, description, custom_instructions: customInstructions }, true);
  return data.id as number;
}

export async function deleteProjectWP(id: number): Promise<void> {
  await wpFetch('aicpp_delete_project', { project_id: id }, true);
}

export async function getORFreeModelsWP(): Promise<WPORModel[]> {
  const data = await wpFetch('aicpp_or_free_models', {}, true);
  return data.models || [];
}

export async function refreshORFreeModelsWP(): Promise<WPORModel[]> {
  const data = await wpFetch('aicpp_or_refresh_free', {}, true);
  return data.models || [];
}

export function getWPUserInfo(): { isLoggedIn: boolean; displayName: string } {
  const w = window as any;
  if (w.versace22_chat?.user_logged_in) {
    return { isLoggedIn: true, displayName: w.versace22_chat.user_display_name || 'User' };
  }
  if (w.versace22_chat) return { isLoggedIn: false, displayName: 'Guest' };
  return { isLoggedIn: false, displayName: 'Guest' };
}

// ============================================================
// Additional WP bridge wrappers — full plugin endpoint coverage
// ============================================================

export async function updateProfileWP(fields: {
  display_name?: string;
  email?: string;
  bio?: string;
  avatar_url?: string;
}): Promise<any> {
  return wpFetch('aicpp_update_profile', fields as any);
}

export async function getLeaderboardWP(): Promise<any> {
  return wpFetch('aicpp_get_leaderboard', {});
}

export async function getReferralDataWP(): Promise<any> {
  return wpFetch('aicpp_get_referral_data', {});
}

export async function searchMessagesWP(query: string): Promise<any> {
  return wpFetch('aicpp_search_messages', { query });
}

export async function updateProjectWP(
  id: number,
  fields: { name?: string; description?: string; custom_instructions?: string },
): Promise<any> {
  return wpFetch('aicpp_update_project', { project_id: id, ...fields } as any, true);
}

export async function attachProjectFileWP(projectId: number, file: Blob): Promise<any> {
  return wpFetch('aicpp_attach_project_file', { project_id: projectId, file }, true);
}

export async function detachProjectFileWP(projectId: number, fileId: number): Promise<any> {
  return wpFetch('aicpp_detach_project_file', { project_id: projectId, file_id: fileId }, true);
}

export async function assignConversationProjectWP(
  conversationId: string,
  projectId: number,
): Promise<any> {
  return wpFetch(
    'aicpp_assign_conversation_project',
    { conversation_id: conversationId, project_id: projectId },
    true,
  );
}

export async function saveArtifactWP(payload: {
  title: string;
  type: string;
  content: string;
  language?: string;
  conversation_id?: string;
}): Promise<any> {
  return wpFetch('aicpp_save_artifact', payload as any, true);
}

export async function getArtifactWP(id: number): Promise<any> {
  return wpFetch('aicpp_get_artifact', { artifact_id: id }, true);
}

export async function listArtifactsWP(): Promise<any> {
  return wpFetch('aicpp_list_artifacts', {}, true);
}

export async function deleteArtifactWP(id: number): Promise<void> {
  await wpFetch('aicpp_delete_artifact', { artifact_id: id }, true);
}

// ============================================================
// v12: Persona admin endpoints (admin-only, manage_options)
// ============================================================

export async function getPersonaWP(id: number): Promise<any> {
  return wpFetch('aicpp_get_persona', { persona_id: id }, true);
}

export async function savePersonaWP(payload: {
  id?: number;
  name: string;
  description?: string;
  model?: string;
  visibility?: 'public' | 'private';
  avatar_initials?: string;
  avatar_color?: string;
  system_prompt?: string;
}): Promise<{ id: number }> {
  return wpFetch('aicpp_save_persona', payload as any, true);
}

export async function deletePersonaWP(id: number): Promise<void> {
  await wpFetch('aicpp_delete_persona', { persona_id: id }, true);
}

export async function assignPersonaWP(personaId: number, userId: number): Promise<void> {
  await wpFetch('aicpp_assign_persona', { persona_id: personaId, user_id: userId }, true);
}

export async function unassignPersonaWP(personaId: number, userId: number): Promise<void> {
  await wpFetch('aicpp_unassign_persona', { persona_id: personaId, user_id: userId }, true);
}

export async function bulkAssignPersonaWP(personaId: number, userIds: number[]): Promise<void> {
  await wpFetch(
    'aicpp_bulk_assign',
    { persona_id: personaId, user_ids: JSON.stringify(userIds) },
    true,
  );
}

export async function getUserPersonasWP(userId: number): Promise<any> {
  return wpFetch('aicpp_get_user_personas', { user_id: userId }, true);
}

export async function getPersonaUsersWP(personaId: number): Promise<any> {
  return wpFetch('aicpp_get_persona_users', { persona_id: personaId }, true);
}

export async function searchUsersWP(query: string): Promise<any> {
  return wpFetch('aicpp_search_users', { query }, true);
}

// TTS alias matching the doc-friendly name
export async function speakWP(text: string, voice = 'alloy'): Promise<string> {
  const data = await wpFetch('aicpp_speak', { text, voice });
  return data.audio as string;
}
