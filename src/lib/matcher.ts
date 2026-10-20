import { RealOpportunity } from "./aiesec-api";

export interface ParsedCv {
  name?: string;
  email?: string;
  phone?: string;
  skills: string[];
  fieldOfStudy?: string;
  education?: string[] | string;
  experience?: string[];
  languages?: string[];
  summary?: string;
}

export interface MatchResultItem {
  opportunity: RealOpportunity;
  score: number;
  reasons: string[];
  matchingSkills: string[];
  missingSkills: string[];
}

export interface UserPreferences {
  country?: string;
  duration?: "all" | "short" | "medium" | "long" | string;
  earliestStartDate?: string;
}

function normalizeSkill(s: string): string {
  return s.toLowerCase().trim().replace(/[-_.]/g, " ");
}

function skillMatches(cvSkill: string, oppSkill: string): boolean {
  const c = normalizeSkill(cvSkill);
  const o = normalizeSkill(oppSkill);

  if (c === o) return true;
  if (c.includes(o) || o.includes(c)) return true;

  // Common synonyms / abbreviations
  const synonyms: Record<string, string[]> = {
    javascript: ["js", "es6", "web development"],
    typescript: ["ts"],
    nodejs: ["node", "node js", "backend"],
    react: ["reactjs", "react js", "frontend"],
    html: ["html5", "web"],
    css: ["css3", "styles", "web"],
    sql: ["mysql", "postgresql", "postgres", "database", "sqlite"],
    python: ["django", "flask"],
    java: ["javafx", "spring", "spring boot"],
    "c++": ["qt", "qt creator", "c"],
    "ui/ux": ["figma", "graphic design", "adobe photoshop", "design"],
    "graphic design": ["photoshop", "illustrator", "branding"],
    sales: ["seller", "customer service", "negotiation", "commercial"],
    communication: ["teamwork", "collaboration", "leadership"],
  };

  for (const [key, list] of Object.entries(synonyms)) {
    const keyNorm = normalizeSkill(key);
    if ((c === keyNorm || list.some(item => c.includes(item))) &&
        (o === keyNorm || list.some(item => o.includes(item)))) {
      return true;
    }
  }

  return false;
}

export function heuristicMatcher(
  cv: ParsedCv,
  opportunities: RealOpportunity[],
  preferences?: UserPreferences
): MatchResultItem[] {
  const cvSkills = cv.skills || [];
  const cvLanguages = (cv.languages || ["English"]).map(l => l.toLowerCase());
  const cvField = (cv.fieldOfStudy || "").toLowerCase();
  const cvText = `${cvField} ${cv.summary || ""} ${(cv.experience || []).join(" ")}`.toLowerCase();

  return opportunities.map(opp => {
    const oppSkills = opp.skills || [];
    const oppTitle = opp.title.toLowerCase();
    const oppDesc = opp.description.toLowerCase();
    const oppBackgrounds = (opp.backgrounds || []).map(b => b.toLowerCase());
    const oppLanguages = (opp.languages || []).map(l => l.toLowerCase());

    // 1. Skill overlap
    const matchingSkills: string[] = [];
    const missingSkills: string[] = [];

    oppSkills.forEach(skill => {
      // Ignore generic education level constant like "High School", "Bachelor" if stored in skills
      if (/^(high school|bachelor|master|phd)$/i.test(skill)) return;

      const found = cvSkills.some(cs => skillMatches(cs, skill));
      if (found) {
        matchingSkills.push(skill);
      } else {
        missingSkills.push(skill);
      }
    });

    let skillScore = 0;
    const realOppSkillsCount = matchingSkills.length + missingSkills.length;
    if (realOppSkillsCount > 0) {
      skillScore = (matchingSkills.length / realOppSkillsCount) * 45;
    } else {
      skillScore = 25; // neutral base for opportunities without tag restrictions
    }

    // 2. Field of study & Background alignment
    let backgroundScore = 0;
    let bgMatchName = "";
    for (const bg of oppBackgrounds) {
      if (
        cvField.includes(bg) ||
        bg.includes("software") ||
        bg.includes("computer") ||
        bg.includes("engineering") ||
        cvText.includes(bg)
      ) {
        backgroundScore = 25;
        bgMatchName = bg;
        break;
      }
    }
    if (backgroundScore === 0 && (cvText.includes(oppTitle) || oppTitle.includes(cvField))) {
      backgroundScore = 15;
    }

    // 3. Language requirement match
    let langScore = 0;
    let matchedLang = "";
    if (oppLanguages.length > 0) {
      const match = oppLanguages.find(ol => cvLanguages.some(cl => cl.includes(ol) || ol.includes(cl)));
      if (match) {
        langScore = 15;
        matchedLang = match;
      }
    } else {
      langScore = 10;
    }

    // 4. Role keywords bonus
    let roleBonus = 0;
    const roleKeywords = ["developer", "software", "engineer", "designer", "sales", "marketing", "web", "iot", "app"];
    for (const kw of roleKeywords) {
      if (oppTitle.includes(kw) && cvText.includes(kw)) {
        roleBonus += 5;
      }
    }
    roleBonus = Math.min(15, roleBonus);

    // 5. User Preferences Alignment (Destination, Duration, Start Date)
    let preferenceBonus = 0;
    const preferenceReasons: string[] = [];

    if (preferences?.country && preferences.country.trim() && preferences.country !== "all") {
      const prefCountry = preferences.country.trim().toLowerCase();
      const oppCountry = (opp.country || "").toLowerCase();
      const oppLocation = (opp.location || "").toLowerCase();

      if (oppCountry.includes(prefCountry) || prefCountry.includes(oppCountry) || oppLocation.includes(prefCountry)) {
        preferenceBonus += 15;
        preferenceReasons.push(`🌟 Destination préférée correspondante (${opp.country})`);
      }
    }

    if (preferences?.duration && preferences.duration !== "all") {
      if (opp.durationBracket === preferences.duration) {
        preferenceBonus += 10;
        const durLabel =
          preferences.duration === "short"
            ? "Court terme (6 - 12 semaines)"
            : preferences.duration === "medium"
            ? "Moyen terme (13 - 26 semaines)"
            : "Long terme (27 - 78 semaines)";
        preferenceReasons.push(`⏱️ Durée idéale : ${opp.duration} semaines (${durLabel})`);
      }
    }

    if (preferences?.earliestStartDate && preferences.earliestStartDate.trim()) {
      if (opp.earliestStartDate) {
        const prefDate = new Date(preferences.earliestStartDate).getTime();
        const oppDate = new Date(opp.earliestStartDate).getTime();
        if (!isNaN(prefDate) && !isNaN(oppDate) && oppDate >= prefDate) {
          preferenceBonus += 10;
          preferenceReasons.push(`📅 Date de départ alignée (dès le ${opp.earliestStartDate})`);
        }
      }
    }

    // Calculate total score (min 45, max 99)
    const rawScore = skillScore + backgroundScore + langScore + roleBonus + preferenceBonus;
    const totalScore = Math.min(99, Math.max(45, Math.round(rawScore)));

    // Formulate personalized match reasons
    const reasons: string[] = [...preferenceReasons];
    if (matchingSkills.length > 0) {
      reasons.push(`Compétences clés alignées : ${matchingSkills.slice(0, 4).join(", ")}`);
    }
    if (bgMatchName) {
      reasons.push(`Correspond à votre domaine académique (${bgMatchName})`);
    } else if (cv.fieldOfStudy) {
      reasons.push(`Pertinent pour votre profil en ${cv.fieldOfStudy}`);
    }
    if (matchedLang) {
      reasons.push(`Exigence linguistique validée : ${matchedLang.charAt(0).toUpperCase() + matchedLang.slice(1)}`);
    }
    if (opp.organisation && opp.country && preferenceReasons.length === 0) {
      reasons.push(`Opportunité ${opp.programmeName} chez ${opp.organisation} (${opp.country})`);
    }

    return {
      opportunity: opp,
      score: totalScore,
      reasons,
      matchingSkills,
      missingSkills,
    };
  }).sort((a, b) => b.score - a.score);
}

