/**
 * aicpp-bridge — single source of truth for talking to the versace22 bridge / plugin.
 *
 * The reports (x, y, w) all converge on the same architectural requirement:
 * a dedicated adapter module that (a) reads the v12 handshake at CALL time
 * (never at module-eval time — MISS G), (b) resolves action+nonce from the
 * bridge manifest first and falls back to a static map when an older bridge
 * is loaded, (c) auto-recovers from stale nonces (MISS D), (d) persists the
 * session id across reloads (MISS B), and (e) warns when the bridge version
 * is not in the supported set (MISS J).
 *
 * The existing src/lib/wp-api.ts continues to expose the typed wrappers the
 * UI imports; this module is the lower-level transport those wrappers can
 * use, and is the artifact the reports explicitly asked to ship.
 */

type EndpointEntry = { action: string; nonce: string; nopriv?: boolean };

type Handshake = {
  ajaxurl: string;
  rest_url?: string;
  transport?: string;
  nonce?: string;
  nonces?: Record<string, string>;
  endpoints?: Record<string, Record<string, EndpointEntry>>;
  can?: Record<string, boolean>;
  persona_id?: string | number;
  session_id?: string;
  user_logged_in?: boolean;
  is_admin?: boolean;
  user_id?: number | string;
  user_display_name?: string;
  user_email?: string;
  user_avatar?: string;
  login_url?: string;
  logout_url?: string;
  register_url?: string;
  plugin_version?: string;
  bridge_version?: string;
};

declare global {
  interface Window {
    versace22_chat?: Handshake;
    aicppStandalone?: { ajaxUrl: string; nonce: string };
  }
}

/** Always read the handshake at CALL time — never cache at module-eval (MISS G). */
export function hs(): Handshake {
  if (typeof window === "undefined") return { ajaxurl: "/wp-admin/admin-ajax.php" };
  if (window.versace22_chat) return window.versace22_chat;
  if (window.aicppStandalone) {
    return { ajaxurl: window.aicppStandalone.ajaxUrl, nonce: window.aicppStandalone.nonce };
  }
  return { ajaxurl: "/wp-admin/admin-ajax.php" };
}

/** Static fallback used only when the bridge did not send an endpoints manifest. */
const FALLBACK: Record<string, [string, string]> = {
  "chat.chat": ["aicpp_chat", "aicpp_chat"],
  "chat.chat_main": ["aicpp_chat_main", "aicpp_chat"],
  "chat.transcribe_audio": ["aicpp_transcribe_audio", "aicpp_chat"],
  "chat.upload_file": ["aicpp_upload_file", "aicpp_chat"],
  "chat.speak": ["aicpp_speak", "aicpp_chat"],
  "chat.search_messages": ["aicpp_search_messages", "aicpp_chat"],
  "conversations.list": ["aicpp_get_conversations", "aicpp_chat"],
  "conversations.load": ["aicpp_load_conversation", "aicpp_chat"],
  "conversations.delete": ["aicpp_delete_conversation", "aicpp_chat"],
  "conversations.pin": ["aicpp_pin_conversation", "aicpp_chat"],
  "conversations.assign_to_project": ["aicpp_assign_conversation_project", "aicpp_chat"],
  "personas.mine": ["aicpp_get_my_personas", "aicpp_chat"],
  // Admin persona endpoints (the entries report-w flagged as missing).
  "personas.get": ["aicpp_get_persona", "aicpp"],
  "personas.save": ["aicpp_save_persona", "aicpp"],
  "personas.delete": ["aicpp_delete_persona", "aicpp"],
  "personas.assign": ["aicpp_assign_persona", "aicpp"],
  "personas.unassign": ["aicpp_unassign_persona", "aicpp"],
  "personas.bulk_assign": ["aicpp_bulk_assign", "aicpp"],
  "personas.user_personas": ["aicpp_get_user_personas", "aicpp"],
  "personas.persona_users": ["aicpp_get_persona_users", "aicpp"],
  "personas.search_users": ["aicpp_search_users", "aicpp"],
  "projects.list": ["aicpp_get_projects", "aicpp"],
  "projects.create": ["aicpp_create_project", "aicpp"],
  "projects.update": ["aicpp_update_project", "aicpp"],
  "projects.delete": ["aicpp_delete_project", "aicpp"],
  "projects.attach_file": ["aicpp_attach_project_file", "aicpp"],
  "projects.detach_file": ["aicpp_detach_project_file", "aicpp"],
  "memories.list": ["aicpp_get_memories", "aicpp"],
  "memories.add": ["aicpp_add_memory", "aicpp"],
  "memories.update": ["aicpp_update_memory", "aicpp"],
  "memories.delete": ["aicpp_delete_memory", "aicpp"],
  "memories.toggle": ["aicpp_toggle_memory", "aicpp"],
  "artifacts.list": ["aicpp_list_artifacts", "aicpp_chat"],
  "artifacts.get": ["aicpp_get_artifact", "aicpp_chat"],
  "artifacts.save": ["aicpp_save_artifact", "aicpp_chat"],
  "artifacts.delete": ["aicpp_delete_artifact", "aicpp_chat"],
  "rewards.referrals": ["aicpp_get_referral_data", "aicpp_chat"],
  "rewards.leaderboard": ["aicpp_get_leaderboard", "aicpp_chat"],
  "account.update_profile": ["aicpp_update_profile", "aicpp_chat"],
  "account.login": ["aicpp_login_user", "aicpp_login"],
  "account.register": ["aicpp_register_user", "aicpp_register"],
  "models.free_models": ["aicpp_or_free_models", "aicpp"],
  "models.refresh_free": ["aicpp_or_refresh_free", "aicpp"],
};

