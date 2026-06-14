/**
 * WordPress entry point — mounts into #versace22-chat-root
 * Uses BrowserRouter in the Lovable preview so auth redirect routes work correctly.
 * In WP mode: uses WPAuthProvider (cookie-based WP auth) instead of Supabase auth.
 * Guest users can chat without logging in; logged-in WP users get history.
 */
import { createRoot } from "react-dom/client";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Route, Routes, Navigate } from "react-router-dom";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { Toaster } from "@/components/ui/toaster";
import { TooltipProvider } from "@/components/ui/tooltip";
import { WPAuthProvider } from "@/hooks/useWPAuth";
import { AuthProvider, useAuth } from "@/hooks/useAuth";
import { ThemeProvider } from "@/hooks/useTheme";
import Index from "./pages/Index";
import Auth from "./pages/Auth";
import ResetPassword from "./pages/ResetPassword";
import "./wp-index.css";

const queryClient = new QueryClient();

/**
 * In WordPress mode, we bypass Supabase auth entirely.
 * The WPAuthProvider gives the app access to WP user state.
 * We still wrap with Supabase AuthProvider so the useAuth hook doesn't crash,
 * but the ProtectedRoute allows all users through in WP mode (guests can chat).
 */
function WPProtectedRoute({ children }: { children: React.ReactNode }) {
  // In WP mode, always allow access — WordPress handles auth via cookies.
  // Both guests and logged-in users can use the chat widget.
  return <>{children}</>;
}

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { user, loading } = useAuth();
  if (loading) return (
    <div className="min-h-dvh bg-background flex items-center justify-center">
      <div className="text-primary animate-pulse text-lg font-medium">Loading...</div>
    </div>
  );
  if (!user) return <Navigate to="/auth" replace />;
  return <>{children}</>;
}

function AuthRoute({ children }: { children: React.ReactNode }) {
  const { user, loading } = useAuth();
  if (loading) return null;
  if (user) return <Navigate to="/" replace />;
  return <>{children}</>;
}

const WPApp = () => (
  <QueryClientProvider client={queryClient}>
    <ThemeProvider>
      <WPAuthProvider>
        <AuthProvider>
          <TooltipProvider>
            <Toaster />
            <Sonner />
            <BrowserRouter>
              <Routes>
                <Route path="/auth" element={<AuthRoute><Auth /></AuthRoute>} />
                <Route path="/reset-password" element={<ResetPassword />} />
                <Route path="/" element={<WPProtectedRoute><Index /></WPProtectedRoute>} />
              </Routes>
            </BrowserRouter>
          </TooltipProvider>
        </AuthProvider>
      </WPAuthProvider>
    </ThemeProvider>
  </QueryClientProvider>
);

/**
 * Resilient mount — handles:
 *  - Bridge root #versace22-chat-root (primary)
 *  - Plugin standalone fallback #aicpp-standalone-root-*
 *  - Generic #root (local dev)
 *  - Themes/page builders that inject the wrapper after DOMContentLoaded (MutationObserver)
 *  - Clears any server-side "Loading…" fallback markup before mount (MISS H)
 */
function aicppFindMount(): HTMLElement | null {
  return (
    document.getElementById("versace22-chat-root") ||
    document.querySelector<HTMLElement>('[id^="aicpp-standalone-root-"]') ||
    document.getElementById("root")
  );
}

function aicppMount(el: HTMLElement) {
  if ((el as any).__aicppMounted) return;
  (el as any).__aicppMounted = true;
  el.innerHTML = "";
  createRoot(el).render(<WPApp />);
  try {
    const h = (window as any).versace22_chat;
    const supported = ["v12", "v12.3", "v13"];
    if (h?.bridge_version && !supported.includes(String(h.bridge_version))) {
      // eslint-disable-next-line no-console
      console.warn(
        `[aicpp] Bridge version ${h.bridge_version} not in supported set ${supported.join(",")}. UI will fall back to the static endpoint map.`,
      );
    }
  } catch {}
}

function aicppBoot() {
  const el = aicppFindMount();
  if (el) { aicppMount(el); return; }

  const obs = new MutationObserver(() => {
    const found = aicppFindMount();
    if (found) { obs.disconnect(); aicppMount(found); }
  });
  obs.observe(document.documentElement, { childList: true, subtree: true });
  setTimeout(() => obs.disconnect(), 10000);

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", aicppBoot, { once: true });
  } else {
    // Last-resort fallback so local dev / unmounted previews still render
    setTimeout(() => {
      if (aicppFindMount()) return;
      const fb = document.createElement("div");
      fb.id = "versace22-chat-root";
      document.body.appendChild(fb);
      aicppMount(fb);
    }, 250);
  }
}

aicppBoot();
