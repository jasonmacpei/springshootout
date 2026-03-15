import { mkdirSync, writeFileSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { spawnSync } from "node:child_process";

const dumpPath = process.argv[2]
  ? resolve(process.cwd(), process.argv[2])
  : resolve(process.cwd(), "supabase/spring-shootout-legacy.dump");
const outputPath = process.argv[3]
  ? resolve(process.cwd(), process.argv[3])
  : resolve(process.cwd(), "scripts/import-legacy/output/legacy-import.sql");

const pgRestore = spawnSync("pg_restore", ["-a", "-f", "-", dumpPath], {
  cwd: process.cwd(),
  encoding: "utf8",
});

if (pgRestore.status !== 0) {
  process.stderr.write(pgRestore.stderr || "Failed to read legacy dump.\n");
  process.exit(pgRestore.status ?? 1);
}

const tables = parseCopySections(pgRestore.stdout);
const sql = buildImportSql(tables);

mkdirSync(dirname(outputPath), { recursive: true });
writeFileSync(outputPath, sql);

const counts = Object.entries(tables)
  .map(([table, payload]) => `${table}: ${payload.rows.length}`)
  .sort()
  .join("\n");

process.stdout.write(`Wrote ${outputPath}\n${counts}\n`);

function parseCopySections(input) {
  const parsed = {};
  const lines = input.split(/\r?\n/);
  let current = null;

  for (const line of lines) {
    const copyMatch = line.match(/^COPY public\.([a-z_]+) \((.+)\) FROM stdin;$/);

    if (copyMatch) {
      current = {
        table: copyMatch[1],
        columns: copyMatch[2].split(", ").map((value) => value.trim()),
        rows: [],
      };
      parsed[current.table] = current;
      continue;
    }

    if (!current) {
      continue;
    }

    if (line === "\\.") {
      current = null;
      continue;
    }

    if (line.length === 0) {
      continue;
    }

    current.rows.push(line.split("\t").map(deserializeCopyValue));
  }

  return parsed;
}

function deserializeCopyValue(value) {
  if (value === "\\N") {
    return null;
  }

  return value
    .replace(/\\\\/g, "\\")
    .replace(/\\t/g, "\t")
    .replace(/\\r/g, "\r")
    .replace(/\\n/g, "\n");
}

function sqlValue(value) {
  if (value === null || value === undefined) {
    return "null";
  }

  if (typeof value === "boolean") {
    return value ? "true" : "false";
  }

  if (typeof value === "number") {
    return Number.isFinite(value) ? String(value) : "null";
  }

  return `'${String(value).replace(/'/g, "''")}'`;
}

function rowObjects(payload) {
  return payload.rows.map((row) =>
    Object.fromEntries(payload.columns.map((column, index) => [column, row[index] ?? null])),
  );
}

function slugifyRoleName(name) {
  return name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
}

function normalizeLegacyStatus(status) {
  const numeric = Number(status);
  if (numeric === 1) {
    return "approved";
  }
  if (numeric === 2) {
    return "withdrawn";
  }
  return "pending";
}

function buildMultiInsert(schemaTable, columns, rows) {
  if (!rows.length) {
    return "";
  }

  const valuesSql = rows
    .map((row) => `  (${row.map((value) => sqlValue(value)).join(", ")})`)
    .join(",\n");

  return `insert into ${schemaTable} (${columns.join(", ")})\nvalues\n${valuesSql};\n`;
}

function buildImportSql(rawTables) {
  const teamRows = rowObjects(rawTables.teams ?? { columns: [], rows: [] });
  const contactRows = rowObjects(rawTables.contacts ?? { columns: [], rows: [] });
  const registrationRows = rowObjects(rawTables.registrations ?? { columns: [], rows: [] });
  const teamContactRows = rowObjects(rawTables.team_contacts ?? { columns: [], rows: [] });
  const contactRoleRows = rowObjects(rawTables.contact_roles ?? { columns: [], rows: [] });
  const welcomeEmailRows = rowObjects(rawTables.welcome_emails ?? { columns: [], rows: [] });
  const scheduleRows = rowObjects(rawTables.schedule ?? { columns: [], rows: [] });

  const eventYears = [...new Set(registrationRows.map((row) => row.year).filter(Boolean))].sort();
  const eventDateRanges = new Map();

  for (const row of scheduleRows) {
    if (!row.game_date) {
      continue;
    }

    const year = String(row.game_date).slice(0, 4);
    const existing = eventDateRanges.get(year);

    if (!existing) {
      eventDateRanges.set(year, { startsOn: row.game_date, endsOn: row.game_date });
      continue;
    }

    if (row.game_date < existing.startsOn) {
      existing.startsOn = row.game_date;
    }

    if (row.game_date > existing.endsOn) {
      existing.endsOn = row.game_date;
    }
  }

  const eventsSql = eventYears
    .map((year) => {
      const numericYear = Number(year);
      const eventDates = eventDateRanges.get(String(year));
      return `insert into public.events (\n  slug,\n  name,\n  starts_on,\n  ends_on,\n  is_active\n)\nvalues (\n  'spring-shootout-${numericYear}',\n  'Spring Shootout ${numericYear}',\n  ${sqlValue(eventDates?.startsOn ?? null)},\n  ${sqlValue(eventDates?.endsOn ?? null)},\n  false\n)\non conflict (slug) do nothing;`;
    })
    .join("\n\n");

  const roleRowsForPublic = contactRoleRows.map((row) => {
    const roleName = row.role_name;
    let slug = slugifyRoleName(roleName);
    if (slug === "head-coach") slug = "head-coach";
    if (slug === "assistant-coach") slug = "assistant-coach";
    return [row.role_id, slug, roleName];
  });

  const legacyInsertBlocks = [
    "delete from legacy.game_results;",
    "delete from legacy.schedule;",
    "delete from legacy.team_pools;",
    "delete from legacy.team_contacts;",
    "delete from legacy.registrations;",
    "delete from legacy.sent_emails;",
    "delete from legacy.welcome_emails;",
    "delete from legacy.pools;",
    "delete from legacy.contact_roles;",
    "delete from legacy.contacts;",
    "delete from legacy.teams;",
    "",
    buildMultiInsert(
      "legacy.teams",
      rawTables.teams.columns,
      rawTables.teams.rows,
    ),
    buildMultiInsert(
      "legacy.contacts",
      rawTables.contacts.columns,
      rawTables.contacts.rows,
    ),
    buildMultiInsert(
      "legacy.contact_roles",
      rawTables.contact_roles.columns,
      rawTables.contact_roles.rows,
    ),
    buildMultiInsert(
      "legacy.pools",
      rawTables.pools.columns,
      rawTables.pools.rows,
    ),
    buildMultiInsert(
      "legacy.registrations",
      rawTables.registrations.columns,
      rawTables.registrations.rows,
    ),
    buildMultiInsert(
      "legacy.team_contacts",
      rawTables.team_contacts.columns,
      rawTables.team_contacts.rows,
    ),
    buildMultiInsert(
      "legacy.team_pools",
      rawTables.team_pools.columns,
      rawTables.team_pools.rows,
    ),
    buildMultiInsert(
      "legacy.schedule",
      rawTables.schedule.columns,
      rawTables.schedule.rows,
    ),
    buildMultiInsert(
      "legacy.game_results",
      rawTables.game_results.columns,
      rawTables.game_results.rows,
    ),
    buildMultiInsert(
      "legacy.sent_emails",
      rawTables.sent_emails.columns,
      rawTables.sent_emails.rows,
    ),
    buildMultiInsert(
      "legacy.welcome_emails",
      rawTables.welcome_emails.columns,
      rawTables.welcome_emails.rows,
    ),
  ]
    .filter(Boolean)
    .join("\n");

  const publicRoleSql = roleRowsForPublic.length
    ? `insert into public.contact_roles (legacy_role_id, slug, name)\nvalues\n${roleRowsForPublic
        .map(([legacyRoleId, slug, name]) => `  (${legacyRoleId}, ${sqlValue(slug)}, ${sqlValue(name)})`)
        .join(",\n")}\non conflict (slug) do update\nset\n  name = excluded.name,\n  legacy_role_id = excluded.legacy_role_id;`
    : "";

  const publicContactsSql = contactRows.length
    ? `insert into public.contacts (legacy_contact_id, full_name, email, phone)\nvalues\n${contactRows
        .map(
          (row) =>
            `  (${sqlValue(Number(row.contact_id))}, ${sqlValue(row.contact_name)}, ${sqlValue(
              row.email_address,
            )}, ${sqlValue(row.phone_number)})`,
        )
        .join(",\n")}\non conflict (legacy_contact_id) do update\nset\n  full_name = excluded.full_name,\n  email = excluded.email,\n  phone = excluded.phone;`
    : "";

  const registrationsByTeam = new Map();
  for (const row of registrationRows) {
    if (!registrationsByTeam.has(row.team_id)) {
      registrationsByTeam.set(row.team_id, row);
    }
  }

  const publicTeamsSql = teamRows.length
    ? `insert into public.teams (event_id, legacy_team_id, name, division_name, class_name, province)\nselect\n  e.id,\n  data.legacy_team_id,\n  data.name,\n  data.division_name,\n  data.class_name,\n  data.province\nfrom (\n${teamRows
        .map((team) => {
          const registration = registrationsByTeam.get(team.team_id) ?? {};
          const year = registration.year ?? "2025";
          return `  select ${sqlValue(Number(team.team_id))}::integer as legacy_team_id, ${sqlValue(
            team.team_name,
          )}::text as name, ${sqlValue(registration.division ?? null)}::text as division_name, ${sqlValue(
            registration.class ?? null,
          )}::text as class_name, ${sqlValue(registration.province ?? null)}::text as province, ${sqlValue(
            `spring-shootout-${year}`,
          )}::text as event_slug`;
        })
        .join("\nunion all\n")}\n) as data\njoin public.events e on e.slug = data.event_slug\non conflict (event_id, legacy_team_id) do update\nset\n  name = excluded.name,\n  division_name = excluded.division_name,\n  class_name = excluded.class_name,\n  province = excluded.province;`
    : "";

  const publicRegistrationsSql = registrationRows.length
    ? `insert into public.registrations (\n  legacy_registration_id,\n  event_id,\n  team_id,\n  primary_contact_id,\n  division_name,\n  class_name,\n  province,\n  note,\n  status\n)\nselect\n  data.legacy_registration_id,\n  e.id,\n  t.id,\n  c.id,\n  data.division_name,\n  data.class_name,\n  data.province,\n  data.note,\n  data.status::public.registration_status\nfrom (\n${registrationRows
        .map(
          (row) =>
            `  select ${sqlValue(Number(row.registration_id))}::integer as legacy_registration_id, ${sqlValue(
              Number(row.team_id),
            )}::integer as legacy_team_id, ${sqlValue(Number(row.contact_id))}::integer as legacy_contact_id, ${sqlValue(
              row.division,
            )}::text as division_name, ${sqlValue(row.class)}::text as class_name, ${sqlValue(
              row.province,
            )}::text as province, ${sqlValue(row.note)}::text as note, ${sqlValue(
              normalizeLegacyStatus(row.status),
            )}::text as status, ${sqlValue(`spring-shootout-${row.year}`)}::text as event_slug`,
        )
        .join("\nunion all\n")}\n) as data\njoin public.events e on e.slug = data.event_slug\njoin public.teams t on t.event_id = e.id and t.legacy_team_id = data.legacy_team_id\nleft join public.contacts c on c.legacy_contact_id = data.legacy_contact_id\non conflict (legacy_registration_id) do update\nset\n  event_id = excluded.event_id,\n  team_id = excluded.team_id,\n  primary_contact_id = excluded.primary_contact_id,\n  division_name = excluded.division_name,\n  class_name = excluded.class_name,\n  province = excluded.province,\n  note = excluded.note,\n  status = excluded.status;`
    : "";

  const publicTeamContactsSql = teamContactRows.length
    ? `insert into public.team_contacts (\n  legacy_team_contact_id,\n  team_id,\n  contact_id,\n  role_id,\n  created_at\n)\nselect\n  data.legacy_team_contact_id,\n  t.id,\n  c.id,\n  r.id,\n  data.created_at::timestamptz\nfrom (\n${teamContactRows
        .map(
          (row) =>
            `  select ${sqlValue(Number(row.team_contact_id))}::integer as legacy_team_contact_id, ${sqlValue(
              Number(row.team_id),
            )}::integer as legacy_team_id, ${sqlValue(Number(row.contact_id))}::integer as legacy_contact_id, ${sqlValue(
              Number(row.role_id),
            )}::integer as legacy_role_id, ${sqlValue(row.created_at)}::text as created_at`,
        )
        .join("\nunion all\n")}\n) as data\njoin public.teams t on t.legacy_team_id = data.legacy_team_id\njoin public.contacts c on c.legacy_contact_id = data.legacy_contact_id\nleft join public.contact_roles r on r.legacy_role_id = data.legacy_role_id\non conflict (legacy_team_contact_id) do update\nset\n  team_id = excluded.team_id,\n  contact_id = excluded.contact_id,\n  role_id = excluded.role_id,\n  created_at = excluded.created_at;`
    : "";

  const publicWelcomeEmailSql = welcomeEmailRows.length
    ? `insert into public.email_templates (\n  legacy_welcome_email_id,\n  event_id,\n  slug,\n  subject,\n  text_body,\n  is_active\n)\nselect\n  data.legacy_welcome_email_id,\n  e.id,\n  data.slug,\n  data.subject,\n  data.text_body,\n  true\nfrom (\n${welcomeEmailRows
        .map(
          (row) =>
            `  select ${sqlValue(Number(row.id))}::integer as legacy_welcome_email_id, ${sqlValue(
              "legacy-welcome-email-2025",
            )}::text as slug, ${sqlValue(row.subject)}::text as subject, ${sqlValue(
              row.body,
            )}::text as text_body, ${sqlValue("spring-shootout-2025")}::text as event_slug`,
        )
        .join("\nunion all\n")}\n) as data\njoin public.events e on e.slug = data.event_slug\non conflict (legacy_welcome_email_id) do update\nset\n  event_id = excluded.event_id,\n  slug = excluded.slug,\n  subject = excluded.subject,\n  text_body = excluded.text_body,\n  is_active = excluded.is_active;`
    : "";

  return [
    "-- Generated by scripts/import-legacy/generate-import-sql.mjs",
    "begin;",
    "",
    eventsSql,
    "",
    legacyInsertBlocks,
    "",
    publicRoleSql,
    "",
    publicContactsSql,
    "",
    publicTeamsSql,
    "",
    publicRegistrationsSql,
    "",
    publicTeamContactsSql,
    "",
    publicWelcomeEmailSql,
    "",
    "commit;",
    "",
  ].join("\n");
}
