import { render } from "@react-email/render";
import * as React from "react";

import { TournamentEmailTemplate } from "@/lib/email/templates";

function stripHtml(input: string) {
  return input
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

export async function buildCampaignEmailContent({
  subject,
  htmlBody,
  textBody,
}: {
  subject: string;
  htmlBody?: string | null;
  textBody?: string | null;
}) {
  const resolvedText = textBody?.trim() || stripHtml(htmlBody ?? "");

  if (htmlBody?.trim()) {
    return {
      html: htmlBody,
      text: resolvedText,
    };
  }

  const html = await render(React.createElement(TournamentEmailTemplate, { heading: subject, body: resolvedText || subject }));

  return {
    html,
    text: resolvedText || subject,
  };
}
