import json
from pathlib import Path
import re
import sys

try:
    sys.stdout.reconfigure(encoding="utf-8")
except Exception:
    pass

# ─── Aliases & Normalization ──────────────────────────────────────────────────
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
    "graphic design": "graphic design",
    "graphic designer": "graphic design",
    "web design": "web design",
    "photoshop": "graphic design",
    "adobe photoshop": "graphic design",
    "illustrator": "graphic design",
    "adobe illustrator": "graphic design",
    "content writing": "content creation",
    "writing": "content creation",
    "react.js": "react",
    "reactjs": "react",
    "vue.js": "vue",
    "vuejs": "vue",
    "angular.js": "angular",
    "customer service": "customer service",
    "social media": "social media marketing",
    "digital marketing": "marketing",
    "media buying": "marketing",
    "sales": "sales",
    "seller": "sales",
    "teaching": "teaching",
    "teacher": "teaching",
    "education": "teaching",
    "educator": "teaching",
    "tutoring": "teaching",
    "tutor": "teaching",
    "instructor": "teaching",
    "pedagogy": "teaching",
    "lesson planning": "teaching",
    "classroom management": "teaching",
    "embedded systems": "embedded systems",
    "arduino": "embedded systems",
    "stm32": "embedded systems",
    "iot": "embedded systems",
    "flutter": "mobile development",
    "flutterflow": "mobile development",
    "android": "mobile development",
    "ios": "mobile development",
    "c#": "c#",
    "c++": "c++",
    "c": "c",
    "python": "python",
    "java": "java",
    "php": "php",
    "symfony": "php",
    "laravel": "php",
}

STOP_WORDS = {
    "the", "and", "for", "with", "that", "this", "from", "are", "will",
    "have", "has", "been", "can", "able", "your", "our", "their", "its",
    "you", "we", "they", "who", "what", "how", "when", "where", "which",
    "all", "any", "each", "more", "most", "some", "such", "than", "then",
    "also", "both", "but", "not", "only", "other", "same", "very", "just",
    "cv", "resume", "summary", "profile", "contact", "address", "email", "phone",
    "responsible", "responsibilities", "activities", "activity", "part",
    "aiesec", "exchange", "participant", "lc", "mc", "gv", "gt", "gte", "gta",
    "outgoing", "incoming", "raising", "awareness", "opportunity", "product",
    "global", "volunteer", "talent", "openings", "open", "offer",
    "good", "strong", "excellent", "great", "high", "new", "various",
    "different", "specific", "important", "based", "including", "providing",
    "support", "supporting", "ensure", "environment", "impact", "strategy",
    "local", "results", "working", "developing", "building", "creating",
    "understanding", "learning", "people", "tasks", "tools", "time",
    "weeks", "months", "level", "area", "areas", "needs", "goals", "goal",
}

DOMAIN_PROFILES = {
    "web_developer": {
        "title_keywords": ["web", "developer", "frontend", "backend", "full stack", "fullstack", "software", "programmer"],
        "cv_skills": {"php", "javascript", "html", "css", "react", "vue", "angular", "node", "sql", "symfony", "laravel", "mvc", "django"},
    },
    "graphic_designer": {
        "title_keywords": ["graphic", "designer", "design", "ui ux", "motion", "creative", "visual", "art"],
        "cv_skills": {"graphic design", "ui ux", "figma", "photoshop", "illustrator", "design", "visual", "video editing"},
    },
    "mobile_developer": {
        "title_keywords": ["mobile", "android", "ios", "flutter", "flutterflow", "app developer"],
        "cv_skills": {"flutter", "flutterflow", "android", "ios", "mobile development"},
    },
    "embedded_engineer": {
        "title_keywords": ["embedded", "iot", "electrical", "hardware", "robotics", "electronics", "firmware"],
        "cv_skills": {"embedded systems", "arduino", "stm32", "c", "c++", "iot", "electronics"},
    },
    "teacher_educator": {
        "title_keywords": ["teacher", "teaching", "trainer", "instructor", "educator", "tutor", "pedagogy", "school", "preschool", "classroom", "curriculum"],
        "cv_skills": {"teaching", "lesson planning", "classroom management", "pedagogy", "education", "curriculum", "english", "french", "tutoring"},
    },
    "sales_business": {
        "title_keywords": ["sales", "seller", "commercial", "business developer", "business development", "account manager", "negotiation"],
        "cv_skills": {"sales", "customer service", "business development", "negotiation", "crm", "commercial"},
    },
    "marketing_digital": {
        "title_keywords": ["marketing", "digital marketing", "media buying", "social media", "content creator", "seo", "branding"],
        "cv_skills": {"marketing", "social media marketing", "digital marketing", "content creation", "media buying", "seo"},
    },
    "hr_recruitment": {
        "title_keywords": ["hr", "human resources", "recruitment", "talent acquisition"],
        "cv_skills": {"human resources", "recruitment", "talent acquisition"},
    },
    "finance_accounting": {
        "title_keywords": ["finance", "financial", "accounting", "accountant", "audit", "banking"],
        "cv_skills": {"finance", "accounting", "financial analysis", "audit"},
    },
    "volunteer_impact": {
        "title_keywords": ["skill up", "scale up", "aquatica", "on the map", "discover", "classroom", "fingerprint", "volunteer"],
        "cv_skills": {"communication", "teamwork", "leadership", "project management", "english", "teaching", "marketing", "sales"},
    }
}

