CREATE TABLE public.memories (
  id UUID NOT NULL DEFAULT gen_random_uuid() PRIMARY KEY,
  user_id UUID NOT NULL,
  persona_id TEXT NOT NULL DEFAULT '1',
  memory_text TEXT NOT NULL,
  enabled BOOLEAN NOT NULL DEFAULT true,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
GRANT SELECT, INSERT, UPDATE, DELETE ON public.memories TO authenticated;
GRANT ALL ON public.memories TO service_role;
ALTER TABLE public.memories ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Users can view their own memories"
ON public.memories FOR SELECT
USING (auth.uid() = user_id);
CREATE POLICY "Users can create their own memories"
ON public.memories FOR INSERT
WITH CHECK (auth.uid() = user_id);
CREATE POLICY "Users can update their own memories"
ON public.memories FOR UPDATE
USING (auth.uid() = user_id)
WITH CHECK (auth.uid() = user_id);
CREATE POLICY "Users can delete their own memories"
ON public.memories FOR DELETE
USING (auth.uid() = user_id);
CREATE TRIGGER update_memories_updated_at
BEFORE UPDATE ON public.memories
FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();
CREATE INDEX idx_memories_user_id ON public.memories(user_id);