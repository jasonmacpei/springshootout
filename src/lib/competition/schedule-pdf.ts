import type { CompetitionScoreboardGame } from "@/lib/competition/schemas";

const tournamentTimeZone = "America/Halifax";

const pageWidth = 792;
const pageHeight = 612;
const marginX = 34;
const accent = [179, 92, 54] as const;
const foreground = [24, 33, 49] as const;
const muted = [93, 101, 116] as const;
const surface = [239, 232, 220] as const;
const line = [215, 205, 190] as const;

type PdfColor = readonly [number, number, number];

type SchedulePdfOptions = {
  eventName?: string;
  generatedAt?: Date;
};

type DivisionSchedule = {
  id: string;
  name: string;
  games: CompetitionScoreboardGame[];
};

type PdfPage = {
  width: number;
  height: number;
  content: string;
};

function formatScheduleDate(date: string) {
  return new Intl.DateTimeFormat("en-CA", {
    month: "short",
    day: "numeric",
    weekday: "short",
    timeZone: tournamentTimeZone,
  }).format(new Date(date));
}

function formatScheduleTime(date: string) {
  return new Intl.DateTimeFormat("en-CA", {
    hour: "numeric",
    minute: "2-digit",
    timeZone: tournamentTimeZone,
  }).format(new Date(date));
}

function formatGeneratedAt(date: Date) {
  return new Intl.DateTimeFormat("en-CA", {
    dateStyle: "medium",
    timeStyle: "short",
    timeZone: tournamentTimeZone,
  }).format(date);
}

function formatMatchup(game: CompetitionScoreboardGame) {
  if (game.gameName) {
    return game.gameName;
  }

  const home = game.homeTeamName || game.homeSlotLabel || "Home TBD";
  const away = game.awayTeamName || game.awaySlotLabel || "Away TBD";
  return `${home} vs ${away}`;
}

function formatVenue(game: CompetitionScoreboardGame) {
  return [game.venue, game.court].filter(Boolean).join(" - ") || "Venue pending";
}

function formatStage(game: CompetitionScoreboardGame) {
  return game.poolName ?? game.stageName ?? "TBD";
}

function sanitizePdfText(value: string) {
  return value
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[–—]/g, "-")
    .replace(/[“”]/g, '"')
    .replace(/[‘’]/g, "'")
    .replace(/[^\x20-\x7e]/g, "")
    .replace(/\\/g, "\\\\")
    .replace(/\(/g, "\\(")
    .replace(/\)/g, "\\)");
}

function colorOperator(color: PdfColor, operator: "rg" | "RG") {
  return `${(color[0] / 255).toFixed(3)} ${(color[1] / 255).toFixed(3)} ${(color[2] / 255).toFixed(3)} ${operator}`;
}

function rect(x: number, y: number, width: number, height: number, color: PdfColor) {
  const pdfY = pageHeight - y - height;
  return `q ${colorOperator(color, "rg")} ${x.toFixed(2)} ${pdfY.toFixed(2)} ${width.toFixed(2)} ${height.toFixed(2)} re f Q\n`;
}

function strokeLine(x1: number, y1: number, x2: number, y2: number, color: PdfColor, width = 0.6) {
  return `q ${colorOperator(color, "RG")} ${width.toFixed(2)} w ${x1.toFixed(2)} ${(pageHeight - y1).toFixed(2)} m ${x2.toFixed(2)} ${(pageHeight - y2).toFixed(2)} l S Q\n`;
}

function text({
  value,
  x,
  y,
  size,
  color = foreground,
  bold = false,
}: {
  value: string;
  x: number;
  y: number;
  size: number;
  color?: PdfColor;
  bold?: boolean;
}) {
  return `BT ${colorOperator(color, "rg")} /${bold ? "F2" : "F1"} ${size.toFixed(2)} Tf 1 0 0 1 ${x.toFixed(2)} ${(pageHeight - y).toFixed(2)} Tm (${sanitizePdfText(value)}) Tj ET\n`;
}

function wrapText(value: string, width: number, fontSize: number, maxLines: number) {
  const maxChars = Math.max(6, Math.floor(width / (fontSize * 0.52)));
  const words = value.split(/\s+/).filter(Boolean);
  const lines: string[] = [];
  let current = "";

  for (const word of words) {
    const candidate = current ? `${current} ${word}` : word;
    if (candidate.length <= maxChars) {
      current = candidate;
      continue;
    }

    if (current) {
      lines.push(current);
    }

    current = word;

    if (lines.length === maxLines) {
      break;
    }
  }

  if (current && lines.length < maxLines) {
    lines.push(current);
  }

  if (lines.length === maxLines && words.join(" ").length > lines.join(" ").length) {
    lines[maxLines - 1] = `${lines[maxLines - 1].slice(0, Math.max(0, maxChars - 1))}...`;
  }

  return lines;
}

