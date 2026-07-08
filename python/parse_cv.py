import json
import os
import re
import sys
from pathlib import Path

try:
    from dotenv import load_dotenv
    # Load .env from parent directories (up to the project root)
    env_path = Path(__file__).parent.parent / '.env'
    if env_path.exists():
        load_dotenv(env_path)
except Exception:
    pass

try:
    import fitz  # PyMuPDF
except Exception:
    fitz = None

try:
    import pdfplumber
except Exception:
    pdfplumber = None

try:
    import requests
except Exception:
    requests = None

SKILL_KEYWORDS = [
    "marketing", "sales", "leadership", "communication", "teamwork", "project management",
    "python", "php", "javascript", "html", "css", "sql", "data analysis", "research",
    "content creation", "design", "public speaking", "problem solving", "excel", "ai", "nlp",
    "java", "c", "c++", "flutter", "flutterflow", "mvc", "qt", "arduino", "stm32", "photoshop", "illustrator",
    "machine learning", "deep learning", "web development", "mobile development", "software development",
    "ui/ux", "ui ux", "user interface", "user experience", "figma", "canva", "bootstrap", "tailwind",
    "react", "vue", "angular", "node", "node.js", "django", "flask", "laravel", "symfony",
    "git", "github", "linux", "word", "powerpoint", "microsoft office", "power bi", "tableau",
    "autocad", "matlab", "kotlin", "swift", "typescript", "typescript", "seo", "social media",
    "presentation", "copywriting", "translation", "customer service", "problem-solving", "critical thinking"
]

LANGUAGE_KEYWORDS = ["english", "french", "spanish", "german", "arabic", "portuguese", "italian", "hindi"]
EDUCATION_KEYWORDS = [
    "bachelor", "master", "mba", "phd", "bsc", "msc", "license", "licence",
    "university", "college", "school", "institute", "degree", "diploma"
]

SECTION_STOP_WORDS = [
    "skills", "technical skills", "experience", "professional experience", "work experience",
    "projects", "academic", "community", "certifications", "languages", "summary", "profile"
]

SKILL_SECTION_MARKERS = [
    "skills",
    "technical skills",
    "key skills",
    "core skills",
    "core competencies",
    "competencies",
    "technologies",
    "tools",
    "abilities",
    "expertise",
]


def debug_log(msg):
    if os.getenv("APP_DEBUG") or os.getenv("PY_DEBUG"):
        print(f"[DEBUG] {msg}", file=sys.stderr)


def extract_text(pdf_path: str) -> str:
    debug_log(f"extract_text: {pdf_path}")
    
    if fitz is not None:
        try:
            debug_log("trying PyMuPDF")
            document = fitz.open(pdf_path)
            text = []
            for page in document:
                text.append(page.get_text())
            combined = "\n".join(text).strip()
            if combined:
                debug_log(f"PyMuPDF success: {len(combined)} chars")
                return combined
        except Exception as e:
            debug_log(f"PyMuPDF error: {e}")
            pass

    if pdfplumber is not None:
        try:
            debug_log("trying pdfplumber")
            lines = []
            with pdfplumber.open(pdf_path) as pdf:
                for page in pdf.pages:
                    extracted = page.extract_text() or ""
                    if extracted:
                        lines.append(extracted)
            result = "\n".join(lines).strip()
            if result:
                debug_log(f"pdfplumber success: {len(result)} chars")
                return result
        except Exception as e:
            debug_log(f"pdfplumber error: {e}")
            pass

    debug_log("no text extracted")
    return ""


def keyword_hits(text: str, keywords):
    lowered = text.lower()
    hits = []
    for keyword in sorted(keywords, key=len, reverse=True):
        pattern = re.escape(keyword.lower())
        if re.search(rf"(?<![a-z0-9]){pattern}(?![a-z0-9])", lowered):
            hits.append(keyword)
    return hits


def normalize_skill_text(value: str) -> str:
    clean = " ".join(str(value).replace("•", " ").replace("·", " ").split())
    clean = clean.strip(" -–—:;,.|/\\")
    lowered = clean.lower()

    alias = {
        "js": "javascript",
        "nodejs": "javascript",
        "node.js": "javascript",
        "mysql": "sql",
        "postgresql": "sql",
        "postgres": "sql",
        "database": "sql",
        "ui": "design",
        "ux": "design",
        "figma": "design",
        "content": "content creation",
        "writing": "content creation",
        "communicat": "communication",
        "collaboration": "teamwork",
        "team work": "teamwork",
        "ui/ux": "ui ux",
        "ui/ux design": "ui ux",
        "ux design": "design",
        "ui design": "design",
        "user interface": "ui ux",
        "user experience": "ui ux",
        "problem-solving": "problem solving",
        "microsoft office": "microsoft office",
    }

    return alias.get(lowered, lowered)


