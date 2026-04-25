export type Persona = {
  id: string;
  name: string;
  avatar: string;
  tagline: string;
  category: string;
};

export type Message = {
  id: string;
  role: "user" | "assistant";
  content: string;
  ts: number;
};

export type Conversation = {
  id: string;
  title: string;
  personaId: string;
  messages: Message[];
  pinned: boolean;
  updatedAt: number;
};

export type User = {
  id: string;
  username: string;
  email: string;
  avatar: string;
  bio: string;
  joinedAt: number;
  messageCount: number;
  referralCode: string;
  referredCount: number;
};

export type Lang = "en" | "ar" | "fr" | "es";