function groupScheduleByDivision(games: CompetitionScoreboardGame[]) {
  const divisions = games.reduce((map, game) => {
    const id = game.divisionId ? String(game.divisionId) : `division:${game.divisionName ?? "TBD"}`;
    const name = game.divisionName ?? "Division TBD";
    const existing = map.get(id) ?? { id, name, games: [] };
    existing.games.push(game);
    map.set(id, existing);
    return map;
  }, new Map<string, DivisionSchedule>());

  return Array.from(divisions.values())
    .map((division) => ({
      ...division,
      games: division.games.sort((a, b) => new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime()),
    }))
    .sort((a, b) => a.name.localeCompare(b.name));
}

function emptySchedulePage(eventName: string, generatedAt: Date) {
  let content = rect(0, 0, pageWidth, 10, accent);
  content += text({ value: eventName, x: marginX, y: 52, size: 22, bold: true });
  content += text({ value: "Schedule", x: marginX, y: 78, size: 15, color: muted, bold: true });
  content += text({ value: `Generated ${formatGeneratedAt(generatedAt)}`, x: pageWidth - 230, y: 52, size: 9, color: muted });
  content += rect(marginX, 124, pageWidth - marginX * 2, 120, surface);
  content += text({ value: "No games are currently available for this schedule.", x: marginX + 22, y: 176, size: 14, bold: true });
  content += text({ value: "Please check springshootout.ca/schedule for the latest tournament updates.", x: marginX + 22, y: 202, size: 10, color: muted });
  return { width: pageWidth, height: pageHeight, content };
}

function divisionPage(division: DivisionSchedule, eventName: string, generatedAt: Date, pageNumber: number, pageCount: number) {
  const tableTop = 116;
  const footerY = 580;
  const usableWidth = pageWidth - marginX * 2;
  const columnGap = 18;
  const columnCount = division.games.length > 22 ? 2 : 1;
  const columnWidth = (usableWidth - columnGap * (columnCount - 1)) / columnCount;
  const rowsPerColumn = Math.max(1, Math.ceil(division.games.length / columnCount));
  const tableHeaderHeight = 24;
  const tableHeight = footerY - tableTop - tableHeaderHeight - 8;
  const rowHeight = Math.max(9.5, Math.min(22, tableHeight / rowsPerColumn));
  const bodyFontSize = rowHeight < 12 ? 6.2 : rowHeight < 15 ? 7.2 : 8.2;
  const secondaryFontSize = Math.max(5.8, bodyFontSize - 1.1);
  const matchupWidth = columnCount === 2 ? columnWidth - 160 : columnWidth - 270;

  let content = rect(0, 0, pageWidth, 10, accent);
  content += text({ value: eventName, x: marginX, y: 42, size: 22, bold: true });
  content += text({ value: division.name, x: marginX, y: 70, size: 16, bold: true });
  content += text({
    value: `${division.games.length} ${division.games.length === 1 ? "game" : "games"} - generated ${formatGeneratedAt(generatedAt)}`,
    x: marginX,
    y: 91,
    size: 9,
    color: muted,
  });
  content += text({ value: "Schedule PDF", x: pageWidth - 154, y: 42, size: 13, color: accent, bold: true });
  content += text({ value: "springshootout.ca/schedule", x: pageWidth - 154, y: 61, size: 8.4, color: muted });

  for (let columnIndex = 0; columnIndex < columnCount; columnIndex += 1) {
    const x = marginX + columnIndex * (columnWidth + columnGap);
    content += rect(x, tableTop, columnWidth, tableHeaderHeight, surface);
    content += text({ value: "When", x: x + 8, y: tableTop + 16, size: 7.4, color: muted, bold: true });
    content += text({ value: "Matchup", x: x + 76, y: tableTop + 16, size: 7.4, color: muted, bold: true });
    content += text({
      value: "Location",
      x: x + 76 + matchupWidth + 12,
      y: tableTop + 16,
      size: 7.4,
      color: muted,
      bold: true,
    });
    content += text({
      value: "Stage",
      x: x + columnWidth - 58,
      y: tableTop + 16,
      size: 7.4,
      color: muted,
      bold: true,
    });
  }

  division.games.forEach((game, index) => {
    const columnIndex = Math.floor(index / rowsPerColumn);
    const rowIndex = index % rowsPerColumn;
    const x = marginX + columnIndex * (columnWidth + columnGap);
    const y = tableTop + tableHeaderHeight + rowIndex * rowHeight;

    if (rowIndex % 2 === 0) {
      content += rect(x, y, columnWidth, rowHeight, [250, 247, 241]);
    }

    content += strokeLine(x, y + rowHeight, x + columnWidth, y + rowHeight, line, 0.35);
    content += text({ value: formatScheduleDate(game.scheduledAt), x: x + 8, y: y + bodyFontSize + 3, size: bodyFontSize, bold: true });
    content += text({ value: formatScheduleTime(game.scheduledAt), x: x + 8, y: y + bodyFontSize * 2 + 5, size: secondaryFontSize, color: muted });

    wrapText(formatMatchup(game), matchupWidth, bodyFontSize, rowHeight < 13 ? 1 : 2).forEach((lineValue, lineIndex) => {
      content += text({
        value: lineValue,
        x: x + 76,
        y: y + bodyFontSize + 3 + lineIndex * (bodyFontSize + 1.4),
        size: bodyFontSize,
        bold: lineIndex === 0,
      });
    });

    wrapText(formatVenue(game), columnCount === 2 ? 86 : 142, secondaryFontSize, 2).forEach((lineValue, lineIndex) => {
      content += text({
        value: lineValue,
        x: x + 76 + matchupWidth + 12,
        y: y + bodyFontSize + 3 + lineIndex * (secondaryFontSize + 1.5),
        size: secondaryFontSize,
        color: muted,
      });
    });

    wrapText(formatStage(game), 54, secondaryFontSize, 2).forEach((lineValue, lineIndex) => {
      content += text({
        value: lineValue,
        x: x + columnWidth - 58,
        y: y + bodyFontSize + 3 + lineIndex * (secondaryFontSize + 1.5),
        size: secondaryFontSize,
        color: muted,
      });
    });
  });

  content += text({ value: `Page ${pageNumber} of ${pageCount}`, x: marginX, y: footerY + 18, size: 8, color: muted });
  content += text({
    value: "Times shown in Atlantic time. Confirm latest changes online before game day.",
    x: pageWidth - 326,
    y: footerY + 18,
    size: 8,
    color: muted,
  });

  return { width: pageWidth, height: pageHeight, content };
}

