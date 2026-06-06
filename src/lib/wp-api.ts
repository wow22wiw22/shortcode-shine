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
}

interface MockWPUser {
  user_id: number;
  display_name: string;
  email: string;
  avatar: string;
  is_admin: boolean;
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

let mockConversationId = 3;
let mockMemories: WPMemory[] = [
  { id: 1, persona_id: 1, memory_text: 'User prefers exact version matching.', enabled: 1 },
];
let mockRegisteredUsers: Array<MockWPUser & { username: string; password: string }> = [
  {
    user_id: 1,
    username: 'lorenzo',
    email: 'lorenzo@example.com',
    password: 'password123',
    display_name: 'lorenzo',
    avatar: '',
    is_admin: true,
  },
];
let mockProjects: WPProject[] = [
  { id: 1, name: 'Integrated v12 WP API #18', description: '', custom_instructions: '' },
];
let mockConversations: Array<WPConversation & { messages: WPMessage[]; session_id: string }> = [
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
    messages: [
      { role: 'user', content: 'Show me the v12 interface.' },
      { role: 'assistant', content: 'You are looking at the v12 WordPress interface preview.' },
    ],
  },
];

const mockModels: WPORModel[] = [
  { id: 'openrouter/auto', name: 'OpenRouter Auto', context_length: 128000 },
  { id: 'meta-llama/llama-3.1-8b-instruct:free', name: 'Llama 3.1 8B Free', context_length: 131072 },
];

function setMockWPUser(user: MockWPUser | null) {
  const w = window as any;
  if (!w.versace22_chat) return;

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

function ensureMockConversation(sessionId: string, personaId: number | null, isMainChat: boolean, seedTitle: string) {
  let conversation = mockConversations.find(
    (item) => item.session_id === sessionId && item.is_main_chat === (isMainChat ? 1 : 0) && item.persona_id === personaId,
  );
  if (!conversation) {
    conversation = {
      id: ++mockConversationId,
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
      messages: [],
    };
    mockConversations = [conversation, ...mockConversations];
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
        return mockDelay({ projects: mockProjects });
      case 'aicpp_create_project': {
        const id = (mockProjects.at(-1)?.id || 0) + 1;
        mockProjects = [...mockProjects, { id, name: String(fields.name || 'Project'), description: '', custom_instructions: '' }];
        return mockDelay({ id });
      }
      case 'aicpp_delete_project':
        mockProjects = mockProjects.filter((p) => p.id !== Number(fields.project_id));
        return mockDelay({ ok: true });
      case 'aicpp_get_memories':
        return mockDelay({ memories: mockMemories });
      case 'aicpp_add_memory': {
        const id = (mockMemories.at(-1)?.id || 0) + 1;
        mockMemories = [...mockMemories, { id, persona_id: Number(fields.persona_id || 1), memory_text: String(fields.memory_text || ''), enabled: 1 }];
        return mockDelay({ id });
      }
      case 'aicpp_update_memory':
        mockMemories = mockMemories.map((m) => (m.id === Number(fields.memory_id) ? { ...m, memory_text: String(fields.memory_text || '') } : m));
        return mockDelay({ ok: true });
      case 'aicpp_delete_memory':
        mockMemories = mockMemories.filter((m) => m.id !== Number(fields.memory_id));
        return mockDelay({ ok: true });
      case 'aicpp_toggle_memory':
        mockMemories = mockMemories.map((m) => (m.id === Number(fields.memory_id) ? { ...m, enabled: m.enabled ? 0 : 1 } : m));
        return mockDelay({ ok: true });
      case 'aicpp_pin_conversation': {
        const id = Number(fields.conversation_id);
        mockConversations = mockConversations.map((c) => (c.id === id ? { ...c, pinned: Number(fields.pinned ?? (c.pinned ? 0 : 1)) } : c));
        return mockDelay({ conversation_id: id, pinned: mockConversations.find((c) => c.id === id)?.pinned || 0 });
      }
      default:
        if (useAdminNonce) return mockDelay({ ok: true });
    }
  }

  const formData = new FormData();
  formData.append('action', action);
  formData.append('nonce', useAdminNonce ? (config.adminNonce || config.nonce) : config.nonce);
  for (const [key, value] of Object.entries(fields)) {
    if (value === undefined || value === null) continue;
    formData.append(key, typeof value === 'number' ? String(value) : (value as any));
  }

  const response = await fetch(config.ajaxurl, { method: 'POST', body: formData });
  if (!response.ok) throw new Error(`Server error: ${response.status}`);
  const result = await response.json();
  if (!result.success) throw new Error(result.data?.message || `${action} failed`);
  return result.data;
}

export function isWordPress(): boolean {
  return getWPConfig() !== null;
}

export function isWPAdmin(): boolean {
  return !!getWPConfig()?.isAdmin;
}

export function getWPPersonaId(): number {
  return getWPConfig()?.personaId ?? 1;
}

export function getWPSessionId(): string {
  return getWPConfig()?.sessionId ?? '';
}

export function getWPUserId(): number {
  return getWPConfig()?.userId ?? 0;
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
    const conversation = ensureMockConversation(sessionId, null, true, message);
    conversation.messages.push({ role: 'user', content: message });
    const reply = buildMockReply(message, mockMainCharacter.name);
    conversation.messages.push({ role: 'assistant', content: reply });
    conversation.updated_at = new Date().toISOString();
    conversation.token_count += message.length + reply.length;
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
    const conversation = ensureMockConversation(sessionId, persona.id, false, message);
    conversation.messages.push({ role: 'user', content: message });
    const reply = buildMockReply(message, persona.name);
    conversation.messages.push({ role: 'assistant', content: reply });
    conversation.updated_at = new Date().toISOString();
    conversation.token_count += message.length + reply.length;
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
      mockConversations.map(({ messages, session_id, ...conversation }) => ({ ...conversation })).sort(
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
    const conversation = mockConversations.find((item) => item.id === conversationId);
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
    mockConversations = mockConversations.filter((item) => item.id !== conversationId);
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
    const normalizedUsername = data.username.trim().toLowerCase();
    const normalizedEmail = data.email.trim().toLowerCase();
    if (!normalizedUsername || !normalizedEmail || !data.password.trim()) {
      throw new Error('Please complete all required fields');
    }
    if (data.password.trim().length < 8) {
      throw new Error('Password must be at least 8 characters');
    }
    if (mockRegisteredUsers.some((user) => user.username.toLowerCase() === normalizedUsername)) {
      throw new Error('That username is already registered');
    }
    if (mockRegisteredUsers.some((user) => user.email.toLowerCase() === normalizedEmail)) {
      throw new Error('That email is already registered');
    }

    const newUser = {
      user_id: mockRegisteredUsers.length + 1,
      username: data.username.trim(),
      email: data.email.trim(),
      password: data.password,
      display_name: data.display_name?.trim() || data.username.trim(),
      avatar: '',
      is_admin: mockRegisteredUsers.length === 0,
    };

    mockRegisteredUsers = [...mockRegisteredUsers, newUser];
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
    const user = mockRegisteredUsers.find(
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
