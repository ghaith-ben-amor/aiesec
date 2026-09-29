export interface ParsedCv {
  name?: string;
  email?: string;
  phone?: string;
  skills: string[];
  fieldOfStudy?: string;
  education?: string;
  experience?: string[];
  summary?: string;
}

export interface OpportunityItem {
  id: number;
  title: string;
  description: string;
  skills: string[];
  location: string;
  sourceUrl: string;
}

export interface MatchResultItem {
  opportunity: OpportunityItem;
  score: number;
  reasons: string[];
  matchingSkills: string[];
  missingSkills: string[];
}

export function heuristicMatcher(cv: ParsedCv, opportunities: OpportunityItem[]): MatchResultItem[] {
  const cvSkills = (cv.skills || []).map(s => s.toLowerCase().trim());
  const cvText = `${cv.fieldOfStudy || ''} ${cv.summary || ''} ${(cv.experience || []).join(' ')}`.toLowerCase();

  return opportunities.map(opp => {
    const oppSkills = (opp.skills || []).map(s => s.toLowerCase().trim());
    const oppTitle = opp.title.toLowerCase();
    const oppDesc = opp.description.toLowerCase();

    // 1. Skill overlap score
    const matchingSkills: string[] = [];
    const missingSkills: string[] = [];

    oppSkills.forEach(skill => {
      if (cvSkills.some(cs => cs.includes(skill) || skill.includes(cs))) {
        matchingSkills.push(skill);
      } else {
        missingSkills.push(skill);
      }
    });

    let skillScore = 0;
    if (oppSkills.length > 0) {
      skillScore = (matchingSkills.length / oppSkills.length) * 50;
    } else {
      skillScore = 30;
    }

    // 2. Field / Title relevance
    let relevanceScore = 0;
    cvSkills.forEach(skill => {
      if (oppTitle.includes(skill) || oppDesc.includes(skill)) {
        relevanceScore += 10;
      }
    });
    if (cvText.includes(oppTitle) || oppTitle.includes(cv.fieldOfStudy?.toLowerCase() || '')) {
      relevanceScore += 25;
    }
    relevanceScore = Math.min(relevanceScore, 50);

    const totalScore = Math.min(98, Math.round(skillScore + relevanceScore + 15));

    const reasons: string[] = [];
    if (matchingSkills.length > 0) {
      reasons.push(`Matches key skills: ${matchingSkills.slice(0, 3).join(', ')}`);
    }
    if (cv.fieldOfStudy && oppDesc.includes(cv.fieldOfStudy.toLowerCase())) {
      reasons.push(`Aligned with field of study: ${cv.fieldOfStudy}`);
    }
    if (reasons.length === 0) {
      reasons.push(`Good general opportunity match for international development.`);
    }

    return {
      opportunity: opp,
      score: totalScore,
      reasons,
      matchingSkills,
      missingSkills
    };
  }).sort((a, b) => b.score - a.score);
}

export async function matchCvWithOpportunities(cvText: string, cvParsed: ParsedCv, opportunities: OpportunityItem[]): Promise<MatchResultItem[]> {
  const apiKey = process.env.GROQ_API_KEY;
  
  if (!apiKey || opportunities.length === 0) {
    return heuristicMatcher(cvParsed, opportunities);
  }

  try {
    const model = process.env.GROQ_MODEL || "llama-3.3-70b-versatile";
    const prompt = `You are an expert AIESEC opportunity matcher. 
Evaluate this candidate's CV profile against the available AIESEC opportunities.

Candidate Profile:
- Skills: ${cvParsed.skills.join(', ')}
- Field of Study: ${cvParsed.fieldOfStudy || 'N/A'}
- Experience Summary: ${cvText.slice(0, 1000)}

Opportunities to rank:
${JSON.stringify(opportunities.map(o => ({ id: o.id, title: o.title, skills: o.skills, location: o.location })), null, 2)}

Return a JSON array of objects with structure:
[
  {
    "id": number,
    "score": number (0 to 100),
    "reasons": string[] (1-2 bullet points explaining why it's a fit)
  }
]
Only return valid JSON array, no extra markdown or explanations.`;

    const res = await fetch("https://api.groq.com/openai/v1/chat/completions", {
      method: "POST",
      headers: {
        "Authorization": `Bearer ${apiKey}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        model: model,
        messages: [{ role: "user", content: prompt }],
        temperature: 0.2,
      }),
    });

    if (!res.ok) {
      return heuristicMatcher(cvParsed, opportunities);
    }

    const data = await res.json();
    const content = data.choices?.[0]?.message?.content || "";
    const jsonMatch = content.match(/\[[\s\S]*\]/);
    if (!jsonMatch) {
      return heuristicMatcher(cvParsed, opportunities);
    }

    const ratings: { id: number; score: number; reasons: string[] }[] = JSON.parse(jsonMatch[0]);

    return opportunities.map(opp => {
      const rating = ratings.find(r => r.id === opp.id);
      const fallback = heuristicMatcher(cvParsed, [opp])[0];
      return {
        opportunity: opp,
        score: rating ? Math.min(100, Math.max(0, rating.score)) : fallback.score,
        reasons: rating?.reasons?.length ? rating.reasons : fallback.reasons,
        matchingSkills: fallback.matchingSkills,
        missingSkills: fallback.missingSkills,
      };
    }).sort((a, b) => b.score - a.score);
  } catch {
    return heuristicMatcher(cvParsed, opportunities);
  }
}