function buildPdfDocument(pages: PdfPage[]) {
  const objects: string[] = [
    "<< /Type /Catalog /Pages 2 0 R >>",
    "",
    "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
    "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>",
  ];

  const firstPageObjectId = 5;
  const pageIds = pages.map((_, index) => firstPageObjectId + index * 2 + 1);
  objects[1] = `<< /Type /Pages /Kids [${pageIds.map((id) => `${id} 0 R`).join(" ")}] /Count ${pages.length} >>`;

  pages.forEach((page, index) => {
    const contentId = firstPageObjectId + index * 2;
    const pageId = contentId + 1;
    objects[contentId - 1] = `<< /Length ${Buffer.byteLength(page.content, "utf8")} >>\nstream\n${page.content}endstream`;
    objects[pageId - 1] =
      `<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${page.width} ${page.height}] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ${contentId} 0 R >>`;
  });

  let pdf = "%PDF-1.4\n";
  const offsets = [0];

  objects.forEach((object, index) => {
    offsets.push(Buffer.byteLength(pdf, "utf8"));
    pdf += `${index + 1} 0 obj\n${object}\nendobj\n`;
  });

  const xrefOffset = Buffer.byteLength(pdf, "utf8");
  pdf += `xref\n0 ${objects.length + 1}\n0000000000 65535 f \n`;
  offsets.slice(1).forEach((offset) => {
    pdf += `${String(offset).padStart(10, "0")} 00000 n \n`;
  });
  pdf += `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xrefOffset}\n%%EOF\n`;

  return Buffer.from(pdf, "utf8");
}

export function buildSchedulePdf(games: CompetitionScoreboardGame[], options: SchedulePdfOptions = {}) {
  const sortedGames = [...games].sort((a, b) => new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime());
  const divisions = groupScheduleByDivision(sortedGames);
  const eventName = options.eventName ?? sortedGames[0]?.eventName ?? "Spring Shootout";
  const generatedAt = options.generatedAt ?? new Date();
  const pages =
    divisions.length > 0
      ? divisions.map((division, index) => divisionPage(division, eventName, generatedAt, index + 1, divisions.length))
      : [emptySchedulePage(eventName, generatedAt)];

  return buildPdfDocument(pages);
}