def is_skill_candidate(value: str) -> bool:
    clean = " ".join(str(value).split()).strip(" -–—:;,.|/\\")
    lowered = clean.lower()

    if not clean or not any(ch.isalpha() for ch in clean):
        return False
    if len(clean) > 60:
        return False
    if any(stop in lowered for stop in [
        "experience", "education", "project", "summary", "profile", "certification", "achievement",
        "responsible", "responsibility", "internship", "volunteer work",
    ]):
        return False
    if len(clean.split()) > 6:
        return False
    return True


def extract_skill_candidates(blob: str):
    candidates = []
    for token in re.split(r"(?:\n+|[,;|]+|[•·▪●]+)", blob):
        clean = token.strip()
        if " and " in clean.lower() and len(clean.split()) <= 6:
            for part in re.split(r"\s+and\s+", clean, flags=re.IGNORECASE):
                part = part.strip()
                if is_skill_candidate(part):
                    candidates.append(normalize_skill_text(part))
            continue
        if not is_skill_candidate(clean):
            continue
        candidates.append(normalize_skill_text(clean))
    return candidates


def dedupe_keep_order(items):
    seen = set()
    out = []
    for item in items:
        clean = " ".join(str(item).strip().split())
        if not clean:
            continue
        key = clean.lower()
        if key in seen:
            continue
        seen.add(key)
        out.append(clean)
    return out


def normalize_lines(text: str):
    return [" ".join(line.strip().split()) for line in text.splitlines() if line.strip()]


def extract_section_lines(text: str, section_markers):
    lines = normalize_lines(text)
    collected = []
    in_section = False

    for line in lines:
        lowered = line.lower()
        if any(re.fullmatch(rf"{re.escape(marker)}[:\- ]*", lowered) for marker in section_markers):
            in_section = True
            continue

        if in_section and any(stop == lowered or lowered.startswith(stop + ":") or lowered.startswith(stop + "-") for stop in SECTION_STOP_WORDS):
            break

        if in_section:
            collected.append(line)

    return collected


def extract_skills(text: str):
    hits = keyword_hits(text, SKILL_KEYWORDS)
    section_lines = extract_section_lines(text, SKILL_SECTION_MARKERS)
    section_blob = "\n".join(section_lines)

    candidates = []
    candidates.extend(extract_skill_candidates(section_blob))

    if not candidates:
        lines = normalize_lines(text)
        for line in lines:
            lowered = line.lower()
            if any(stop in lowered for stop in SECTION_STOP_WORDS):
                continue
            if re.match(r"^\s*(?:[-*•·]|\d+[.)])\s*", line) or len(line.split()) <= 6:
                candidates.extend(extract_skill_candidates(line))

    merged = hits + candidates
    filtered = []
    for skill in merged:
        skill = normalize_skill_text(skill)
        if not skill:
            continue
        if skill.startswith("software development"):
            continue
        if skill.startswith("adobe & creative tools"):
            continue
        if skill.startswith("embedded systems"):
            continue
        if skill in {"skills", "technical skills", "technologies", "tools"}:
            continue
        filtered.append(skill)

    return dedupe_keep_order(filtered)[:30]


def extract_languages(text: str):
    langs = keyword_hits(text, LANGUAGE_KEYWORDS)
    section_lines = extract_section_lines(text, ["languages", "language"])
    section_blob = " ".join(section_lines).lower()

    alias = {
        "frenc": "french",
        "anglais": "english",
        "arab": "arabic",
    }

    for token in re.split(r"[,;/| ]+", section_blob):
        t = token.strip().lower()
        if not t:
            continue
        t = alias.get(t, t)
        if t in LANGUAGE_KEYWORDS:
            langs.append(t)

    return dedupe_keep_order(langs)[:8]


def extract_experience(text: str):
    years = sorted(set(re.findall(r"(\d+)\+?\s+years?", text.lower())))
    roles = []
    exp_section = extract_section_lines(text, ["professional experience", "work experience", "experience"])
    source_lines = exp_section if exp_section else text.splitlines()

    for line in source_lines:
        clean = line.strip()
        lowered = clean.lower()
        if "passionate about" in lowered or "professional summary" in lowered:
            continue
        if "student branch" in lowered or "certification" in lowered:
            continue
        if len(clean) > 110 and "." in clean:
            continue
        if 2 < len(clean) < 160 and any(token in lowered for token in ["intern", "manager", "developer", "volunteer", "leader", "analyst", "designer", "engineer"]):
            roles.append(clean)
    return {"years": years, "roles": dedupe_keep_order(roles)[:8]}


def extract_education(text: str):
    education = []
    edu_section = extract_section_lines(text, ["education", "academic background"])
    source_lines = edu_section if edu_section else text.splitlines()

    for line in source_lines:
        clean = " ".join(line.strip().split())
        lowered = clean.lower()
        if not clean or len(clean) < 4:
            continue
        if "project" in lowered or "technologies:" in lowered:
            continue
        if any(keyword in lowered for keyword in EDUCATION_KEYWORDS) or re.search(r"\b(19|20)\d{2}\b", clean):
            education.append(clean)

    if not education and edu_section:
        # Keep first entries from explicit education section even without classic keywords.
        for line in edu_section[:4]:
            lowered = line.lower()
            if "project" in lowered or "technologies:" in lowered:
                continue
            education.append(line)

    return dedupe_keep_order(education)[:8]


