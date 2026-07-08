import json
from pathlib import Path
import re
import sys


try:
    sys.stdout.reconfigure(encoding="utf-8")
except Exception:
    pass


# ─── Alias map: normalize skill variations ────────────────────────────────────
ALIASES = {
    "js": "javascript",
    "nodejs": "javascript",
    "node.js": "javascript",
    "node": "javascript",
    "ts": "typescript",
    "mysql": "sql",
    "postgresql": "sql",
    "postgres": "sql",
    "sqlite": "sql",
    "figma": "ui ux",
    "ui/ux": "ui ux",
    "ui": "ui ux",
    "ux": "ui ux",
    "graphic design": "design",
    "web design": "design",
    "content writing": "content creation",
    "writing": "content creation",
    "communicat": "communication",
    "collaboration": "teamwork",
    "team work": "teamwork",
    "team player": "teamwork",
    "problem-solving": "problem solving",
    "ml": "machine learning",
    "ai": "artificial intelligence",
    "deep learning": "machine learning",
    "react.js": "react",
    "reactjs": "react",
    "vue.js": "vue",
    "vuejs": "vue",
    "angular.js": "angular",
    "customer service": "customer service",
    "social media": "social media marketing",
    "social media management": "social media marketing",
    "information technology": "it",
    "marketing": "marketing",
    "sales": "sales",
    "management": "management",
    "leadership": "leadership",
    "public speaking": "presentation",
    "microsoft office": "microsoft office",
    "ms office": "microsoft office",
    "excel": "microsoft office",
    "word": "microsoft office",
    "powerpoint": "microsoft office",
    "business development": "business",
    "business administration": "business",
    "entrepreneurship": "business",
    "data analysis": "data analysis",
    "data science": "data science",
    "software development": "software development",
    "software engineering": "software development",
    "web development": "web development",
    "mobile development": "mobile development",
    "android": "mobile development",
    "ios": "mobile development",
    "flutter": "mobile development",
    "flutterflow": "mobile development",
    "project management": "project management",
    "agile": "project management",
    "scrum": "project management",
}

# ─── Stop words: must NOT be treated as skills ────────────────────────────────
STOP_WORDS = {
    # Generic words
    "the", "and", "for", "with", "that", "this", "from", "are", "will",
    "have", "has", "been", "can", "able", "your", "our", "their", "its",
    "you", "we", "they", "who", "what", "how", "when", "where", "which",
    "all", "any", "each", "more", "most", "some", "such", "than", "then",
    "also", "both", "but", "not", "only", "other", "same", "very", "just",
    # CV/document structure
    "cv", "resume", "skills", "experience", "education", "summary", "profile",
    "student", "intern", "internship", "volunteer", "year", "years", "member",
    "work", "role", "roles", "language", "languages", "certifications",
    "achievement", "achievements", "responsible", "responsibilities",
    "technology", "technologies", "activities", "activity",
    # AIESEC specific
    "aiesec", "exchange", "participant", "lc", "mc", "gv", "gt", "gte", "gta",
    "outgoing", "incoming", "raising", "awareness", "opportunity", "global",
    "volunteer", "talent", "teacher", "programme", "programmes", "program",
    # Location words — NEVER skills
    "tunisia", "tunisie", "tunis", "ariana", "sfax", "sousse", "monastir",
    "remote", "country", "countries", "city", "region", "location", "address",
    "africa", "europe", "asia", "america", "greece", "india", "egypt", "brazil",
    "turkey", "ukraine", "romania", "vietnam", "colombia", "mexico", "kenya",
    "nigeria", "ghana", "rwanda", "bolivia", "nepal", "sri", "lanka", "ecuador",
    # Academic words
    "academic", "bachelor", "master", "degree", "diploma", "university",
    "college", "institute", "faculty", "semester", "course", "courses",
    "lecture", "baccalaureate", "cycle", "preparatory", "school",
    # Filler descriptors
    "good", "strong", "excellent", "great", "high", "new", "various",
    "different", "specific", "important", "based", "including", "providing",
    "support", "supporting", "ensure", "environment", "impact", "strategy",
    "community", "local", "digital", "international", "professional",
    "solutions", "service", "services", "quality", "results", "working",
    "developing", "development", "building", "creating", "implementing",
    "understanding", "learning", "planning", "organization", "organizations",
    "participants", "people", "tasks", "tools", "training", "time",
    "weeks", "months", "level", "area", "areas", "needs", "goals", "goal",
    "follow", "model", "point", "points", "open", "offer", "part",
    "department", "company", "center", "centre", "change", "current",
    "daily", "details", "first", "identify", "include", "knowledge",
    "lead", "leading", "make", "manage", "material", "materials",
    "need", "perform", "plan", "practice", "practices", "prepare",
    "process", "report", "required", "research", "result", "run",
    "running", "session", "sessions", "start", "systems", "talent",
    "technical", "use", "using", "well",
}


