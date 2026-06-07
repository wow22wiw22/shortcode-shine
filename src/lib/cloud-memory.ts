import { supabase } from '@/integrations/supabase/client';

export interface CloudMemory {
  id: string;
  memory_text: string;
  enabled: number;
  created_at: string;
}

interface StoredMemoryPayload {
  text: string;
  enabled: boolean;
}

export const MEMORY_PERSONA_ID = '__memory__';
const MEMORY_CONVERSATION_TITLE = '__system_memories__';

function parseMemoryContent(content: string): StoredMemoryPayload {
  try {
    const parsed = JSON.parse(content) as Partial<StoredMemoryPayload>;
    if (typeof parsed?.text === 'string') {
      return {
        text: parsed.text,
        enabled: parsed.enabled !== false,
      };
    }
  } catch {}

  return {
    text: content,
    enabled: true,
  };
}

function stringifyMemoryContent(memory_text: string, enabled: boolean) {
  return JSON.stringify({ text: memory_text, enabled });
}

async function ensureMemoryConversation(userId: string) {
  const { data: existing } = await supabase
    .from('conversations')
    .select('id')
    .eq('user_id', userId)
    .eq('persona_id', MEMORY_PERSONA_ID)
    .limit(1)
    .maybeSingle();

  if (existing?.id) return existing.id;

  const { data, error } = await supabase
    .from('conversations')
    .insert({
      user_id: userId,
      persona_id: MEMORY_PERSONA_ID,
      title: MEMORY_CONVERSATION_TITLE,
    })
    .select('id')
    .single();

  if (error || !data?.id) {
    throw error ?? new Error('Failed to create memory store');
  }

  return data.id;
}

async function touchConversation(conversationId: string) {
  await supabase
    .from('conversations')
    .update({ updated_at: new Date().toISOString() })
    .eq('id', conversationId);
}

export async function listCloudMemories(userId: string): Promise<CloudMemory[]> {
  const conversationId = await ensureMemoryConversation(userId);
  const { data, error } = await supabase
    .from('messages')
    .select('id, content, created_at')
    .eq('conversation_id', conversationId)
    .eq('user_id', userId)
    .order('created_at', { ascending: false });

  if (error) throw error;

  return (data ?? []).map((item) => {
    const parsed = parseMemoryContent(item.content);
    return {
      id: item.id,
      memory_text: parsed.text,
      enabled: parsed.enabled ? 1 : 0,
      created_at: item.created_at,
    };
  });
}

export async function addCloudMemory(userId: string, memoryText: string) {
  const conversationId = await ensureMemoryConversation(userId);
  const { error } = await supabase.from('messages').insert({
    conversation_id: conversationId,
    user_id: userId,
    role: 'user',
    content: stringifyMemoryContent(memoryText, true),
    persona_id: MEMORY_PERSONA_ID,
  });

  if (error) throw error;
  await touchConversation(conversationId);
}

export async function updateCloudMemory(userId: string, memoryId: string, memoryText: string) {
  const { data: existing, error: existingError } = await supabase
    .from('messages')
    .select('id, content, conversation_id')
    .eq('id', memoryId)
    .eq('user_id', userId)
    .maybeSingle();

  if (existingError || !existing) throw existingError ?? new Error('Memory not found');

  const parsed = parseMemoryContent(existing.content);
  const { error } = await supabase
    .from('messages')
    .update({ content: stringifyMemoryContent(memoryText, parsed.enabled) })
    .eq('id', memoryId)
    .eq('user_id', userId);

  if (error) throw error;
  await touchConversation(existing.conversation_id);
}

export async function toggleCloudMemory(userId: string, memoryId: string) {
  const { data: existing, error: existingError } = await supabase
    .from('messages')
    .select('id, content, conversation_id')
    .eq('id', memoryId)
    .eq('user_id', userId)
    .maybeSingle();

  if (existingError || !existing) throw existingError ?? new Error('Memory not found');

  const parsed = parseMemoryContent(existing.content);
  const { error } = await supabase
    .from('messages')
    .update({ content: stringifyMemoryContent(parsed.text, !parsed.enabled) })
    .eq('id', memoryId)
    .eq('user_id', userId);

  if (error) throw error;
  await touchConversation(existing.conversation_id);
}

export async function deleteCloudMemory(userId: string, memoryId: string) {
  const { data: existing, error: existingError } = await supabase
    .from('messages')
    .select('conversation_id')
    .eq('id', memoryId)
    .eq('user_id', userId)
    .maybeSingle();

  if (existingError || !existing) throw existingError ?? new Error('Memory not found');

  const { error } = await supabase
    .from('messages')
    .delete()
    .eq('id', memoryId)
    .eq('user_id', userId);

  if (error) throw error;
  await touchConversation(existing.conversation_id);
}