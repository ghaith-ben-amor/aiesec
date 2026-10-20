import { RealOpportunity } from "./aiesec-api";

export interface ParsedCv {
  name?: string;
  email?: string;
  phone?: string;
  skills: string[];
  fieldOfStudy?: string;
  education?: string[] | string;
  experience?: string[];
  projects?: string[];
  languages?: string[];
  summary?: string;
}

export interface MatchResultItem {
  opportunity: RealOpportunity;
  score: number;
  reasons: string[];
  matchingSkills: string[];
  missingSkills: string[];
  matchedExperience?: string[];
  matchedProjects?: string[];
  matchedJobRoles?: string[];
  matchedLanguages?: string[];
}

export interface UserPreferences {
  country?: string;
  duration?: "all" | "short" | "medium" | "long" | string;
  earliestStartDate?: string;
}

function normalizeSkill(s: string): string {
  return s.toLowerCase().trim().replace(/[-_.]/g, " ");
}

/**
 * Extract meaningful keywords from a text string, filtering common stop words.
 */
function extractKeywords(text: string): string[] {
  const stopWords = new Set([
    "the", "and", "for", "with", "that", "this", "from", "are", "will", "have",
    "has", "been", "can", "able", "your", "our", "their", "you", "we", "they",
    "who", "what", "how", "when", "where", "which", "all", "any", "each", "more",
    "most", "some", "such", "than", "also", "both", "but", "not", "only", "other",
    "very", "just", "work", "role", "team", "support", "new", "good", "skills",
    "experience", "internship", "opportunity", "global", "volunteer",
  ]);
  return Array.from(
    new Set(
      text
        .toLowerCase()
        .replace(/[^a-z\s]/g, " ")
        .split(/\s+/)
        .filter(w => w.length > 3 && !stopWords.has(w))
    )
  );
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
  const cvExperience = (cv.experience || []).join(" ").toLowerCase();
  const cvProjects = (cv.projects || []).join(" ").toLowerCase();
  const cvEducation = Array.isArray(cv.education)
    ? cv.education.join(" ").toLowerCase()
    : (cv.education || "").toLowerCase();

  // Combined CV full-text for keyword matching (skills + experience + projects + education + summary + field)
  const cvFullText = [
    cvField,
    cv.summary || "",
    cvExperience,
    cvProjects,
    cvEducation,
    cvSkills.join(" "),
  ].join(" ").toLowerCase();

  return opportunities.map(opp => {
    const oppSkills = opp.skills || [];
    const oppTitle = opp.title.toLowerCase();
    const oppDesc = opp.description.toLowerCase();
    const oppBackgrounds = (opp.backgrounds || []).map(b => b.toLowerCase());
    const oppLanguages = (opp.languages || []).map(l => l.toLowerCase());

    // ── 1. Skill match (CV skills vs opportunity required skills) ──────────
    const matchingSkills: string[] = [];
    const missingSkills: string[] = [];

    oppSkills.forEach(skill => {
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
      skillScore = (matchingSkills.length / realOppSkillsCount) * 40;
    } else {
      skillScore = 20; // neutral base for opportunities without tag restrictions
    }

    // ── 2. Experience match (CV experience text vs opportunity title+description) ──
    let experienceScore = 0;
    const experienceKeywords = extractKeywords(oppTitle + " " + oppDesc);
    const cvExpKeywordHits = experienceKeywords.filter(kw => cvExperience.includes(kw));
    if (cvExperience.length > 10) {
      // Up to 20 pts based on keyword overlap between experience and opportunity
      experienceScore = Math.min(20, cvExpKeywordHits.length * 4);
    }

    // ── 3. Projects match (CV projects text vs opportunity title+description) ──
    let projectScore = 0;
    if (cvProjects.length > 5) {
      const cvProjKeywordHits = experienceKeywords.filter(kw => cvProjects.includes(kw));
      projectScore = Math.min(10, cvProjKeywordHits.length * 3);
    }

    // ── 4. Field of study & Background alignment ──────────────────────────
    let backgroundScore = 0;
    let bgMatchName = "";
    for (const bg of oppBackgrounds) {
      if (
        cvField.includes(bg) ||
        cvEducation.includes(bg) ||
        bg.includes("software") ||
        bg.includes("computer") ||
        bg.includes("engineering") ||
        cvFullText.includes(bg)
      ) {
        backgroundScore = 15;
        bgMatchName = bg;
        break;
      }
    }
    if (backgroundScore === 0 && (cvFullText.includes(oppTitle) || oppTitle.includes(cvField))) {
      backgroundScore = 10;
    }

    // ── 5. Language requirement match ─────────────────────────────────────
    let langScore = 0;
    let matchedLang = "";
    if (oppLanguages.length > 0) {
      const langMatch = oppLanguages.find(ol => cvLanguages.some(cl => cl.includes(ol) || ol.includes(cl)));
      if (langMatch) {
        langScore = 15;
        matchedLang = langMatch;
      }
    } else {
      langScore = 8; // no language restriction — slight bonus
    }

    // ── 6. User Preferences Alignment (Destination, Duration, Start Date) ──
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

    // ── Compute total score ────────────────────────────────────────────────
    // Max breakdown: skills(40) + experience(20) + projects(10) + background(15) + language(15) + preferences(35)
    const rawScore = skillScore + experienceScore + projectScore + backgroundScore + langScore + preferenceBonus;
    const totalScore = Math.min(99, Math.max(30, Math.round(rawScore)));

    // ── Build human-readable reasons ──────────────────────────────────────
    const reasons: string[] = [...preferenceReasons];

    if (matchingSkills.length > 0) {
      reasons.push(`✅ Compétences alignées : ${matchingSkills.slice(0, 4).join(", ")}`);
    }
    if (cvExpKeywordHits.length > 0) {
      reasons.push(`💼 Expérience pertinente détectée : ${cvExpKeywordHits.slice(0, 3).join(", ")}`);
    }
    if (projectScore > 0) {
      reasons.push(`🚀 Projets correspondants au domaine de l'opportunité`);
    }
    if (bgMatchName) {
      reasons.push(`🎓 Correspond à votre domaine académique (${bgMatchName})`);
    } else if (cv.fieldOfStudy) {
      reasons.push(`📚 Pertinent pour votre profil en ${cv.fieldOfStudy}`);
    }
    if (matchedLang) {
      reasons.push(`🌐 Exigence linguistique validée : ${matchedLang.charAt(0).toUpperCase() + matchedLang.slice(1)}`);
    }
    if (opp.organisation && opp.country && preferenceReasons.length === 0) {
      reasons.push(`Opportunité ${opp.programmeName} chez ${opp.organisation} (${opp.country})`);
    }

    const matchedProjectsList = (cv.projects || []).filter(p =>
      oppTitle.includes(p.toLowerCase()) || oppDesc.includes(p.toLowerCase()) || experienceKeywords.some(kw => p.toLowerCase().includes(kw))
    );

    return {
      opportunity: opp,
      score: totalScore,
      reasons,
      matchingSkills,
      missingSkills,
      matchedExperience: cvExpKeywordHits.slice(0, 4),
      matchedProjects: matchedProjectsList.slice(0, 3),
      matchedLanguages: matchedLang ? [matchedLang] : [],
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
- Education: ${Array.isArray(cvParsed.education) ? cvParsed.education.join(", ") : (cvParsed.education || "N/A")}
- Experience: ${(cvParsed.experience || []).slice(0, 5).join(" | ") || "N/A"}
- Projects: ${(cvParsed.projects || []).slice(0, 5).join(" | ") || "N/A"}
- Summary: ${cvText.slice(0, 600)}

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
