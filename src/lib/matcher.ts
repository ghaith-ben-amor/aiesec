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
  return s
    .toLowerCase()
    .trim()
    .replace(/[._/-]+/g, " ")
    .replace(/\s+/g, " ");
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

const SKILL_ALIASES: Record<string, string> = {
  js: "javascript",
  nodejs: "node js",
  ts: "typescript",
  html5: "html",
  css3: "css",
  mysql: "sql",
  postgresql: "sql",
  postgres: "sql",
  sqlite: "sql",
  reactjs: "react",
  vuejs: "vue",
  "react js": "react",
  "node js": "node js",
  "graphic designer": "graphic design",
  "web design": "design",
  "ui ux": "ui ux",
  "ui/ux": "ui ux",
  flutter: "mobile development",
  flutterflow: "mobile development",
  android: "mobile development",
  ios: "mobile development",
  arduino: "embedded systems",
  stm32: "embedded systems",
  "software engineering": "software development",
  "software engineer": "software development",
};

function canonicalSkill(value: string): string {
  const normalized = normalizeSkill(value);
  return SKILL_ALIASES[normalized] || normalized;
}

function containsTerm(text: string, term: string): boolean {
  const normalizedText = normalizeSkill(text).replace(/[^a-z0-9+#]+/gi, " ").trim();
  const normalizedTerm = normalizeSkill(term).replace(/[^a-z0-9+#]+/gi, " ").trim();
  if (!normalizedText || !normalizedTerm) return false;
  if (normalizedTerm.length === 1 || /^[c][+#]?$/.test(normalizedTerm)) {
    return new RegExp(`(^|\\s)${normalizedTerm.replace(/[+#]/g, "\\$&")}(?=\\s|$)`, "i").test(normalizedText);
  }
  return new RegExp(`(^|\\s)${normalizedTerm.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")}(?=\\s|$)`, "i").test(normalizedText);
}

function skillMatches(cvSkill: string, oppSkill: string): boolean {
  const c = canonicalSkill(cvSkill);
  const o = canonicalSkill(oppSkill);

  if (c === o) return true;
  if (c.length >= 3 && o.length >= 3 && (containsTerm(c, o) || containsTerm(o, c))) return true;

  // Common synonyms / abbreviations
  const synonyms: Record<string, string[]> = {
    javascript: ["js", "es6"],
    typescript: ["ts"],
    "node js": ["node", "nodejs"],
    react: ["reactjs", "react js"],
    html: ["html5"],
    css: ["css3"],
    sql: ["mysql", "postgresql", "postgres", "sqlite"],
    python: ["django", "flask"],
    java: ["javafx", "spring", "spring boot"],
    "c++": ["qt", "qt creator"],
    "ui ux": ["figma", "graphic design", "adobe photoshop"],
    "graphic design": ["photoshop", "illustrator", "branding"],
    sales: ["seller", "customer service", "negotiation", "commercial"],
    communication: ["teamwork", "collaboration", "leadership"],
    teaching: ["teacher", "education", "tutoring", "tutor", "instructor", "lesson planning", "classroom management", "pedagogy"],
  };

  for (const [key, list] of Object.entries(synonyms)) {
    const keyNorm = canonicalSkill(key);
    const cMatches = c === keyNorm || list.some(item => canonicalSkill(item) === c);
    const oMatches = o === keyNorm || list.some(item => canonicalSkill(item) === o);
    if (cMatches && oMatches) {
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
  const cvLanguages = (cv.languages || ["English"]).map(l => l.toLowerCase());
  const cvSkills = [...(cv.skills || [])];
  cvLanguages.forEach(l => {
    cvSkills.push(l);
    cvSkills.push(`${l} teacher`);
    cvSkills.push(`${l} trainer`);
    cvSkills.push(`${l} instructor`);
  });
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

  // Education/subject expansions
  if (/(math|mathematics|baccalaur)/i.test(cvEducation + " " + cvFullText)) {
    cvSkills.push("math", "mathematics", "math teacher", "stem", "science");
  }
  if (/(engineering|computer|informatique|esprit)/i.test(cvEducation + " " + cvFullText)) {
    cvSkills.push("science", "science teacher", "stem", "coding", "robotics");
  }
  if (/(design|graphic|illustrator|photoshop|visual)/i.test(cvExperience + " " + cvFullText)) {
    cvSkills.push("art", "art instructor", "design instructor", "visual arts");
  }

  return opportunities.map(opp => {
    const oppSkills = opp.skills || [];
    const oppTitle = opp.title.toLowerCase();
    const oppDesc = opp.description.toLowerCase();
    const oppBackgrounds = (opp.backgrounds || []).map(b => b.toLowerCase());
    const oppLanguages = (opp.languages || []).map(l => l.toLowerCase());

    const isTeacher =
      opp.programme?.short_name === "GTe" ||
      opp.programmeSlug === "global-teacher" ||
      (opp.programmeName || "").toLowerCase().includes("teacher") ||
      /\b(?:teach|teacher|teaching|instructor|tutor|educator|trainer)\b/i.test(oppTitle);

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
    let skillMatchRatio = 0;
    const realOppSkillsCount = matchingSkills.length + missingSkills.length;
    if (realOppSkillsCount > 0) {
      skillMatchRatio = matchingSkills.length / realOppSkillsCount;
      skillScore = Math.round(skillMatchRatio * 100);
    } else {
      const cvSkillHits = cvSkills.filter(skill =>
        containsTerm(`${oppTitle} ${oppDesc}`, skill)
      );
      skillMatchRatio = Math.min(1, cvSkillHits.length / 5);
      skillScore = Math.round(skillMatchRatio * 100);
    }

    // ── 2. Experience match (CV experience text vs opportunity title+description) ──
    let experienceScore = 0;
    let experienceMatchRatio = 0;
    const experienceKeywords = extractKeywords(oppTitle + " " + oppDesc);
    const cvExpKeywordHits = experienceKeywords.filter(kw => containsTerm(cvExperience, kw));
    if (cvExperience.length > 5 || cvFullText.length > 50) {
      if (containsTerm(cvFullText, oppTitle) || containsTerm(cvExperience, oppTitle)) {
        experienceMatchRatio = 1;
      } else {
        experienceMatchRatio = Math.min(1, cvExpKeywordHits.length / 5);
      }
    }
    if (isTeacher) {
      if (
        (oppTitle.includes("english") && cvLanguages.includes("english")) ||
        (oppTitle.includes("french") && cvLanguages.includes("french")) ||
        (oppTitle.includes("math") && /(math|baccalaur|engineer)/i.test(cvFullText)) ||
        (oppTitle.includes("science") && /(engineer|computer|informatique|science)/i.test(cvFullText)) ||
        (oppTitle.includes("art") && /(design|graphic|illustrator|photoshop)/i.test(cvFullText))
      ) {
        experienceMatchRatio = Math.max(experienceMatchRatio, 1);
      } else if (cvLanguages.length >= 2) {
        experienceMatchRatio = Math.max(experienceMatchRatio, 0.66);
      }
    }
    experienceScore = Math.round(experienceMatchRatio * 100);

    // ── 3. Projects match (CV projects text vs opportunity title+description) ──
    let projectScore = 0;
    if (cvProjects.length > 5) {
      const cvProjKeywordHits = experienceKeywords.filter(kw => containsTerm(cvProjects, kw));
      projectScore = Math.min(10, cvProjKeywordHits.length * 3);
    }
    if (projectScore === 0 && (experienceScore >= 20 || /(web|app|platform|design|fintech|system|embedded|project)/i.test(cvFullText))) {
      projectScore = 7;
    }

    // ── 4. Field of study & Background alignment ──────────────────────────
    let backgroundScore = 0;
    let bgMatchName = "";
    for (const bg of oppBackgrounds) {
      if (
        cvField.includes(bg) ||
        cvEducation.includes(bg) ||
        containsTerm(cvField, bg) ||
        containsTerm(cvEducation, bg) ||
        containsTerm(cvFullText, bg)
      ) {
        backgroundScore = 15;
        bgMatchName = bg;
        break;
      }
    }
    if (backgroundScore === 0) {
      if (/(engineering|computer|informatique|software|esprit)/i.test(cvFullText)) {
        if (/(developer|engineer|software|design|tech|web|data)/i.test(oppTitle)) {
          backgroundScore = 15;
        } else {
          backgroundScore = 9;
        }
      } else if (/(business|management|marketing|finance|commerce)/i.test(cvFullText)) {
        if (/(sales|marketing|business|finance|hr|management)/i.test(oppTitle)) {
          backgroundScore = 15;
        } else {
          backgroundScore = 9;
        }
      } else if (/(teacher|teaching|education|languages)/i.test(cvFullText)) {
        if (/(teacher|teaching|trainer|instructor|educator)/i.test(oppTitle)) {
          backgroundScore = 15;
        } else {
          backgroundScore = 9;
        }
      } else if (cvFullText.includes(oppTitle) || oppTitle.includes(cvField)) {
        backgroundScore = 10;
      }
    }

    // ── 5. Language requirement match ─────────────────────────────────────
    let langScore = 0;
    let matchedLang = "";
    if (oppLanguages.length > 0) {
      const langMatch = oppLanguages.find(ol =>
        cvLanguages.some(cl => containsTerm(cl, ol) || containsTerm(ol, cl))
      );
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
    const weightedCoreScore = (skillScore * 0.8) + (experienceScore * 0.2);
    const totalScore = Math.min(100, Math.max(0, Math.round(weightedCoreScore + preferenceBonus)));

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
      containsTerm(oppTitle, p) ||
      containsTerm(oppDesc, p) ||
      experienceKeywords.some(kw => containsTerm(p, kw))
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
        score: fallback.score,
        reasons: rating?.reasons?.length ? rating.reasons : fallback.reasons,
        matchingSkills: fallback.matchingSkills,
        missingSkills: fallback.missingSkills,
      };
    }).sort((a, b) => b.score - a.score);
  } catch {
    return heuristicMatcher(cvParsed, opportunities, preferences);
  }
}
