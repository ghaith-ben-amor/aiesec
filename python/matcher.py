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
    "seller": "sales",
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
    "iot": "iot",
    "embedded systems": "embedded systems",
    "arduino": "embedded systems",
    "stm32": "embedded systems",
    "teaching": "teaching",
    "teacher": "teaching",
    "education": "education",
    "educator": "teaching",
    "tutoring": "teaching",
    "tutor": "teaching",
    "instructor": "teaching",
    "pedagogy": "teaching",
    "lesson planning": "teaching",
    "classroom management": "teaching",
}

# ─── Stop words: must NOT be treated as skills ────────────────────────────────
STOP_WORDS = {
    # Generic filler
    "the", "and", "for", "with", "that", "this", "from", "are", "will",
    "have", "has", "been", "can", "able", "your", "our", "their", "its",
    "you", "we", "they", "who", "what", "how", "when", "where", "which",
    "all", "any", "each", "more", "most", "some", "such", "than", "then",
    "also", "both", "but", "not", "only", "other", "same", "very", "just",
    # CV structural headers
    "cv", "resume", "summary", "profile", "contact", "address", "email", "phone",
    "responsible", "responsibilities", "activities", "activity",
    # AIESEC specific filler
    "aiesec", "exchange", "participant", "lc", "mc", "gv", "gt", "gte", "gta",
    "outgoing", "incoming", "raising", "awareness", "opportunity",
    # Locations
    "tunisia", "tunisie", "tunis", "ariana", "sfax", "sousse", "monastir",
    "remote", "country", "countries", "city", "region", "location",
    "africa", "europe", "asia", "america", "greece", "india", "egypt", "brazil",
    "turkey", "ukraine", "romania", "vietnam", "colombia", "mexico", "kenya",
    "nigeria", "ghana", "rwanda", "bolivia", "nepal", "sri", "lanka", "ecuador",
    # Filler descriptors
    "good", "strong", "excellent", "great", "high", "new", "various",
    "different", "specific", "important", "based", "including", "providing",
    "support", "supporting", "ensure", "environment", "impact", "strategy",
    "local", "results", "working", "developing", "building", "creating",
    "understanding", "learning", "people", "tasks", "tools", "time",
    "weeks", "months", "level", "area", "areas", "needs", "goals", "goal",
    "open", "offer", "part", "department", "company", "center", "centre",
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
    """Return the list of opportunity skills that match CV skills."""
    matched = []
    for raw_skill in opp_skills:
        if not raw_skill or str(raw_skill).strip() in STOP_WORDS:
            continue
        ns = normalize(raw_skill)
        if ns in STOP_WORDS or len(ns) < 2:
            continue
        if ns in cv_skills:
            matched.append(raw_skill)
            continue
        for cs in cv_skills:
            if len(ns) > 3 and len(cs) > 3 and (ns in cs or cs in ns):
                matched.append(raw_skill)
                break
    return sorted(set(matched))


def match(cv_data: dict, opportunities: list) -> list:
    # ── 1. Parse Skills ────────────────────────────────────────────────────────
    cv_skills_raw = [s for s in cv_data.get("skills", []) if s]
    cv_skills = {normalize(s) for s in cv_skills_raw} - STOP_WORDS

    # ── 2. Parse Languages ────────────────────────────────────────────────────
    cv_lang_raw = cv_data.get("languages", [])
    cv_languages = {normalize(l) for l in cv_lang_raw} - STOP_WORDS
    if not cv_languages:
        cv_languages = {"english"}

    # ── 3. Parse Experience (Roles, Descriptions, Text) ───────────────────────
    exp_data = cv_data.get("experience", {})
    if isinstance(exp_data, dict):
        cv_exp_roles = exp_data.get("roles", [])
        cv_exp_descs = exp_data.get("descriptions", [])
        cv_exp_text = exp_data.get("text", "") or " ".join(cv_exp_roles + cv_exp_descs)
    elif isinstance(exp_data, list):
        cv_exp_roles = exp_data
        cv_exp_descs = []
        cv_exp_text = " ".join(cv_exp_roles)
    else:
        cv_exp_roles = []
        cv_exp_descs = []
        cv_exp_text = str(exp_data)

    cv_exp_tokens = tokenize(cv_exp_text)

    # ── 4. Parse Job Titles / Target Roles ────────────────────────────────────
    cv_job_titles = cv_data.get("job_titles", [])
    if not cv_job_titles and cv_exp_roles:
        cv_job_titles = [re.split(r"[-–—@|(]", r)[0].strip() for r in cv_exp_roles if r]

    # ── 5. Parse Projects ─────────────────────────────────────────────────────
    cv_projects = cv_data.get("projects", [])
    if isinstance(cv_projects, list):
        cv_proj_text = " ".join(cv_projects)
    else:
        cv_proj_text = str(cv_projects)
    cv_proj_tokens = tokenize(cv_proj_text)

    # ── 6. Parse Education ────────────────────────────────────────────────────
    cv_education = cv_data.get("education", [])
    if isinstance(cv_education, list):
        cv_edu_text = " ".join(cv_education)
    else:
        cv_edu_text = str(cv_education)
    cv_edu_tokens = tokenize(cv_edu_text)

    # Combined full pool
    all_cv_terms = cv_skills | cv_languages | cv_exp_tokens | cv_proj_tokens | cv_edu_tokens | tokenize(cv_data.get("summary", ""))

    ranked = []

    for opp in opportunities:
        opp_id    = opp.get("id")
        opp_title = (opp.get("title", "") or "").strip()
        opp_desc  = (opp.get("description", "") or "").strip()
        opp_skills_raw = opp.get("skills", [])
        opp_lang_raw   = opp.get("languages", [])
        opp_bg_raw     = opp.get("backgrounds", [])

        opp_full_text = f"{opp_title} {opp_desc}"
        opp_tokens = tokenize(opp_full_text)
        opp_title_tokens = tokenize(opp_title)

        # ── Dimension A: Skills Overlap (Weight: 30 pts) ──────────────────────
        matched_skills = skill_overlap(cv_skills, opp_skills_raw)
        if opp_skills_raw:
            skill_score = (len(matched_skills) / len(opp_skills_raw)) * 30
        else:
            # Opportunity has no explicit skill list; check skill tokens in description
            skill_hits = cv_skills & opp_tokens
            skill_score = min(25, len(skill_hits) * 5)

        # ── Dimension B: Work Experience Match (Weight: 25 pts) ───────────────
        matched_experience_items = []
        exp_score = 0
        if cv_exp_roles:
            # Check if any role directly aligns with opportunity title or tasks
            for role in cv_exp_roles:
                role_toks = tokenize(role)
                overlap = role_toks & (opp_title_tokens | opp_tokens)
                if overlap:
                    matched_experience_items.append(role)

            # Check keyword hits from descriptions
            exp_keyword_hits = sorted(cv_exp_tokens & opp_tokens)
            if matched_experience_items:
                exp_score = min(25, 12 + len(exp_keyword_hits) * 3)
            else:
                exp_score = min(20, len(exp_keyword_hits) * 3)

        # ── Dimension C: Job / Role Title Fit (Weight: 20 pts) ────────────────
        matched_job_roles = []
        job_score = 0
        for title in cv_job_titles:
            title_toks = tokenize(title)
            if title_toks & opp_title_tokens:
                matched_job_roles.append(title)
            elif title_toks & opp_tokens:
                matched_job_roles.append(title)

        if matched_job_roles:
            job_score = 20 if any(tokenize(t) & opp_title_tokens for t in matched_job_roles) else 12

        # ── Dimension D: Project Portfolio Match (Weight: 15 pts) ─────────────
        matched_projects = []
        proj_score = 0
        if cv_projects:
            for p in cv_projects:
                p_toks = tokenize(p)
                if p_toks & opp_tokens:
                    matched_projects.append(p)
            proj_hits = sorted(cv_proj_tokens & opp_tokens)
            if matched_projects:
                proj_score = min(15, 8 + len(proj_hits) * 2)
            else:
                proj_score = min(12, len(proj_hits) * 2)

        # ── Dimension E: Spoken Languages Match (Weight: 10 pts) ──────────────
        matched_languages = []
        lang_score = 0
        if opp_lang_raw:
            matched_languages = skill_overlap(cv_languages, opp_lang_raw)
            lang_score = (len(matched_languages) / len(opp_lang_raw)) * 10
        else:
            matched_languages = list(cv_languages)[:2]
            lang_score = 8  # default credit when no strict language barrier

        # ── Dimension F: Education / Background Bonus (Max 5 pts) ─────────────
        edu_score = 0
        if opp_bg_raw:
            opp_bg_tokens = tokenize(" ".join(opp_bg_raw))
            if opp_bg_tokens & (cv_edu_tokens | cv_exp_tokens | cv_skills):
                edu_score = 5
        elif cv_education and any(term in opp_full_text.lower() for term in ["engineering", "business", "computer", "developer"]):
            edu_score = 3

        # ── Compute Final Score ───────────────────────────────────────────────
        total_score = skill_score + exp_score + job_score + proj_score + lang_score + edu_score
        final_score = round(min(99.0, max(15.0, total_score)), 1)

        # ── Construct Concise Match Reasons ───────────────────────────────────
        reasons = []
        if matched_skills:
            reasons.append(f"Compétences clés : {', '.join(matched_skills[:4])}")
        if matched_experience_items:
            reasons.append(f"Expérience validée : {', '.join(matched_experience_items[:2])}")
        if matched_job_roles:
            reasons.append(f"Poste en adéquation : {', '.join(matched_job_roles[:2])}")
        if matched_projects:
            reasons.append(f"Projets applicables : {', '.join(matched_projects[:2])}")
        if matched_languages:
            reasons.append(f"Langue confirmée : {', '.join(matched_languages[:2]).title()}")

        general_keywords = sorted(all_cv_terms & opp_tokens)

        ranked.append({
            **opp,
            "score": final_score,
            "matched_skills": matched_skills,
            "matched_experience": matched_experience_items,
            "matched_job_roles": matched_job_roles,
            "matched_projects": matched_projects,
            "matched_languages": matched_languages,
            "matched_keywords": general_keywords[:8],
            "reasons": reasons,
        })

    # Sort descending by score, then by title
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
    cv_data = load_input(sys.argv[1])
    opportunities = load_input(sys.argv[2])
    print(json.dumps(match(cv_data, opportunities), ensure_ascii=False))


if __name__ == "__main__":
    main()
