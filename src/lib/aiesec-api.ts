// AIESEC Global Information System (GIS) API Client
const AIESEC_GRAPHQL_ENDPOINT = "https://gis-api.aiesec.org/graphql";
const DEFAULT_TOKEN = "e316ebe109dd84ed16734e5161a2d236d0a7e6daf499941f7c110078e3c75493";

export type DurationBracket = "short" | "medium" | "long";

export interface OpportunitySlot {
  id?: string;
  startDate?: string;
  endDate?: string;
  status?: string;
}

export interface RealOpportunity {
  id: number;
  gisId: string;
  title: string;
  description: string;
  skills: string[];
  backgrounds: string[];
  languages: string[];
  location: string;
  country: string;
  city: string;
  organisation: string;
  programme: {
    id: string;
    short_name: string;
  };
  programmeName: string; // "Global Talent" | "Global Volunteer" | "Global Teacher"
  programmeSlug: string; // "global-talent" | "global-volunteer" | "global-teacher"
  sourceUrl: string;
  coverPhoto?: string | null;
  status: string;
  duration: number; // Duration in weeks (e.g. 6, 8, 12, 26)
  durationBracket: DurationBracket; // "short" (6-12w) | "medium" (13-26w) | "long" (27-78w)
  earliestStartDate?: string | null;
  latestEndDate?: string | null;
  slots: OpportunitySlot[];
}

// In-memory cache for fast lookups and resilience
let cachedOpportunities: RealOpportunity[] = [];
let lastFetchTime = 0;
const CACHE_TTL_MS = 10 * 60 * 1000; // 10 minutes cache

export function getDurationBracket(weeks?: number | null): DurationBracket {
  if (!weeks || weeks <= 12) return "short"; // 6 - 12 weeks (AIESEC short term)
  if (weeks <= 26) return "medium"; // 13 - 26 weeks (AIESEC medium term)
  return "long"; // 27 - 78 weeks (AIESEC long term)
}

function getProgrammeInfo(shortName?: string): { name: string; slug: string } {
  const code = (shortName || "").toUpperCase();
  if (code === "GV") {
    return { name: "Global Volunteer", slug: "global-volunteer" };
  }
  if (code === "GTE" || code === "TE") {
    return { name: "Global Teacher", slug: "global-teacher" };
  }
  return { name: "Global Talent", slug: "global-talent" };
}

function cleanText(text?: string | null): string {
  if (!text) return "";
  return text
    .replace(/<[^>]*>?/gm, " ") // remove HTML tags if any
    .replace(/\s+/g, " ")
    .trim();
}

async function executeGisQuery(query: string, token: string): Promise<any> {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 12000);

  try {
    const res = await fetch(AIESEC_GRAPHQL_ENDPOINT, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        Authorization: token,
      },
      body: JSON.stringify({ query }),
      signal: controller.signal,
    });

    if (!res.ok) {
      const errText = await res.text().catch(() => "");
      console.warn(`GIS GraphQL returned HTTP ${res.status}: ${errText.slice(0, 150)}`);
      return null;
    }

    const data = await res.json();
    return data;
  } catch (err: any) {
    console.warn(`GIS GraphQL request error: ${err?.message || err}`);
    return null;
  } finally {
    clearTimeout(timeout);
  }
}

function transformRawOpportunity(raw: any): RealOpportunity | null {
  if (!raw || !raw.id || !raw.title) return null;

  const numericId = parseInt(raw.id, 10);
  if (isNaN(numericId)) return null;

  const progInfo = getProgrammeInfo(raw.programme?.short_name);

  // Country & City normalization
  let country = raw.host_lc?.country?.trim() || "";
  if (!country && raw.location) {
    const parts = raw.location.split(",");
    country = parts[parts.length - 1].trim();
  }
  if (!country) country = "International";
  country = country.replace(/\s+/g, " ").trim();

  let city = raw.host_lc?.name?.trim() || "";
  if (!city && raw.location) {
    const parts = raw.location.split(",");
    city = parts[0].trim();
  }

  const skills = (raw.skills || [])
    .map((s: any) => s?.constant_name || s?.name)
    .filter(Boolean);

  const backgrounds = (raw.backgrounds || [])
    .map((b: any) => b?.constant_name || b?.name)
    .filter(Boolean);

  const languages = (raw.languages || [])
    .map((l: any) => l?.constant_name || l?.name)
    .filter(Boolean);

  const sourceUrl = `https://aiesec.org/opportunity/${progInfo.slug}/${raw.id}`;
  const coverPhoto = raw.cover_photo?.url || null;

  // Process Slots & Dates
  const rawSlots = raw.available_slots || [];
  const slots: OpportunitySlot[] = rawSlots.map((s: any) => ({
    id: s?.id ? String(s.id) : undefined,
    startDate: s?.start_date || undefined,
    endDate: s?.end_date || undefined,
    status: s?.status || undefined,
  }));

  let earliestStartDate: string | null = raw.earliest_start_date || null;
  let latestEndDate: string | null = raw.latest_end_date || null;

  if (!earliestStartDate && slots.length > 0) {
    const validStartDates = slots
      .map(s => s.startDate)
      .filter(Boolean)
      .sort() as string[];
    if (validStartDates.length > 0) {
      earliestStartDate = validStartDates[0];
    }
  }

  if (!latestEndDate && slots.length > 0) {
    const validEndDates = slots
      .map(s => s.endDate)
      .filter(Boolean)
      .sort()
      .reverse() as string[];
    if (validEndDates.length > 0) {
      latestEndDate = validEndDates[0];
    }
  }

  // Calculate Duration in Weeks
  let durationWeeks: number | null = typeof raw.duration === "number" ? raw.duration : null;

  // If duration is null, calculate from first valid slot
  if (!durationWeeks && slots.length > 0) {
    for (const slot of slots) {
      if (slot.startDate && slot.endDate) {
        const start = new Date(slot.startDate).getTime();
        const end = new Date(slot.endDate).getTime();
        if (!isNaN(start) && !isNaN(end) && end > start) {
          durationWeeks = Math.round((end - start) / (7 * 24 * 3600 * 1000));
          break;
        }
      }
    }
  }

  // If still null, try extracting from title (e.g. "[6 weeks]")
  if (!durationWeeks && raw.title) {
    const match = raw.title.match(/\[?\b(\d+)\s*weeks?\b\]?/i);
    if (match) {
      durationWeeks = parseInt(match[1], 10);
    }
  }

  // Default duration fallback according to programme standards
  if (!durationWeeks || durationWeeks <= 0) {
    if (progInfo.slug === "global-volunteer") durationWeeks = 6;
    else if (progInfo.slug === "global-teacher") durationWeeks = 12;
    else durationWeeks = 12; // default standard Global Talent
  }

  const durationBracket = getDurationBracket(durationWeeks);

  return {
    id: numericId,
    gisId: String(raw.id),
    title: cleanText(raw.title),
    description: cleanText(raw.description),
    skills: Array.from(new Set(skills)),
    backgrounds: Array.from(new Set(backgrounds)),
    languages: Array.from(new Set(languages)),
    location: cleanText(raw.location) || `${city}, ${country}`.replace(/^, /, ""),
    country,
    city,
    organisation: cleanText(raw.organisation?.name) || "AIESEC Partner Organization",
    programme: {
      id: String(raw.programme?.id || "8"),
      short_name: raw.programme?.short_name || "GT",
    },
    programmeName: progInfo.name,
    programmeSlug: progInfo.slug,
    sourceUrl,
    coverPhoto,
    status: raw.status || "open",
    duration: durationWeeks,
    durationBracket,
    earliestStartDate,
    latestEndDate,
    slots,
  };
}

