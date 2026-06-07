import { supabase } from '@/integrations/supabase/client';

export interface CloudMemory {
  id: string;
  user_id: string;
  persona_id: string;
  memory_text: string;
  enabled: boolean;
}

export async function listCloudMemories(userId: string): Promise<CloudMemory[]> {
  const { data, error } = await supabase
    .from('memories')
    .select('id, user_id, persona_id, memory_text, enabled')
    .eq('user_id', userId)
    .order('created_at', { ascending: false });

  if (error) throw error;
  return data || [];
}

export async function addCloudMemory(userId: string, personaId: string, text: string): Promise<void> {
  const { error } = await supabase.from('memories').insert({
    user_id: userId,
    persona_id: personaId,
    memory_text: text,
  });

  if (error) throw error;
}

export async function updateCloudMemory(id: string, memoryText: string): Promise<void> {
  const { error } = await supabase.from('memories').update({ memory_text: memoryText }).eq('id', id);
  if (error) throw error;
}

export async function toggleCloudMemory(id: string, enabled: boolean): Promise<void> {
  const { error } = await supabase.from('memories').update({ enabled }).eq('id', id);
  if (error) throw error;
}

export async function deleteCloudMemory(id: string): Promise<void> {
  const { error } = await supabase.from('memories').delete().eq('id', id);
  if (error) throw error;
}