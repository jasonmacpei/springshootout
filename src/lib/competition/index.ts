import { appConfig } from "@/lib/config";
import { hoopsscorebookProvider } from "@/lib/competition/adapters/hoopsscorebook";
import { mockCompetitionProvider } from "@/lib/competition/adapters/mock";

export function getCompetitionProvider() {
  if (!appConfig.hoopsApiBase || process.env.NODE_ENV === "test") {
    return mockCompetitionProvider;
  }

  if (process.env.USE_MOCK_COMPETITION === "1") {
    return mockCompetitionProvider;
  }

  return hoopsscorebookProvider;
}
