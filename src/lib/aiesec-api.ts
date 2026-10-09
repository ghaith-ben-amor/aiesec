// AIESEC Global Information System (GIS) API Client
const AIESEC_GRAPHQL_ENDPOINT = "https://gis-api.aiesec.org/graphql";
const DEFAULT_TOKEN = "3Zy3WdCwUE70rJBIlJ-AHTsjdmZQJrz7o8BgUi3TIdk";

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
}

// In-memory cache for fast lookups and resilience
let cachedOpportunities: RealOpportunity[] = [];
let lastFetchTime = 0;
const CACHE_TTL_MS = 10 * 60 * 1000; // 10 minutes cache

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
  const country = raw.host_lc?.country || raw.location?.split(",")?.pop()?.trim() || "Global";
  const city = raw.host_lc?.name || raw.location?.split(",")?.[0]?.trim() || "";

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
  };
}

export async function fetchRealAiesecOpportunities(options?: {
  keywords?: string[];
  fieldOfStudy?: string;
  limit?: number;
  customToken?: string;
}): Promise<RealOpportunity[]> {
  const token =
    options?.customToken ||
    process.env.AIESEC_GIS_TOKEN ||
    DEFAULT_TOKEN;

  const now = Date.now();
  // Return cached if fresh and no specific keywords requested
  if (
    cachedOpportunities.length > 0 &&
    now - lastFetchTime < CACHE_TTL_MS &&
    (!options?.keywords || options.keywords.length === 0)
  ) {
    return cachedOpportunities.slice(0, options?.limit || 50);
  }

  const collectedMap = new Map<number, RealOpportunity>();

  // Determine targeted search terms from CV skills or default tech terms
  const searchTerms = (options?.keywords || [])
    .map(k => k.trim())
    .filter(k => k.length > 2)
    .slice(0, 3);

  // If no terms provided, use broad standard search terms
  if (searchTerms.length === 0) {
    searchTerms.push("developer", "software");
  }

  // Construct parallel GraphQL queries
  const queryPromises: Promise<any>[] = [];

  // 1. Keyword search queries
  for (const term of searchTerms) {
    const qEscaped = term.replace(/"/g, '\\"');
    const qStr = `
      query {
        opportunities(q: "${qEscaped}", page: 1, per_page: 20, filters: { status: "open" }) {
          data {
            id
            title
            description
            location
            status
            programme { id short_name }
            organisation { name }
            skills { constant_name option }
            backgrounds { constant_name option }
            languages { constant_name option }
            host_lc { name country }
            cover_photo(size: "thumb")
          }
        }
      }
    `;
    queryPromises.push(executeGisQuery(qStr, token));
  }

  // 2. Broad open opportunities query
  const broadQuery = `
    query {
      allOpportunity(page: 1, per_page: 35, filters: { status: "open" }) {
        data {
          id
          title
          description
          location
          status
          programme { id short_name }
          organisation { name }
          skills { constant_name option }
          backgrounds { constant_name option }
          languages { constant_name option }
          host_lc { name country }
          cover_photo(size: "thumb")
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
    // If request failed but we have cache, reuse cache
    return cachedOpportunities.slice(0, options?.limit || 50);
  }

  return finalOpportunities.slice(0, options?.limit || 60);
}