def build_summary(skills, languages, education, experience):
    parts = []
    if skills:
        parts.append(f"{len(skills)} skills detected")
    if languages:
        parts.append(f"{len(languages)} languages detected")
    if education:
        parts.append(f"{len(education)} education entries")
    if experience.get("roles"):
        parts.append(f"{len(experience.get('roles', []))} experience roles")

    if not parts:
        return "No structured profile data could be extracted from the PDF text."
    return "CV analysis completed: " + ", ".join(parts) + "."


def parse_json_from_response(content: str):
    try:
        return json.loads(content)
    except Exception:
        pass

    match = re.search(r"\{.*\}", content, re.DOTALL)
    if match:
        try:
            return json.loads(match.group(0))
        except Exception:
            return {}
    return {}


def groq_extract_profile(raw_text: str):
    api_key = os.getenv("GROQ_API_KEY", "").strip()
    debug_log(f"groq_extract: api_key={'YES' if api_key else 'NO'}, text_len={len(raw_text)}")
    
    if not api_key:
        debug_log("no api_key")
        return None
    if requests is None:
        debug_log("requests not available")
        return None

    try:
        debug_log("calling groq api")
        response = requests.post(
            "https://api.groq.com/openai/v1/chat/completions",
            headers={
                "Authorization": f"Bearer {api_key}",
                "Content-Type": "application/json",
            },
            json={
                "model": os.getenv("GROQ_MODEL", "llama3-8b-8192"),
                "messages": [
                    {
                        "role": "system",
                        "content": (
                            "You are a CV parser. Extract information from a CV and return ONLY valid JSON. "
                            "No markdown, no code blocks, no extra text. Only JSON. "
                            'Return: {"skills": [...], "languages": [...], "education": [...], '
                            '"experience": {"years": [...], "roles": [...]}, "summary": "...", "suggestions": [...]}'
                        ),
                    },
                    {
                        "role": "user",
                        "content": (
                            "Extract ALL information from this CV. Return JSON only:\n\n"
                            f"{raw_text[:16000]}\n\n"
                            "Return JSON with skills, languages, education, experience (years and roles), summary, suggestions."
                        ),
                    },
                ],
                "temperature": 0.2,
                "max_tokens": 2000,
            },
            timeout=30,
        )
        debug_log(f"response status: {response.status_code}")
        response.raise_for_status()
        data = response.json()
        
        if "error" in data:
            debug_log(f"api error: {data.get('error')}")
            return None
        
        content = data["choices"][0]["message"]["content"]
        debug_log(f"response content length: {len(content)}")
        
        parsed = parse_json_from_response(content)
        if parsed and isinstance(parsed, dict) and len(parsed.get("skills", [])) > 0:
            debug_log(f"success: parsed {len(parsed.get('skills', []))} skills")
            return parsed
        else:
            debug_log(f"parse failed or no skills")
            return None
    except Exception as e:
        debug_log(f"exception: {type(e).__name__}: {str(e)}")
        return None



def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "PDF path required"}))
        return

    debug_log(f"starting main")
    pdf_path = Path(sys.argv[1])
    raw_text = extract_text(str(pdf_path))
    debug_log(f"raw_text length: {len(raw_text)}")

    if len(raw_text.strip()) < 100:
        debug_log("text too short, skipping groq")
    else:
        groq_profile = groq_extract_profile(raw_text)
        if groq_profile:
            debug_log("returning groq result")
            print(json.dumps(groq_profile, ensure_ascii=True))
            return
    
    debug_log("using heuristics")
    skills = extract_skills(raw_text)
    languages = extract_languages(raw_text)
    education = extract_education(raw_text)
    experience = extract_experience(raw_text)

    heuristic_profile = {
        "raw_text": raw_text,
        "skills": skills,
        "languages": languages,
        "education": education,
        "experience": experience,
        "summary": build_summary(skills, languages, education, experience),
        "suggestions": [
            "Add measurable achievements to the CV.",
            "Highlight technical and interpersonal skills more clearly.",
        ],
    }

    if not heuristic_profile.get("skills") and not heuristic_profile.get("languages") and not heuristic_profile.get("education"):
        heuristic_profile["summary"] = "No structured profile data could be extracted from the PDF text."
        heuristic_profile["suggestions"] = [
            "Make sure the PDF contains selectable text, not only scanned images.",
            "Try a CV with clear headings like Skills, Education, Languages, and Experience.",
        ]

    print(json.dumps(heuristic_profile, ensure_ascii=True))
if __name__ == "__main__":
    main()
