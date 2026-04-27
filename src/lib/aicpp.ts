// Thin wrapper around the WordPress admin-ajax endpoints exposed by
// `ai-chat-persona-pro99.php`. When the React bundle is loaded inside
// WordPress, the PHP enqueue script injects `window.aicpp_obj` with:
//   { ajax_url: "/wp-admin/admin-ajax.php", nonce: "...", logged_in: bool, ... }
//
// In the Lovable preview that object does not exist, so every helper here
// returns `{ ok: false, offline: true }` and callers fall back to the local
// mock data. That way the same code runs in both environments.

declare global {
  interface Window {
    aicpp_obj?: {
      ajax_url: string;
      nonce: string;
      logged_in?: boolean;
      user_id?: number;
      actions?: Record<string, string>;
    };
  }
}

export type AicppResult<T = any> =
  | { ok: true; data: T }
  | { ok: false; offline?: boolean; error: string };

export function isOnline(): boolean {
  return typeof window !== "undefined" && !!window.aicpp_obj?.ajax_url;
}

export async function aicpp<T = any>(
  action: string,
  payload: Record<string, string | number | boolean | undefined | null> = {}
): Promise<AicppResult<T>> {
  if (!isOnline()) {
    return { ok: false, offline: true, error: "aicpp_obj not available (preview mode)" };
  }
  const cfg = window.aicpp_obj!;
  const body = new FormData();
  body.append("action", action);
  body.append("nonce", cfg.nonce);
  for (const [k, v] of Object.entries(payload)) {
    if (v === undefined || v === null) continue;
    body.append(k, String(v));
  }
  try {
    const res = await fetch(cfg.ajax_url, { method: "POST", body, credentials: "include" });
    const json = await res.json().catch(() => ({}));
    // WP AJAX convention: { success: true, data: ... } or { success: false, data: { message } }
    if (json && json.success) {
      return { ok: true, data: json.data as T };
    }
    const msg =
      (json && json.data && (json.data.message || json.data)) ||
      `Request failed (${res.status})`;
    return { ok: false, error: typeof msg === "string" ? msg : JSON.stringify(msg) };
  } catch (e: any) {
    return { ok: false, error: e?.message || "Network error" };
  }
}