export async function fetchRealAiesecOpportunities(options?: {
  keywords?: string[];
  fieldOfStudy?: string;
  limit?: number;
  customToken?: string;
  preferredCountry?: string;
  preferredDuration?: "all" | "short" | "medium" | "long";
  earliestStartDate?: string;
}): Promise<RealOpportunity[]> {
  const token =
    options?.customToken ||
    process.env.AIESEC_GIS_TOKEN ||
    DEFAULT_TOKEN;

  const now = Date.now();
  // Return cached if fresh and no specific keywords or country requested
  if (
    cachedOpportunities.length > 0 &&
    now - lastFetchTime < CACHE_TTL_MS &&
    (!options?.keywords || options.keywords.length === 0) &&
    !options?.preferredCountry
  ) {
    return cachedOpportunities.slice(0, options?.limit || 60);
  }

  const collectedMap = new Map<number, RealOpportunity>();

  // Targeted search terms from CV skills or default tech terms
  const searchTerms = (options?.keywords || [])
    .map(k => k.trim())
    .filter(k => k.length > 2)
    .slice(0, 3);

  // If preferred country provided, include it in search queries
  if (options?.preferredCountry && options.preferredCountry.trim().length > 1) {
    searchTerms.unshift(options.preferredCountry.trim());
  }

  if (searchTerms.length === 0) {
    searchTerms.push("developer", "software");
  }

  // Construct parallel GraphQL queries requesting full slots, dates and durations
  const queryPromises: Promise<any>[] = [];

  // 1. Keyword search queries
  for (const term of searchTerms) {
    const qEscaped = term.replace(/"/g, '\\"');
    const qStr = `
      query {
        opportunities(q: "${qEscaped}", page: 1, per_page: 25, filters: { status: "open" }) {
          data {
            id
            title
            description
            location
            duration
            earliest_start_date
            latest_end_date
            status
            programme { id short_name }
            organisation { name }
            skills { constant_name option }
            backgrounds { constant_name option }
            languages { constant_name option }
            host_lc { name country }
            cover_photo(size: "thumb")
            available_slots {
              id
              start_date
              end_date
              status
            }
          }
        }
      }
    `;
    queryPromises.push(executeGisQuery(qStr, token));
  }

  // 2. Broad open opportunities query
  const broadQuery = `
    query {
      allOpportunity(page: 1, per_page: 40, filters: { status: "open" }) {
        data {
          id
          title
          description
          location
          duration
          earliest_start_date
          latest_end_date
          status
          programme { id short_name }
          organisation { name }
          skills { constant_name option }
          backgrounds { constant_name option }
          languages { constant_name option }
          host_lc { name country }
          cover_photo(size: "thumb")
          available_slots {
            id
            start_date
            end_date
            status
          }
        }
      }
    }
  `;
  queryPromises.push(executeGisQuery(broadQuery, token));

  // Run all queries in parallel
  const results = await Promise.all(queryPromises);

  for (const res of results) {
    if (!res?.data) continue;

    const list =
      res.data.opportunities?.data ||
      res.data.allOpportunity?.data ||
      [];

    for (const raw of list) {
      const transformed = transformRawOpportunity(raw);
      if (transformed && !collectedMap.has(transformed.id)) {
        collectedMap.set(transformed.id, transformed);
      }
    }
  }

  const finalOpportunities = Array.from(collectedMap.values());

  if (finalOpportunities.length > 0) {
    cachedOpportunities = finalOpportunities;
    lastFetchTime = now;
  } else if (cachedOpportunities.length > 0) {
    return cachedOpportunities.slice(0, options?.limit || 60);
  }

  return finalOpportunities.slice(0, options?.limit || 80);
}

