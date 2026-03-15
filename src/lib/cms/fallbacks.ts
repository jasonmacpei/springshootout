export type SimpleCmsSection = {
  heading: string;
  body: string;
};

export type SimpleCmsPage = {
  slug: string;
  title: string;
  subtitle: string;
  status?: "draft" | "published";
  sections: SimpleCmsSection[];
};

export type FallbackVenue = {
  name: string;
  address: string;
  mapUrl: string;
  notes: string;
};

export const fallbackContactRoles = [
  { slug: "primary-contact", name: "Primary Contact" },
  { slug: "head-coach", name: "Head Coach" },
  { slug: "assistant-coach", name: "Assistant Coach" },
  { slug: "manager", name: "Manager" },
];

export const fallbackEvent = {
  id: "spring-shootout-2026",
  name: "Spring Shootout 2026",
  slug: "spring-shootout-2026",
  starts_on: "2026-05-08",
  ends_on: "2026-05-10",
  provider_event_slug: "spring-shootout-2026",
  provider_event_public_id: null,
};

export const fallbackEventSettings = {
  hero_eyebrow: "3rd annual girls tournament",
  hero_title: "Spring Shootout returns to Charlottetown on May 8-10, 2026.",
  hero_description:
    "Girls divisions only, U12 and U13. Three-game guarantee, Basketball PEI sanctioned referees, medals for winners, all-star shirts, and negotiated team hotel rates.",
  support_email: "jasonmacpei@hotmail.com",
  support_phone: "902-626-1936",
  registration_status: "Open now. Entry fee is $495 per team.",
};

export const fallbackVenues: FallbackVenue[] = [
  {
    name: "UPEI Field House",
    address: "550 University Ave, Charlottetown, PE C1A 4P3",
    mapUrl: "https://maps.app.goo.gl/S3rqe51KgiNECmTQ9",
    notes: "Championship-site venue. Use the Chi-Wan Young Sports Centre entrance.",
  },
  {
    name: "Colonel Grey High School",
    address: "175 Spring Park Rd, Charlottetown, PE C1A 3Y8",
    mapUrl: "https://maps.app.goo.gl/oqBFBLt4oDEoD7DQ9",
    notes: "Central Charlottetown court with easy family parking.",
  },
  {
    name: "Glen Stewart Primary",
    address: "34 Glen Stewart Dr, Stratford, PE C1B 0J9",
    mapUrl: "https://maps.app.goo.gl/CKEwj1judPZCqvzM7",
    notes: "Stratford-side venue used throughout pool play weekend.",
  },
  {
    name: "Stonepark Intermediate",
    address: "50 Pope Ave, Charlottetown, PE C1A 7P5",
    mapUrl: "https://maps.app.goo.gl/CRHCKp9vhRsvzofz8",
    notes: "One of the busiest event gyms; allow extra time for warm-up traffic.",
  },
  {
    name: "Birchwood Intermediate",
    address: "49 Longworth Ave, Charlottetown, PE C1A 5A6",
    mapUrl: "https://maps.app.goo.gl/YiRAHQYaB5Jq4Bx77",
    notes: "Additional city venue used to keep game turnarounds tight.",
  },
  {
    name: "Charlottetown Rural High School",
    address: "100 Raiders Rd, Charlottetown, PE C1E 1K6",
    mapUrl: "https://maps.app.goo.gl/FBU6gUKxebatbYRL8",
    notes: "Large gym footprint with straightforward highway access.",
  },
  {
    name: "Donagh Regional School",
    address: "928 Bethel Rd, Donagh, PE C1B 3J7",
    mapUrl: "https://maps.app.goo.gl/XXC5K9TSr8Tip9L97",
    notes: "Used when division volume requires overflow courts.",
  },
  {
    name: "Stratford Town Hall",
    address: "234 Shakespeare Dr, Stratford, PE C1B 2V8",
    mapUrl: "https://maps.app.goo.gl/tHqy8ZR4uV4Hwsc68",
    notes: "Short drive from the hotels and a reliable opening-night site.",
  },
];

export const fallbackCmsPages: Record<string, SimpleCmsPage> = {
  rules: {
    slug: "rules",
    title: "Tournament Rules",
    subtitle: "Game administration, tie breakers, and division-specific notes for Spring Shootout weekend.",
    status: "published",
    sections: [
      {
        heading: "Tournament format",
        body: "Games are capped at a 20-point spread for standings purposes. A 60-30 result is recorded as 50-30. Division finish is driven first by wins, then by the published tie-break sequence.",
      },
      {
        heading: "Standings tie breakers",
        body: "Tie breakers move in this order: head-to-head record, plus-minus, points against, then a final draw if teams are still level.",
      },
      {
        heading: "Core game rules",
        body: "Games follow FIBA rules. All games are 5-on-5 on a 10-foot hoop, played in four 8-minute quarters with a 2-minute halftime. Teams receive two timeouts per half and one additional timeout in overtime.",
      },
      {
        heading: "Defense and sportsmanship",
        body: "Man-to-man only. No zone defense and no off-ball double teams. Help-side defense is allowed. Full-court man-to-man is allowed until the margin reaches 20 points, then teams must defend in the half court only.",
      },
      {
        heading: "Division notes",
        body: "U12 uses a 27.5-inch ball and does not allow ball screens. U13 uses a 28.5-inch ball. Event staff may issue a final coaches bulletin before opening tip if facility or officiating conditions require a clarification.",
      },
    ],
  },
  gyms: {
    slug: "gyms",
    title: "Gyms & Venues",
    subtitle: "Court locations for players, coaches, and travelling families across Charlottetown and Stratford.",
    status: "published",
    sections: [
      {
        heading: "Weekend logistics",
        body: "Most venues are within a short drive of the tournament hotels. Check the game card carefully before leaving because divisions will move between city, Stratford, and UPEI sites through the weekend.",
      },
      {
        heading: "Arrival notes",
        body: "Plan to arrive at least 30 minutes before your first game. PEI spring weather can slow bridge and city traffic, especially on Saturday morning.",
      },
    ],
  },
  hotels: {
    slug: "hotels",
    title: "Hotels",
    subtitle: "Tournament-rate lodging guidance for teams travelling into Charlottetown.",
    status: "published",
    sections: [
      {
        heading: "Hampton Inn & Suites",
        body: "300 Capital Drive, Charlottetown, PEI C1E 2N1. Call (902) 368-3551 and reference the Spring Shootout team block. Tournament rate: $189 plus tax per night.",
      },
      {
        heading: "Holiday Inn Express & Suites",
        body: "200 Capital Drive, Charlottetown, PEI C1E 2E8. Call (902) 892-1201 and reference the Spring Shootout team block. Tournament rate: $189 plus tax per night.",
      },
      {
        heading: "Booking process",
        body: "Coaches or managers should reserve the team block first, then families can book from that block individually. Wait for the final booking note from the tournament if inventory or deadlines change.",
      },
    ],
  },
  contact: {
    slug: "contact",
    title: "Contact",
    subtitle: "Reach the people running Spring Shootout and the local basketball partners supporting the weekend.",
    status: "published",
    sections: [
      {
        heading: "Tournament organizer",
        body: "Jason MacDonald is the primary tournament contact for registrations, venue logistics, and weekend issues. Phone: 902-626-1936. Email: jasonmacpei@hotmail.com.",
      },
      {
        heading: "Basketball PEI",
        body: "Basketball PEI supports officiating and the broader event partnership. Contact Josh Whitty at josh@basketballpei.ca for federation-related questions.",
      },
    ],
  },
};