GV_THEMES = {
    "skill up":   {"keywords": ["skills", "training", "coding", "software", "web", "design", "professional"], "bonus_domain": "web_developer"},
    "scale up":   {"keywords": ["entrepreneurship", "business", "startups", "management", "growth"], "bonus_domain": "sales_business"},
    "aquatica":   {"keywords": ["environment", "sustainability", "conservation", "water", "nature"], "bonus_domain": "volunteer_impact"},
    "discover":   {"keywords": ["culture", "language", "tourism", "heritage", "exchange"], "bonus_domain": "teacher_educator"},
    "classroom":  {"keywords": ["children", "education", "school", "teaching", "youth"], "bonus_domain": "teacher_educator"},
    "map":        {"keywords": ["community", "development", "social", "impact"], "bonus_domain": "volunteer_impact"},
    "fingerprint":{"keywords": ["health", "wellbeing", "society", "empowerment"], "bonus_domain": "volunteer_impact"},
}

def normalize(val: str) -> str:
    v = re.sub(r"[^a-zA-Z0-9+#]+", " ", str(val).lower()).strip()
    return ALIASES.get(v, v)

def tokenize(text: str) -> set:
    text = str(text).lower()
    toks = set()
    for t in re.findall(r"[a-zA-Z][a-zA-Z0-9+#]{1,}", text):
        norm = normalize(t)
        if norm and norm not in STOP_WORDS and len(norm) > 1:
            toks.add(norm)
    return toks

