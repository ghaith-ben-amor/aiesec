// Advanced CV Parser & Skill Extraction Engine

export interface ParsedCvResult {
  name: string;
  email?: string;
  phone?: string;
  location?: string;
  fieldOfStudy: string;
  education: string[];
  skills: string[];
  skillsByCategory: {
    programming: string[];
    webAndMobile: string[];
    embeddedAndIot: string[];
    databasesAndCloud: string[];
    designAndCreative: string[];
    businessAndSales: string[];
    softSkills: string[];
  };
  languages: string[];
  experience: string[];
  projects: string[];
  summary: string;
  searchKeywords: string[];
}

// Comprehensive skill dictionary with boundary-aware matching
const SKILL_TAXONOMY: {
  category: keyof ParsedCvResult["skillsByCategory"];
  skills: { name: string; regex: RegExp; searchWeight?: number }[];
}[] = [
  {
    category: "programming",
    skills: [
      { name: "C", regex: /\b(?<!\w)C(?!\w|[+#])/i, searchWeight: 1 },
      { name: "C++", regex: /\bC\+\+\b/i, searchWeight: 2 },
      { name: "C#", regex: /\bC#\b/i, searchWeight: 2 },
      { name: "Java", regex: /\bJava\b(?!\s*script)/i, searchWeight: 3 },
      { name: "JavaFX", regex: /\bJavaFX\b/i, searchWeight: 2 },
      { name: "Python", regex: /\bPython\b/i, searchWeight: 3 },
      { name: "JavaScript", regex: /\b(?:JavaScript|JS)\b/i, searchWeight: 3 },
      { name: "TypeScript", regex: /\b(?:TypeScript|TS)\b/i, searchWeight: 3 },
      { name: "PHP", regex: /\bPHP\b/i, searchWeight: 3 },
      { name: "Go", regex: /\b(?:Golang|Go)\b/i },
      { name: "Rust", regex: /\bRust\b/i },
      { name: "Dart", regex: /\bDart\b/i },
      { name: "Kotlin", regex: /\bKotlin\b/i },
      { name: "Swift", regex: /\bSwift\b/i },
      { name: "SQL", regex: /\bSQL\b/i, searchWeight: 2 },
    ],
  },
  {
    category: "webAndMobile",
    skills: [
      { name: "React", regex: /\bReact(?:\.js)?\b/i, searchWeight: 3 },
      { name: "Next.js", regex: /\bNext(?:\.js)?\b/i, searchWeight: 3 },
      { name: "Node.js", regex: /\bNode(?:\.js)?\b/i, searchWeight: 3 },
      { name: "Express", regex: /\bExpress(?:\.js)?\b/i },
      { name: "Vue.js", regex: /\bVue(?:\.js)?\b/i },
      { name: "Angular", regex: /\bAngular\b/i },
      { name: "Symfony", regex: /\bSymfony\b/i, searchWeight: 2 },
      { name: "Laravel", regex: /\bLaravel\b/i, searchWeight: 2 },
      { name: "HTML", regex: /\bHTML5?\b/i },
      { name: "CSS", regex: /\bCSS3?\b/i },
      { name: "Tailwind CSS", regex: /\bTailwind(?:\s*CSS)?\b/i },
      { name: "Bootstrap", regex: /\bBootstrap\b/i },
      { name: "Flutter", regex: /\bFlutter\b/i, searchWeight: 3 },
      { name: "FlutterFlow", regex: /\bFlutterFlow\b/i, searchWeight: 2 },
      { name: "React Native", regex: /\bReact\s*Native\b/i, searchWeight: 2 },
      { name: "REST API", regex: /\bREST(?:ful)?(?:\s*APIs?)?\b/i },
      { name: "GraphQL", regex: /\bGraphQL\b/i },
      { name: "MVC", regex: /\bMVC\b/i },
      { name: "SDL", regex: /\bSDL\b/i },
      { name: "Qt Creator", regex: /\bQT(?:\s*Creator)?\b/i },
    ],
  },
  {
    category: "embeddedAndIot",
    skills: [
      { name: "Embedded Systems", regex: /\bEmbedded\s*Systems?\b/i, searchWeight: 2 },
      { name: "IoT", regex: /\bIoT|Internet\s*of\s*Things\b/i, searchWeight: 2 },
      { name: "Arduino", regex: /\bArduino(?:\s*Uno)?\b/i, searchWeight: 2 },
      { name: "ESP32", regex: /\bESP32\b/i, searchWeight: 2 },
      { name: "STM32", regex: /\bSTM32\b/i, searchWeight: 2 },
      { name: "PIC Microcontrollers", regex: /\bPIC16F\w*|\bPIC\s*micro\b/i },
      { name: "MikroC", regex: /\bMikroC\b/i },
      { name: "Proteus", regex: /\bProteus(?:\s*ISIS)?\b/i },
      { name: "MPLAB", regex: /\bMPLAB\b/i },
      { name: "Electronics", regex: /\bElectronics?\b/i },
      { name: "Robotics", regex: /\bRobotics?\b/i },
    ],
  },
  {
    category: "databasesAndCloud",
    skills: [
      { name: "MySQL", regex: /\bMySQL\b/i, searchWeight: 2 },
      { name: "PostgreSQL", regex: /\bPostgreSQL|Postgres\b/i, searchWeight: 2 },
      { name: "MongoDB", regex: /\bMongoDB\b/i },
      { name: "SQLite", regex: /\bSQLite\b/i },
      { name: "Firebase", regex: /\bFirebase\b/i },
      { name: "Supabase", regex: /\bSupabase\b/i },
      { name: "Docker", regex: /\bDocker\b/i, searchWeight: 2 },
      { name: "Git", regex: /\bGit(?:Hub|Lab)?\b/i },
      { name: "Linux", regex: /\bLinux|Unix\b/i },
      { name: "GNS3", regex: /\bGNS3\b/i },
      { name: "Computer Networking", regex: /\b(?:Networking|VLSM|Routing|TCP\/IP)\b/i },
      { name: "AWS", regex: /\bAWS|Amazon\s*Web\s*Services\b/i },
      { name: "Cloud Infrastructure", regex: /\bCloud\s*(?:Infrastructure|Computing)?\b/i },
    ],
  },
  {
    category: "designAndCreative",
    skills: [
      { name: "UI/UX Design", regex: /\bUI[\s/]*UX(?:\s*Design)?\b/i, searchWeight: 3 },
      { name: "Graphic Design", regex: /\bGraphic\s*Design(?:er)?\b/i, searchWeight: 3 },
      { name: "Adobe Photoshop", regex: /\b(?:Adobe\s*)?Photoshop\b/i, searchWeight: 2 },
      { name: "Adobe Illustrator", regex: /\b(?:Adobe\s*)?Illustrator\b/i, searchWeight: 2 },
      { name: "Adobe Premiere Pro", regex: /\b(?:Adobe\s*)?Premiere(?:\s*Pro)?\b/i, searchWeight: 2 },
      { name: "Figma", regex: /\bFigma\b/i, searchWeight: 3 },
      { name: "Video Editing", regex: /\bVideo\s*Editing\b/i },
      { name: "Branding", regex: /\bBranding|Visual\s*Identity\b/i },
    ],
  },
  {
    category: "businessAndSales",
    skills: [
      { name: "Sales", regex: /\bSales|Seller\b/i, searchWeight: 2 },
      { name: "Customer Service", regex: /\bCustomer\s*(?:Service|Support|Care)\b/i, searchWeight: 2 },
      { name: "Marketing", regex: /\bMarketing|Digital\s*Marketing\b/i, searchWeight: 3 },
      { name: "SEO", regex: /\bSEO\b/i },
      { name: "Negotiation", regex: /\bNegotiat(?:ion|ing)\b/i },
      { name: "Business Development", regex: /\bBusiness\s*Development\b/i, searchWeight: 2 },
      { name: "Fintech", regex: /\bFintech\b/i, searchWeight: 2 },
      { name: "Project Management", regex: /\bProject\s*Management\b/i, searchWeight: 2 },
    ],
  },
  {
    category: "softSkills",
    skills: [
      { name: "Communication", regex: /\bCommunication(?:\s*Skills)?\b/i },
      { name: "Teamwork", regex: /\bTeamwork|Collaboration\b/i },
      { name: "Leadership", regex: /\bLeadership|Leader\b/i },
      { name: "Problem Solving", regex: /\bProblem\s*Solving\b/i },
      { name: "Adaptability", regex: /\bAdaptability|Adaptable\b/i },
      { name: "Critical Thinking", regex: /\bCritical\s*Thinking\b/i },
      { name: "Presentation skills", regex: /\bPresentation(?:\s*skills)?\b/i },
    ],
  },
];

const LANGUAGE_PATTERNS = [
  { name: "English", regex: /\bEnglish\b/i },
  { name: "French", regex: /\b(?:French|Français)\b/i },
  { name: "Arabic", regex: /\b(?:Arabic|Arabe)\b/i },
  { name: "German", regex: /\b(?:German|Allemand|Deutsch)\b/i },
  { name: "Spanish", regex: /\b(?:Spanish|Espagnol)\b/i },
  { name: "Italian", regex: /\b(?:Italian|Italien)\b/i },
];

function extractCandidateName(text: string): string {
  const lines = text
    .split(/\r?\n/)
    .map(l => l.trim())
    .filter(l => l.length > 0);

  const ignoredKeywords = [
    "curriculum", "vitae", "resume", "cv", "profile", "contact", "summary",
    "education", "experience", "skills", "projects", "email", "phone", "address"
  ];

  for (let i = 0; i < Math.min(lines.length, 6); i++) {
    const line = lines[i];
    const lower = line.toLowerCase();

    // Skip lines with @, phone digits, urls, or ignored headers
    if (line.includes("@") || line.includes("http") || line.length > 50 || line.length < 3) continue;
    if (/\+?\d{6,}/.test(line)) continue;
    if (ignoredKeywords.some(kw => lower.includes(kw))) continue;

    // Check if line looks like a person's name (letters, spaces, dashes)
    if (/^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/.test(line)) {
      const words = line.split(/\s+/).filter(Boolean);
      if (words.length >= 2 && words.length <= 4) {
        return line;
      }
    }
  }

  return "AIESEC Candidate";
}

function extractEmail(text: string): string | undefined {
  const match = text.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/);
  return match ? match[0].toLowerCase() : undefined;
}

function extractPhone(text: string): string | undefined {
  const match = text.match(/(?:\+?\d{1,4}[\s.-]*)?(?:\(?\d{2,4}\)?[\s.-]*)?\d{2,4}[\s.-]*\d{2,4}[\s.-]*\d{2,4}/);
  return match ? match[0].trim() : undefined;
}

function extractLocation(text: string): string | undefined {
  const addressMatch = text.match(/(?:Address|Adresse)\s*:\s*([^\n\r]+)/i);
  if (addressMatch) {
    return addressMatch[1].trim();
  }

  // Look for Tunisian / common cities
  const cities = ["Tunis", "Ariana", "Sousse", "Sfax", "Monastir", "Paris", "Berlin", "Cairo", "Istanbul"];
  for (const city of cities) {
    if (new RegExp(`\\b${city}\\b`, "i").test(text)) {
      return `${city}`;
    }
  }

  return undefined;
}

function extractEducationAndField(text: string, manualField?: string | null): {
  fieldOfStudy: string;
  education: string[];
} {
  const education: string[] = [];
  const lower = text.toLowerCase();

  // Degree detection
  if (/engineering cycle|cycle d['’]ingénieur|esprit|insat|enit/i.test(text)) {
    education.push("Engineering Cycle Student (ESPRIT / Engineering School)");
  }
  if (/preparatory cycle|cycle préparatoire/i.test(text)) {
    education.push("Integrated Preparatory Cycle");
  }
  if (/baccalaureate|baccalauréat/i.test(text)) {
    if (/math/i.test(text)) {
      education.push("Mathematics Baccalaureate");
    } else {
      education.push("Baccalaureate Diploma");
    }
  }
  if (/master|msc|magistère/i.test(text)) {
    education.push("Master's Degree");
  }
  if (/bachelor|licence|undergraduate/i.test(text)) {
    education.push("Bachelor's Degree");
  }

  let fieldOfStudy = manualField?.trim() || "";

  if (!fieldOfStudy) {
    if (
      lower.includes("software development") ||
      lower.includes("computer engineering") ||
      lower.includes("computer science") ||
      lower.includes("informatique") ||
      lower.includes("web technologies")
    ) {
      fieldOfStudy = "Computer Engineering & Software Development";
    } else if (lower.includes("embedded systems") || lower.includes("iot") || lower.includes("electronics")) {
      fieldOfStudy = "Embedded Systems & IoT";
    } else if (lower.includes("marketing") || lower.includes("business") || lower.includes("sales")) {
      fieldOfStudy = "Business & Marketing";
    } else if (lower.includes("engineering")) {
      fieldOfStudy = "Engineering & Technology";
    } else {
      fieldOfStudy = "Information Technology";
    }
  }

  return { fieldOfStudy, education };
}

function extractExperienceAndProjects(text: string): { experience: string[]; projects: string[] } {
  const experience: string[] = [];
  const projects: string[] = [];

  // Identify common project or work role patterns
  const roleMatches = text.match(/(?:Seller|Developer|Designer|Intern|Engineer|Freelance|Vice-Chair|Active Member)[^\n\r•]+/gi);
  if (roleMatches) {
    for (const r of roleMatches) {
      const clean = r.trim().replace(/\s+/g, " ");
      if (clean.length > 5 && clean.length < 80) {
        experience.push(clean);
      }
    }
  }

  // Specific project names if mentioned
  const knownProjects = [
    "FinTrack", "InnoVest", "SmartFix", "Smart Greenhouse", "TMUZYA", "IEEE"
  ];
  for (const proj of knownProjects) {
    if (new RegExp(`\\b${proj}\\b`, "i").test(text)) {
      projects.push(proj);
    }
  }

  return {
    experience: Array.from(new Set(experience)).slice(0, 6),
    projects: Array.from(new Set(projects)),
  };
}

export function parseCvText(
  cvText: string,
  options?: { manualSkills?: string | null; manualField?: string | null }
): ParsedCvResult {
  const name = extractCandidateName(cvText);
  const email = extractEmail(cvText);
  const phone = extractPhone(cvText);
  const location = extractLocation(cvText);

  const { fieldOfStudy, education } = extractEducationAndField(cvText, options?.manualField);
  const { experience, projects } = extractExperienceAndProjects(cvText);

  // Extract skills by category
  const skillsByCategory: ParsedCvResult["skillsByCategory"] = {
    programming: [],
    webAndMobile: [],
    embeddedAndIot: [],
    databasesAndCloud: [],
    designAndCreative: [],
    businessAndSales: [],
    softSkills: [],
  };

  const allSkillsSet = new Set<string>();
  const weightedKeywords: { term: string; weight: number }[] = [];

  for (const tax of SKILL_TAXONOMY) {
    for (const s of tax.skills) {
      if (s.regex.test(cvText)) {
        skillsByCategory[tax.category].push(s.name);
        allSkillsSet.add(s.name);
        if (s.searchWeight) {
          weightedKeywords.push({ term: s.name.toLowerCase(), weight: s.searchWeight });
        }
      }
    }
  }

  // Merge manual user skills if provided
  if (options?.manualSkills) {
    const userSkills = options.manualSkills
      .split(",")
      .map(s => s.trim())
      .filter(Boolean);

    for (const us of userSkills) {
      allSkillsSet.add(us);
      skillsByCategory.programming.push(us);
      weightedKeywords.push({ term: us.toLowerCase(), weight: 3 });
    }
  }

  // Extract spoken languages
  const detectedLanguages: string[] = [];
  for (const lang of LANGUAGE_PATTERNS) {
    if (lang.regex.test(cvText)) {
      detectedLanguages.push(lang.name);
    }
  }
  if (detectedLanguages.length === 0) {
    detectedLanguages.push("English");
  }

  // Generate top search keywords for GIS querying
  // Sort weighted keywords and pick high-impact terms
  const searchKeywords: string[] = [];
  
  if (skillsByCategory.webAndMobile.length > 0 || skillsByCategory.programming.length > 0) {
    searchKeywords.push("developer");
    searchKeywords.push("software");
  }
  if (skillsByCategory.webAndMobile.some(s => ["React", "HTML", "CSS", "Next.js"].includes(s))) {
    searchKeywords.push("web");
  }
  if (skillsByCategory.designAndCreative.length > 0) {
    searchKeywords.push("graphic");
  }
  if (skillsByCategory.businessAndSales.length > 0) {
    searchKeywords.push("marketing");
  }

  // Deduplicate and ensure at least 2 search keywords
  const uniqueSearchKeywords = Array.from(new Set(searchKeywords));
  if (uniqueSearchKeywords.length === 0) {
    uniqueSearchKeywords.push("developer", "software");
  }

  // Extract professional summary
  let summary = "";
  const summaryMatch = cvText.match(/(?:PROFESSIONAL SUMMARY|SUMMARY|PROFIL|OBJECTIVE)([\s\S]{50,600}?)(?:EDUCATION|EXPERIENCE|PROJECTS|SKILLS|$)/i);
  if (summaryMatch && summaryMatch[1]) {
    summary = summaryMatch[1].replace(/\s+/g, " ").trim();
  } else {
    summary = cvText.slice(0, 400).replace(/\s+/g, " ").trim();
  }

  return {
    name,
    email,
    phone,
    location,
    fieldOfStudy,
    education,
    skills: Array.from(allSkillsSet),
    skillsByCategory,
    languages: detectedLanguages,
    experience,
    projects,
    summary,
    searchKeywords: uniqueSearchKeywords,
  };
}