export async function matchCvWithOpportunities(
  cvText: string,
  cvParsed: ParsedCv,
  opportunities: RealOpportunity[],
  preferences?: UserPreferences
): Promise<MatchResultItem[]> {
  const apiKey = process.env.GROQ_API_KEY;

  if (!apiKey || opportunities.length === 0) {
    return heuristicMatcher(cvParsed, opportunities, preferences);
  }

  try {
    const model = process.env.GROQ_MODEL || "llama-3.3-70b-versatile";
    const prompt = `You are an expert AIESEC opportunity matcher. 
Evaluate this candidate's CV profile against the available real AIESEC opportunities.

Candidate Profile:
- Skills: ${cvParsed.skills.join(", ")}
- Field of Study: ${cvParsed.fieldOfStudy || "N/A"}
- Languages: ${(cvParsed.languages || ["English"]).join(", ")}
- Experience Summary: ${cvText.slice(0, 1000)}

Opportunities to rank (top candidates):
${JSON.stringify(
  opportunities.slice(0, 25).map(o => ({
    id: o.id,
    title: o.title,
    organisation: o.organisation,
    skills: o.skills,
    location: o.location,
    country: o.country,
  })),
  null,
  2
)}

Return a JSON array of objects with structure:
[
  {
    "id": number,
    "score": number (50 to 98),
    "reasons": string[] (2-3 concise French bullet points explaining the match)
  }
]
Only return a valid JSON array, no extra markdown or explanations.`;

    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 8000);

    const res = await fetch("https://api.groq.com/openai/v1/chat/completions", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${apiKey}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        model: model,
        messages: [{ role: "user", content: prompt }],
        temperature: 0.2,
      }),
      signal: controller.signal,
    });
    clearTimeout(timeout);

    if (!res.ok) {
      return heuristicMatcher(cvParsed, opportunities, preferences);
    }

    const data = await res.json();
    const content = data.choices?.[0]?.message?.content || "";
    const jsonMatch = content.match(/\[[\s\S]*\]/);
    if (!jsonMatch) {
      return heuristicMatcher(cvParsed, opportunities, preferences);
    }

    const ratings: { id: number; score: number; reasons: string[] }[] = JSON.parse(jsonMatch[0]);

    return opportunities.map(opp => {
      const rating = ratings.find(r => r.id === opp.id);
      const fallback = heuristicMatcher(cvParsed, [opp], preferences)[0];
      return {
        opportunity: opp,
        score: rating ? Math.min(100, Math.max(40, rating.score)) : fallback.score,
        reasons: rating?.reasons?.length ? rating.reasons : fallback.reasons,
        matchingSkills: fallback.matchingSkills,
        missingSkills: fallback.missingSkills,
      };
    }).sort((a, b) => b.score - a.score);
  } catch {
    return heuristicMatcher(cvParsed, opportunities, preferences);
  }
}
