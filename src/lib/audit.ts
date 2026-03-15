import type { SupabaseClient } from "@supabase/supabase-js";

export async function logAuditEvent(
  supabase: SupabaseClient,
  {
    actorUserId,
    entityType,
    entityId,
    action,
    metadata,
  }: {
    actorUserId?: string | null;
    entityType: string;
    entityId?: string | null;
    action: string;
    metadata?: Record<string, unknown>;
  },
) {
  await supabase.from("audit_logs").insert({
    actor_user_id: actorUserId ?? null,
    entity_type: entityType,
    entity_id: entityId ?? null,
    action,
    metadata: metadata ?? {},
  });
}