def match(cv_data: dict, opportunities: list) -> list:
    raw_text = str(cv_data.get("raw_text", "")).lower()
    summary = str(cv_data.get("summary", "")).lower()

    # ── 1. Candidate Skills ───────────────────────────────────────────────────
    raw_skills = [str(s) for s in cv_data.get("skills", []) if s]
    cv_skills = {normalize(s) for s in raw_skills} - STOP_WORDS

    # ── 2. Candidate Experience & Roles ───────────────────────────────────────
    exp_data = cv_data.get("experience", {})
    if isinstance(exp_data, dict):
        cv_roles = exp_data.get("roles", [])
        cv_descs = exp_data.get("descriptions", [])
        exp_full = exp_data.get("text", "") or " ".join(cv_roles + cv_descs)
    elif isinstance(exp_data, list):
        cv_roles = exp_data
        cv_descs = []
        exp_full = " ".join(cv_roles)
    else:
        cv_roles = []
        cv_descs = []
        exp_full = str(exp_data)

    cv_job_titles = cv_data.get("job_titles") or []
    if not cv_job_titles:
        cv_job_titles = []
        for r in cv_roles:
            clean_r = re.split(r"[-–—@|(]", str(r))[0].strip()
            if clean_r and len(clean_r) > 3 and not re.search(r'\d', clean_r):
                cv_job_titles.append(clean_r)
        for jt in [
            "web developer", "software engineer", "frontend developer", "backend developer",
            "full stack developer", "c# developer", "java developer", "python developer",
            "mobile developer", "graphic designer", "ui ux designer", "motion graphics designer",
            "sales representative", "business developer", "account manager", "english teacher",
            "marketing specialist", "content creator", "data analyst", "embedded engineer"
        ]:
            if jt in raw_text or jt in summary or jt in exp_full.lower():
                cv_job_titles.append(jt)
    cv_job_titles = list(set(cv_job_titles))

    # Incorporate job_titles and clean roles into cv_skills
    for jt in cv_job_titles:
        norm_jt = normalize(jt)
        if norm_jt:
            cv_skills.add(norm_jt)
            for part in norm_jt.split():
                if len(part) > 2 and part not in STOP_WORDS:
                    cv_skills.add(part)

    for r in cv_roles:
        clean_r = re.split(r"[-–—@|(]", str(r))[0].strip()
        norm_r = normalize(clean_r)
        if norm_r:
            cv_skills.add(norm_r)
            for part in norm_r.split():
                if len(part) > 2 and part not in STOP_WORDS:
                    cv_skills.add(part)

    # Domain Synonyms expansion
    if cv_skills & {"php", "javascript", "html", "css", "react", "vue", "angular", "node", "sql", "symfony", "laravel", "mvc", "django"} or "web developer" in raw_text or "developer" in raw_text:
        cv_skills.update({"web", "developer", "web developer", "web development", "software developer", "software development"})

    if cv_skills & {"figma", "photoshop", "illustrator", "design", "ui ux", "graphic design"} or "graphic designer" in raw_text:
        cv_skills.update({"graphic designer", "graphic design", "designer", "design", "ui ux"})

    if cv_skills & {"teaching", "lesson planning", "classroom management", "pedagogy", "curriculum"} or "teacher" in raw_text:
        cv_skills.update({"teacher", "teaching", "educator", "instructor", "education"})

    if cv_skills & {"sales", "seller", "customer service"} or "sales" in raw_text or "seller" in raw_text:
        cv_skills.update({"sales", "seller", "sales representative", "customer service"})

    if cv_skills & {"marketing", "social media", "seo"} or "marketing" in raw_text:
        cv_skills.update({"marketing", "digital marketing", "marketing specialist"})

    # ── 3. Candidate Languages ────────────────────────────────────────────────
    cv_languages = {normalize(l) for l in cv_data.get("languages", ["English"])} - STOP_WORDS
    if not cv_languages:
        cv_languages = {"english"}

    # ── 4. Candidate Projects & Education ─────────────────────────────────────
    cv_projects = cv_data.get("projects") or []
    projects_text = (" ".join(str(p) for p in cv_projects) + " " + raw_text).lower()
    projects_tokens = tokenize(projects_text)

    cv_edu = cv_data.get("education") or []
    edu_text = (" ".join(str(e) for e in cv_edu) + " " + raw_text + " " + summary).lower()
    edu_tokens = tokenize(edu_text)

    role_and_job_text = (" ".join(str(r) for r in cv_roles) + " " + " ".join(cv_job_titles) + " " + exp_full + " " + summary + " " + raw_text).lower()

    # Identify candidate's primary domain strengths
    domain_strengths = {}
    for domain, dinfo in DOMAIN_PROFILES.items():
        score = 0
        overlap = cv_skills & dinfo["cv_skills"]
        score += len(overlap) * 3

        for kw in dinfo["title_keywords"]:
            if re.search(r'\b' + re.escape(kw) + r'\b', role_and_job_text):
                score += 5
            elif re.search(r'\b' + re.escape(kw) + r'\b', projects_text):
                score += 3
            elif re.search(r'\b' + re.escape(kw) + r'\b', edu_text):
                score += 2

        domain_strengths[domain] = score

    scored_opps = []

    for opp in opportunities:
        title = (opp.get("title", "") or "").strip()
        title_lower = title.lower()
        desc = (opp.get("description", "") or "").strip()
        desc_lower = desc.lower()
        cat = (opp.get("category", "") or "GV").strip()

        # Opportunity Skills
        opp_skills_raw = opp.get("skills", [])
        if isinstance(opp_skills_raw, str):
            try:
                opp_skills_raw = json.loads(opp_skills_raw)
            except Exception:
                opp_skills_raw = [s.strip() for s in opp_skills_raw.split(",") if s.strip()]

        clean_opp_skills = []
        for s in opp_skills_raw:
            ns = normalize(str(s))
            if ns and ns not in STOP_WORDS and len(ns) > 2 and not re.match(r'^\d+\s*week', ns):
                clean_opp_skills.append(ns)
        clean_opp_skills = list(set(clean_opp_skills))

        opp_full_text = f"{title_lower} {desc_lower}"
        opp_tokens = tokenize(opp_full_text)

        # ─── 1. SKILLS SCORE (0 to 45 points) ─────────────────────────────────
        matched_skills = []
        for os in clean_opp_skills:
            if os in cv_skills:
                matched_skills.append(os)
            else:
                for cs in cv_skills:
                    if len(os) >= 4 and len(cs) >= 4 and (os in cs or cs in os):
                        matched_skills.append(os)
                        break
        matched_skills = sorted(set(matched_skills))

        for cs in cv_skills:
            if len(cs) >= 3 and cs not in matched_skills:
                if re.search(r'\b' + re.escape(cs) + r'\b', title_lower):
                    matched_skills.append(cs)
                elif re.search(r'\b' + re.escape(cs) + r'\b', desc_lower):
                    if len(matched_skills) < 6:
                        matched_skills.append(cs)
        matched_skills = sorted(set(matched_skills))

        if clean_opp_skills:
            coverage = len([s for s in matched_skills if s in clean_opp_skills]) / max(1, len(clean_opp_skills))
            skill_score = min(45.0, (coverage * 32.0) + (len(matched_skills) * 4.0))
        else:
            skill_score = min(32.0, len(matched_skills) * 6.0)

        # ─── 2. DOMAIN & JOB ROLE RELEVANCE (0 to 35 points) ──────────────────
        role_score = 0.0
        best_domain_match = None
        matched_roles = []

        for domain, dinfo in DOMAIN_PROFILES.items():
            if domain == "volunteer_impact" and cat != "GV":
                continue
            is_opp_domain = False
            for tkw in dinfo["title_keywords"]:
                if re.search(r'\b' + re.escape(tkw) + r'\b', title_lower):
                    is_opp_domain = True
                    break

            if is_opp_domain:
                candidate_strength = domain_strengths.get(domain, 0)
                if candidate_strength > 0:
                    d_score = min(35.0, 16.0 + candidate_strength * 2.2)
                    if d_score > role_score:
                        role_score = d_score
                        best_domain_match = domain

        for jt in cv_job_titles:
            jt_clean = normalize(jt)
            if jt_clean and len(jt_clean) > 3 and jt_clean in title_lower:
                role_score = max(role_score, 35.0)
                matched_roles.append(jt)
            elif any(w in title_lower for w in jt_clean.split() if len(w) > 3):
                role_score = max(role_score, 25.0)
                matched_roles.append(jt)

        matched_roles = list(set(matched_roles))

        # ─── 3. PROJECTS & PORTFOLIO FIT (0 to 10 points) ─────────────────────
        proj_score = 0.0
        matched_projects = []
        if cv_projects:
            for p in cv_projects:
                p_str = str(p)
                p_toks = tokenize(p_str)
                common = p_toks & opp_tokens
                if len(common) >= 2 or any(len(w) > 4 and w in title_lower for w in p_toks):
                    matched_projects.append(p_str)
            if matched_projects:
                proj_score = min(10.0, 6.0 + len(matched_projects) * 2.0)

        if proj_score == 0.0:
            if role_score >= 20.0 or any(p in projects_text for p in ["web", "app", "platform", "design", "fintech", "system", "embedded", "project"]):
                proj_score = 7.0

        # ─── 4. EDUCATION & BACKGROUND (0 to 12 points) ───────────────────────
        edu_score = 0.0
        if any(w in edu_text for w in ["engineering", "computer", "informatique", "software", "esprit"]):
            if any(w in title_lower for w in ["developer", "engineer", "software", "design", "tech", "web"]):
                edu_score = 12.0
            elif cat == "GTa":
                edu_score = 9.0
            elif cat == "GV":
                edu_score = 7.0
        elif any(w in edu_text for w in ["education", "literature", "teaching", "languages", "anglais"]):
            if any(w in title_lower for w in ["teacher", "teaching", "trainer", "instructor", "educator"]):
                edu_score = 12.0
            elif cat == "GTe":
                edu_score = 10.0
        elif any(w in edu_text for w in ["business", "management", "marketing", "finance", "commerce"]):
            if any(w in title_lower for w in ["sales", "marketing", "business", "finance", "hr", "management"]):
                edu_score = 12.0
            elif cat == "GTa":
                edu_score = 9.0

        if edu_score == 0.0 and len(tokenize(edu_text)) > 0:
            edu_score = 5.0

        # ─── 5. LANGUAGES FIT (0 to 8 points) ─────────────────────────────────
        lang_score = 0.0
        matched_languages = []
        if "english" in cv_languages:
            matched_languages.append("English")
            lang_score = 6.0
        if "french" in cv_languages:
            matched_languages.append("French")
            if "french" in opp_full_text:
                lang_score = 8.0
        if "arabic" in cv_languages and ("arabic" in opp_full_text or "egypt" in opp_full_text):
            matched_languages.append("Arabic")
            lang_score = min(8.0, lang_score + 2.0)

        # ─── 6. GLOBAL VOLUNTEER (GV) PROGRAMME RELEVANCE (0 to 25 points) ────
        gv_score = 0.0
        if cat == "GV":
            for theme, tinfo in GV_THEMES.items():
                if theme in title_lower:
                    bonus_domain = tinfo["bonus_domain"]
                    cand_strength = domain_strengths.get(bonus_domain, 0)
                    gv_score = min(25.0, 15.0 + cand_strength * 1.5)
                    break
            if gv_score == 0.0:
                gv_score = 16.0

        # ─── TOTAL SCORE COMPUTATION ──────────────────────────────────────────
        if cat == "GV":
            raw = (skill_score * 0.5) + (role_score * 0.4) + proj_score + edu_score + lang_score + gv_score
            final_score = round(min(85.0, max(35.0, raw * 85.0 / 72.0)), 1)
        else:
            raw = skill_score + role_score + proj_score + edu_score + lang_score
            if role_score == 0 and not matched_skills:
                final_score = round(min(25.0, max(12.0, (edu_score + lang_score) * 1.2)), 1)
            else:
                final_score = round(min(98.0, max(28.0, raw * 98.0 / 102.0)), 1)

        # Reasons
        reasons = []
        if matched_skills:
            reasons.append(f"Compétences clés : {', '.join(matched_skills[:4])}")
        if matched_roles:
            reasons.append(f"Poste en adéquation : {', '.join(matched_roles[:2])}")
        elif role_score >= 18.0:
            reasons.append("Expérience en adéquation avec le profil recherché")
        if matched_projects:
            reasons.append(f"Projets applicables : {', '.join(matched_projects[:2])}")
        if matched_languages:
            reasons.append(f"Langue confirmée : {', '.join(matched_languages[:2])}")
        if cat == "GV":
            reasons.append("Projet Global Volunteer accessible à votre profil")

        scored_opps.append({
            **opp,
            "score": final_score,
            "matched_skills": matched_skills,
            "matched_experience": matched_roles,
            "matched_job_roles": matched_roles,
            "matched_projects": matched_projects,
            "matched_languages": matched_languages,
            "reasons": reasons,
        })

    # Sort strictly by score descending
    scored_opps.sort(key=lambda x: (-x["score"], x.get("title", "")))

    # Smart diversification: ensure max 2 duplicate titles on Page 1
    title_counts = {}
    primary = []
    deferred = []
    for opp in scored_opps:
        tn = re.sub(r"\s*[\[(].*?[\])]", "", opp.get("title", "")).lower().strip()
        cnt = title_counts.get(tn, 0)
        if cnt < 2:
            primary.append(opp)
            title_counts[tn] = cnt + 1
        else:
            deferred.append(opp)

    return primary + deferred

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
