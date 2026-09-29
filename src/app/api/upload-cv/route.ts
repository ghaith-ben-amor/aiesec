import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getSession } from "@/lib/auth";
import { matchCvWithOpportunities, ParsedCv } from "@/lib/matcher";
import pdfParse from "pdf-parse";

// Default fallback opportunities if database table is empty
const DEMO_OPPORTUNITIES = [
  {
    id: 1,
    title: "Global Talent - Software Engineer",
    description: "Full-stack development position in Hamburg, Germany focusing on React, Node.js, and Cloud Infrastructure.",
    skills: ["React", "TypeScript", "Node.js", "Git", "Docker"],
    location: "Hamburg, Germany",
    sourceUrl: "https://aiesec.org/opportunity/1001",
  },
  {
    id: 2,
    title: "Global Volunteer - SDG Quality Education Teacher",
    description: "Teach English and IT literacy to youth in Istanbul, Turkey. Work alongside international volunteer team.",
    skills: ["English", "Teaching", "Leadership", "Communication", "IT Literacy"],
    location: "Istanbul, Turkey",
    sourceUrl: "https://aiesec.org/opportunity/1002",
  },
  {
    id: 3,
    title: "Global Teacher - STEM Educator",
    description: "Secondary school STEM teacher in São Paulo, Brazil. Plan lessons, lead workshops, and manage lab activities.",
    skills: ["Mathematics", "Physics", "Computer Science", "Portuguese", "Mentorship"],
    location: "São Paulo, Brazil",
    sourceUrl: "https://aiesec.org/opportunity/1003",
  },
  {
    id: 4,
    title: "Marketing & Growth Specialist",
    description: "Digital marketing manager in Kuala Lumpur, Malaysia. Lead social media campaigns, SEO, and content creation.",
    skills: ["Digital Marketing", "SEO", "Copywriting", "Analytics", "Social Media"],
    location: "Kuala Lumpur, Malaysia",
    sourceUrl: "https://aiesec.org/opportunity/1004",
  },
  {
    id: 5,
    title: "Business Development Trainee",
    description: "International sales and partnership trainee position in Bucharest, Romania.",
    skills: ["Sales", "Negotiation", "CRM", "English", "Market Research"],
    location: "Bucharest, Romania",
    sourceUrl: "https://aiesec.org/opportunity/1005",
  }
];

export async function POST(req: Request) {
  try {
    const session = await getSession();
    const formData = await req.formData();
    const file = formData.get("file") as File | null;
    const manualSkillsStr = formData.get("skills") as string | null;
    const manualField = formData.get("fieldOfStudy") as string | null;

    let cvText = "";
    let parsedCv: ParsedCv = {
      skills: [],
      fieldOfStudy: manualField || "",
    };

    if (file && file.type === "application/pdf") {
      const arrayBuffer = await file.arrayBuffer();
      const buffer = Buffer.from(arrayBuffer);
      const pdfData = await pdfParse(buffer);
      cvText = pdfData.text || "";

      // Simple keyword extractor for skills & field of study
      const lower = cvText.toLowerCase();
      const commonSkills = [
        "react", "typescript", "javascript", "node.js", "python", "java", "c++", "sql", "git",
        "docker", "english", "french", "spanish", "teaching", "communication", "marketing",
        "seo", "sales", "leadership", "management", "excel", "ui/ux", "design", "css", "html"
      ];

      const extractedSkills = commonSkills.filter(s => lower.includes(s));
      parsedCv.skills = Array.from(new Set([...extractedSkills]));
      parsedCv.summary = cvText.slice(0, 500);
    }

    if (manualSkillsStr) {
      const userSkills = manualSkillsStr.split(",").map(s => s.trim()).filter(Boolean);
      parsedCv.skills = Array.from(new Set([...parsedCv.skills, ...userSkills]));
    }

    if (parsedCv.skills.length === 0) {
      parsedCv.skills = ["general", "communication", "leadership"];
    }

    // Load opportunities from DB or fallback
    let opportunities = await prisma.opportunity.findMany();
    let oppList = opportunities.map(o => ({
      id: o.id,
      title: o.title,
      description: o.description,
      skills: JSON.parse(o.skills || "[]"),
      location: o.location,
      sourceUrl: o.sourceUrl,
    }));

    if (oppList.length === 0) {
      oppList = DEMO_OPPORTUNITIES;
    }

    const matchResults = await matchCvWithOpportunities(cvText, parsedCv, oppList);

    // Save to database if user is logged in
    let cvId = null;
    if (session?.id) {
      const newCv = await prisma.cv.create({
        data: {
          userId: session.id,
          filePath: file ? file.name : "manual_entry",
          parsedData: JSON.stringify(parsedCv),
        },
      });
      cvId = newCv.id;

      for (const m of matchResults.slice(0, 10)) {
        await prisma.match.create({
          data: {
            cvId: newCv.id,
            opportunityId: m.opportunity.id <= 5 && opportunities.length === 0 ? null : m.opportunity.id,
            score: m.score,
          },
        });
      }
    }

    return NextResponse.json({
      success: true,
      parsedCv,
      matches: matchResults,
      cvId,
    });
  } catch (error: any) {
    return NextResponse.json({ error: error?.message || "CV upload & matching failed" }, { status: 500 });
  }
}