function resolve(id: string): { action: string; nonce: string } {
  const h = hs();
  const [group, key] = id.split(".");
  const fromManifest = h.endpoints?.[group]?.[key];
  if (fromManifest) {
    const nonceVal = h.nonces?.[fromManifest.nonce] ?? h.nonce ?? "";
    return { action: fromManifest.action, nonce: nonceVal };
  }
  const fb = FALLBACK[id];
  if (!fb) throw new Error("Unknown endpoint: " + id);
  const [action, group2] = fb;
  const nonceVal = h.nonces?.[group2] ?? h.nonce ?? "";
  return { action, nonce: nonceVal };
}

/** Force a single full reload to mint fresh nonces (no client-side mint possible). */
async function aicppRefreshHandshake(): Promise<boolean> {
  try {
    if (typeof sessionStorage !== "undefined" && !sessionStorage.getItem("aicpp_reloaded_for_nonce")) {
      sessionStorage.setItem("aicpp_reloaded_for_nonce", "1");
      location.reload();
    }
  } catch {}
  return false;
}

/** Core call: POST FormData to admin-ajax, parse wp_send_json, retry once on stale nonce. */
export async function aicppCall(
  id: string,
  payload: Record<string, any> = {},
  files?: Record<string, File | Blob>,
  _retried = false,
): Promise<any> {
  const h = hs();
  const { action, nonce } = resolve(id);
  const fd = new FormData();
  fd.append("action", action);
  fd.append("nonce", nonce);
  for (const k of Object.keys(payload)) {
    const v = payload[k];
    if (v === undefined || v === null) continue;
    fd.append(k, typeof v === "object" ? JSON.stringify(v) : String(v));
  }
  if (files) for (const k of Object.keys(files)) fd.append(k, files[k]);

  const res = await fetch(h.ajaxurl, { method: "POST", body: fd, credentials: "same-origin" });
  const text = await res.text();

  if ((res.status === 403 || text.trim() === "-1") && !_retried) {
    if (await aicppRefreshHandshake()) return aicppCall(id, payload, files, true);
    throw new Error("Refreshing session…");
  }

  let json: any;
  try { json = JSON.parse(text); } catch { throw new Error("Bad response"); }
  if (!json || json.success !== true) {
    throw new Error(json?.data?.message || "Request failed");
  }
  try { sessionStorage.removeItem("aicpp_reloaded_for_nonce"); } catch {}
  return json.data;
}

export function can(flag: string): boolean { return !!hs().can?.[flag]; }
export function isLoggedIn(): boolean { return !!hs().user_logged_in; }
export function isAdmin(): boolean { return !!hs().is_admin; }
export function personaId(): string { return String(hs().persona_id ?? "0"); }

/** MISS B: persist session across reloads so conversations continue. */
const SESSION_KEY = "aicpp_session_id";
export function sessionId(): string {
  try {
    let s = localStorage.getItem(SESSION_KEY);
    if (!s) {
      s = hs().session_id || ("sess_" + Math.random().toString(36).slice(2) + Date.now().toString(36));
      localStorage.setItem(SESSION_KEY, s);
    }
    return s;
  } catch {
    return hs().session_id || "";
  }
}

/** Start a fresh session id (clears persistence). */
export function newSession(): string {
  const s = "sess_" + Math.random().toString(36).slice(2) + Date.now().toString(36);
  try { localStorage.setItem(SESSION_KEY, s); } catch {}
  return s;
}

/** MISS J: warn (don't throw) when the bridge version isn't in the supported set. */
export function assertBridgeContract(supported: string[] = ["v12", "v12.3", "v13"]): void {
  const h = hs();
  if (h.bridge_version && !supported.includes(String(h.bridge_version))) {
    // eslint-disable-next-line no-console
    console.warn(
      `[aicpp] Bridge version ${h.bridge_version} not in supported set ${supported.join(",")}. ` +
      `UI will fall back to the static endpoint map.`,
    );
  }
}