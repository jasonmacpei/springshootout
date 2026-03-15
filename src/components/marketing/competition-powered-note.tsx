import Link from "next/link";

export function CompetitionPoweredNote() {
  return (
    <div className="relative overflow-hidden rounded-[28px] border border-[#9cc7f0]/50 bg-[linear-gradient(135deg,#11243f_0%,#17335a_52%,#1f4f85_100%)] p-6 text-white shadow-[0_24px_70px_rgba(18,42,75,0.22)]">
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(114,194,255,0.22),transparent_28%),radial-gradient(circle_at_bottom_right,rgba(255,166,92,0.18),transparent_24%)]" />
      <div className="relative">
        <p className="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-sky-100">
          Powered by Hoops Scorebook
        </p>
        <p className="mt-4 text-lg font-semibold leading-8 text-white sm:text-xl">
          Live schedule, game results and standings will be provided by HoopsScoreBook.com.
        </p>
        <p className="mt-3 max-w-3xl text-sm leading-7 text-sky-100/88 sm:text-base">
          Follow the tournament through a modern live scoring platform built for basketball events, clubs, and leagues
          across Atlantic Canada.
        </p>
        <Link
          className="mt-5 inline-flex items-center rounded-full border border-white/18 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/16"
          href="https://www.hoopsscorebook.com"
          rel="noreferrer"
          target="_blank"
        >
          Visit HoopsScoreBook.com
        </Link>
      </div>
    </div>
  );
}
