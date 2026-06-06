import { useState, useEffect, createContext, useContext, ReactNode } from 'react';
import { User, Session } from '@supabase/supabase-js';
import { supabase } from '@/integrations/supabase/client';

interface AuthContextType {
  user: User | null;
  session: Session | null;
  loading: boolean;
  signOut: () => Promise<void>;
  profile: { display_name: string | null; avatar_url: string | null } | null;
}

const AuthContext = createContext<AuthContextType>({
  user: null,
  session: null,
  loading: true,
  signOut: async () => {},
  profile: null,
});

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [session, setSession] = useState<Session | null>(null);
  const [loading, setLoading] = useState(true);
  const [profile, setProfile] = useState<AuthContextType['profile']>(null);

  useEffect(() => {
    const wpCfg = (window as any)?.versace22_chat;
    if (wpCfg) {
      const syncWPAuth = () => {
        const currentCfg = (window as any)?.versace22_chat;
        const isLoggedIn = !!currentCfg?.user_logged_in;
        setSession(null);
        setUser(
          isLoggedIn
            ? ({
                id: String(currentCfg?.user_id || 'wp-user'),
                email: currentCfg?.user_email || undefined,
                created_at: new Date().toISOString(),
              } as User)
            : null,
        );
        setProfile({
          display_name: isLoggedIn ? currentCfg?.user_display_name || 'User' : null,
          avatar_url: currentCfg?.user_avatar || null,
        });
        setLoading(false);
      };

      syncWPAuth();
      window.addEventListener('versace22-wp-auth-changed', syncWPAuth as EventListener);
      window.addEventListener('storage', syncWPAuth as EventListener);

      return () => {
        window.removeEventListener('versace22-wp-auth-changed', syncWPAuth as EventListener);
        window.removeEventListener('storage', syncWPAuth as EventListener);
      };
    }

    const { data: { subscription } } = supabase.auth.onAuthStateChange((_event, session) => {
      setSession(session);
      setUser(session?.user ?? null);
      setLoading(false);

      if (session?.user) {
        setTimeout(() => {
          supabase
            .from('profiles')
            .select('display_name, avatar_url')
            .eq('user_id', session.user.id)
            .single()
            .then(({ data }) => {
              if (data) setProfile(data);
            });
        }, 0);
      } else {
        setProfile(null);
      }
    });

    supabase.auth.getSession().then(({ data: { session } }) => {
      setSession(session);
      setUser(session?.user ?? null);
      setLoading(false);
    });

    return () => subscription.unsubscribe();
  }, []);

  const signOut = async () => {
    const wpCfg = (window as any)?.versace22_chat;
    if (wpCfg?.ajaxurl?.includes('/wp-mock/')) {
      try {
        localStorage.removeItem('versace22-mock-user');
      } catch {}
      wpCfg.user_logged_in = false;
      wpCfg.user_id = 0;
      wpCfg.user_display_name = '';
      wpCfg.user_email = '';
      wpCfg.user_avatar = '';
      wpCfg.is_admin = false;
      window.dispatchEvent(new Event('versace22-wp-auth-changed'));
      return;
    }
    if (wpCfg?.logout_url) {
      window.location.href = wpCfg.logout_url;
      return;
    }
    await supabase.auth.signOut();
  };

  return (
    <AuthContext.Provider value={{ user, session, loading, signOut, profile }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
