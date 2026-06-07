import { useState } from 'react';
import { X } from 'lucide-react';
import { hasWPGoogleLogin, loginUserWP, registerUserWP, signInWithGoogleWP, isPreviewMock } from '@/lib/wp-api';
import { supabase } from '@/integrations/supabase/client';
import { lovable } from '@/integrations/lovable';
import { toast } from 'sonner';

interface WPAuthModalProps {
  open: boolean;
  onClose: () => void;
}

export function WPAuthModal({ open, onClose }: WPAuthModalProps) {
  const [mode, setMode] = useState<'login' | 'register' | 'forgot'>('login');
  const [login, setLogin] = useState('');
  const [email, setEmail] = useState('');
  const [username, setUsername] = useState('');
  const [displayName, setDisplayName] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const previewMock = isPreviewMock();
  // In the preview, Google works via Supabase OAuth. On WP, requires plugin config.
  const googleEnabled = previewMock || hasWPGoogleLogin();

  if (!open) return null;

  const resetState = () => {
    setLoading(false);
    setLogin('');
    setEmail('');
    setUsername('');
    setDisplayName('');
    setPassword('');
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      if (previewMock) {
        const { error } = await supabase.auth.signInWithPassword({
          email: login,
          password,
        });
        if (error) throw error;
        toast.success('Signed in!');
        resetState();
        onClose();
      } else {
        await loginUserWP({ login, password });
        window.dispatchEvent(new Event('versace22-wp-auth-changed'));
        toast.success('Signed in!');
        resetState();
        onClose();
      }
    } catch (err: any) {
      toast.error(err.message || 'Login failed');
      setLoading(false);
    }
  };

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      if (previewMock) {
        const { error } = await supabase.auth.signUp({
          email,
          password,
          options: {
            emailRedirectTo: `${window.location.origin}/`,
            data: { display_name: displayName || username || email.split('@')[0] },
          },
        });
        if (error) throw error;
        toast.success('Account created! Check your email to confirm your address.');
        resetState();
        setMode('login');
        setLoading(false);
      } else {
        await registerUserWP({ username, email, password, display_name: displayName });
        window.dispatchEvent(new Event('versace22-wp-auth-changed'));
        toast.success('Account created!');
        resetState();
        onClose();
      }
    } catch (err: any) {
      toast.error(err.message || 'Registration failed');
      setLoading(false);
    }
  };

  const handleForgot = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      if (previewMock) {
        const { error } = await supabase.auth.resetPasswordForEmail(email, {
          redirectTo: `${window.location.origin}/reset-password`,
        });
        if (error) throw error;
        toast.success('Password reset email sent! Check your inbox.');
        setMode('login');
      } else {
        toast.info('Use the WordPress "Lost password?" page on your site.');
      }
    } catch (err: any) {
      toast.error(err.message || 'Could not send reset email');
    } finally {
      setLoading(false);
    }
  };

  const handleGoogle = async () => {
    setLoading(true);
    try {
      if (previewMock) {
        const result = await lovable.auth.signInWithOAuth('google', {
          redirect_uri: `${window.location.origin}/`,
          extraParams: { prompt: 'select_account' },
        });
        if (result.error) throw result.error;
        // Browser redirects to Google; modal closes on return.
      } else {
        await signInWithGoogleWP();
        window.dispatchEvent(new Event('versace22-wp-auth-changed'));
        if (googleEnabled) {
          resetState();
          onClose();
        }
      }
    } catch (err: any) {
      toast.error(err.message || 'Google sign-in failed');
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/70 backdrop-blur-md">
      <div className="relative w-full max-w-sm bg-card/95 border border-border rounded-[22px] p-6 space-y-4 shadow-2xl">
        <button
          onClick={onClose}
          className="absolute right-3 top-3 p-1.5 rounded-full text-muted-foreground hover:text-foreground hover:bg-muted"
        >
          <X className="w-4 h-4" />
        </button>

        <div className="space-y-1 text-center">
          <div className="text-xl font-extrabold text-primary">VERSACE22 AI</div>
          <p className="text-sm text-muted-foreground">Sign in to continue</p>
        </div>

        {mode !== 'forgot' && (
        <div className="flex gap-2 p-1 bg-muted rounded-full">
          <button
            onClick={() => setMode('login')}
            className={`flex-1 py-2 text-sm font-medium rounded-full transition-colors ${
              mode === 'login' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'
            }`}
          >
            Login
          </button>
          <button
            onClick={() => setMode('register')}
            className={`flex-1 py-2 text-sm font-medium rounded-full transition-colors ${
              mode === 'register' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'
            }`}
          >
            Register
          </button>
        </div>
        )}

        {mode === 'login' ? (
          <form onSubmit={handleLogin} className="space-y-3">
            <input
              type={previewMock ? 'email' : 'text'}
              placeholder={previewMock ? 'Email' : 'Email or username'}
              value={login}
              onChange={(e) => setLogin(e.target.value)}
              required
              className="w-full px-4 py-2.5 rounded-xl bg-background border border-border text-sm focus:border-primary focus:outline-none"
            />
            <input
              type="password"
              placeholder="Password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={8}
              className="w-full px-4 py-2.5 rounded-xl bg-background border border-border text-sm focus:border-primary focus:outline-none"
            />
            <div className="text-right">
              <button
                type="button"
                onClick={() => setMode('forgot')}
                className="text-xs text-primary hover:underline"
              >
                Forgot password?
              </button>
            </div>
            <button
              type="submit"
              disabled={loading}
              className="w-full py-2.5 rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 text-sm font-medium disabled:opacity-50"
            >
              {loading ? 'Signing in...' : 'Sign in'}
            </button>
            <button
              type="button"
              onClick={handleGoogle}
              disabled={loading || !googleEnabled}
              className="w-full py-2.5 rounded-xl bg-muted text-foreground hover:bg-secondary text-sm font-medium"
            >
              {googleEnabled ? 'Continue with Google' : 'Google sign-in unavailable'}
            </button>
          </form>
        ) : mode === 'register' ? (
          <form onSubmit={handleRegister} className="space-y-3">
            <h2 className="text-base font-semibold text-foreground">Create an account</h2>
            {!previewMock && (
              <input
                type="text"
                placeholder="Username"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                required
                className="w-full px-4 py-2.5 rounded-xl bg-background border border-border text-sm focus:border-primary focus:outline-none"
              />
            )}
            <input
              type="text"
              placeholder="Display name (optional)"
              value={displayName}
              onChange={(e) => setDisplayName(e.target.value)}
              className="w-full px-4 py-2.5 rounded-xl bg-background border border-border text-sm focus:border-primary focus:outline-none"
            />
            <input
              type="email"
              placeholder="Email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="w-full px-4 py-2.5 rounded-xl bg-background border border-border text-sm focus:border-primary focus:outline-none"
            />
            <input
              type="password"
              placeholder="Password (min 8 characters)"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={8}
              className="w-full px-4 py-2.5 rounded-xl bg-background border border-border text-sm focus:border-primary focus:outline-none"
            />
            <button
              type="submit"
              disabled={loading}
              className="w-full py-2.5 rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 text-sm font-medium disabled:opacity-50"
            >
              {loading ? 'Creating...' : 'Create account'}
            </button>
            <button
              type="button"
              onClick={handleGoogle}
              disabled={loading || !googleEnabled}
              className="w-full py-2.5 rounded-xl bg-muted text-foreground hover:bg-secondary text-sm font-medium"
            >
              {googleEnabled ? 'Sign up with Google' : 'Google sign-up unavailable'}
            </button>
          </form>
        ) : (
          <form onSubmit={handleForgot} className="space-y-3">
            <h2 className="text-base font-semibold text-foreground">Reset your password</h2>
            <p className="text-xs text-muted-foreground">
              Enter your email and we'll send you a link to set a new password.
            </p>
            <input
              type="email"
              placeholder="Email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="w-full px-4 py-2.5 rounded-xl bg-background border border-border text-sm focus:border-primary focus:outline-none"
            />
            <button
              type="submit"
              disabled={loading}
              className="w-full py-2.5 rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 text-sm font-medium disabled:opacity-50"
            >
              {loading ? 'Sending...' : 'Send reset link'}
            </button>
            <button
              type="button"
              onClick={() => setMode('login')}
              className="w-full text-xs text-muted-foreground hover:text-foreground"
            >
              Back to sign in
            </button>
          </form>
        )}
      </div>
    </div>
  );
}