def normalize(value: str) -> str:
    v = re.sub(r"\s+", " ", str(value).lower().strip())
    return ALIASES.get(v, v)


def tokenize(text: str) -> set:
    """Extract meaningful word tokens from text, excluding stop words."""
    text = text.lower()
    tokens = set()
    for tok in re.findall(r"[a-zA-Z][a-zA-Z+#.]{1,}", text):
        n = normalize(tok)
        if n and n not in STOP_WORDS and len(n) > 2:
            tokens.add(n)
    return tokens


def skill_overlap(cv_skills: set, opp_skills: list) -> list:
    """
    Return the list of opportunity skills that match CV skills.
    Uses exact match, alias match, and substring containment.
    """
    matched = []
    for raw_skill in opp_skills:
        if not raw_skill or str(raw_skill).strip() in STOP_WORDS:
            continue
        ns = normalize(raw_skill)
        if ns in STOP_WORDS or len(ns) < 2:
            continue
        # Direct or alias match
        if ns in cv_skills:
            matched.append(raw_skill)
            continue
        # Substring match for multi-word skills (e.g. "project management" ⊆ CV skills)
        for cs in cv_skills:
            if len(ns) > 3 and len(cs) > 3 and (ns in cs or cs in ns):
                matched.append(raw_skill)
                break
    return sorted(set(matched))


def match(cv_data: dict, opportunities: list) -> list:
    # ── Build CV profile ────────────────────────────────────────────────────
    cv_skills_raw = [s for s in cv_data.get("skills", []) if s]
    cv_skills = {normalize(s) for s in cv_skills_raw} - STOP_WORDS

    cv_lang_raw = cv_data.get("languages", [])
    cv_languages = {normalize(l) for l in cv_lang_raw} - STOP_WORDS

    # Extract meaningful keywords from CV text (summary + roles + education)
    cv_text_parts = [
        cv_data.get("summary", ""),
        " ".join(cv_data.get("education", [])),
        " ".join(cv_data.get("experience", {}).get("roles", [])
                 if isinstance(cv_data.get("experience"), dict) else []),
        cv_data.get("raw_text", ""),
    ]
    cv_text_tokens = tokenize(" ".join(cv_text_parts))
    all_cv_terms = cv_skills | cv_languages | cv_text_tokens

    ranked = []

    for opp in opportunities:
        opp_skills_raw = opp.get("skills", [])
        opp_lang_raw   = opp.get("languages", [])

        # ── Skill match (CV skills vs opportunity explicit skills) ──────────
        matched_skills    = skill_overlap(cv_skills, opp_skills_raw)
        matched_languages = skill_overlap(cv_languages, opp_lang_raw)

        # ── Keyword match (CV text vs opportunity title+description) ────────
        # Exclude location/category from keyword matching to prevent noise
        opp_text = tokenize(
            (opp.get("title", "") or "") + " " +
            (opp.get("description", "") or "")
        )
        keyword_hits = sorted(all_cv_terms & opp_text)

        # ── Score calculation ───────────────────────────────────────────────
        has_explicit_skills = len(opp_skills_raw) > 0

        if has_explicit_skills:
            # Primary: skill overlap ratio (max 70 pts)
            skill_score = (len(matched_skills) / len(opp_skills_raw)) * 70
            # Bonus: keyword hits in text (max 15 pts, 3 pts each)
            text_score  = min(15, len(keyword_hits) * 3)
            # Penalty: if skills defined but ZERO matched, reduce keyword bonus
            if len(matched_skills) == 0:
                text_score = min(10, len(keyword_hits) * 2)
        else:
            # No explicit skills → use keyword matching as primary signal
            skill_score = 0
            text_score  = min(55, len(keyword_hits) * 5)

        # Language bonus (max 15 pts)
        lang_score = 0
        if opp_lang_raw:
            lang_score = (len(matched_languages) / len(opp_lang_raw)) * 15

        score = round(min(100, skill_score + text_score + lang_score), 1)

        ranked.append({
            **opp,
            "matched_skills":    matched_skills,
            "matched_languages": matched_languages,
            "matched_keywords":  keyword_hits[:6],
            "score":             score,
        })

    # Sort by score descending — show ALL opportunities (even 0% match)
    # so the user can browse all options in their chosen country/programme
    ranked.sort(key=lambda x: (-x["score"], x.get("title", "")))
    return ranked


def load_input(argument: str):
    path = Path(argument)
    if path.exists():
        return json.loads(path.read_text(encoding="utf-8"))
    return json.loads(argument)


def main():
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Usage: matcher.py <cv_json> <opportunities_json>"}))
        return
    cv_data       = load_input(sys.argv[1])
    opportunities = load_input(sys.argv[2])
    print(json.dumps(match(cv_data, opportunities), ensure_ascii=False))


if __name__ == "__main__":
    main()
