import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getSession } from "@/lib/auth";
import { parseCvText } from "@/lib/cv-parser";
import { fetchRealAiesecOpportunities } from "@/lib/aiesec-api";
import { matchCvWithOpportunities } from "@/lib/matcher";
import pdfParse from "pdf-parse";

export async function POST(req: Request) {
  try {
    const session = await getSession().catch(() => null);
    const formData = await req.formData();
    const file = formData.get("file") as File | null;
    const manualSkillsStr = formData.get("skills") as string | null;
    const manualField = formData.get("fieldOfStudy") as string | null;
    const customToken = formData.get("token") as string | null;

    let cvText = "";

    // 1. Extract raw text from PDF
    if (file && file.type === "application/pdf") {
      try {
        const arrayBuffer = await file.arrayBuffer();
        const buffer = Buffer.from(arrayBuffer);
        const pdfData = await pdfParse(buffer);
        cvText = pdfData.text || "";
      } catch (pdfErr: any) {
        console.warn("PDF extraction warning:", pdfErr?.message);
      }
    }

    // 2. High-precision CV Analysis
    const parsedCv = parseCvText(cvText, {
      manualSkills: manualSkillsStr,
      manualField,
    });

    // 3. Fetch REAL opportunities from AIESEC GIS API using the live token
    const realOpportunities = await fetchRealAiesecOpportunities({
      keywords: parsedCv.searchKeywords,
      fieldOfStudy: parsedCv.fieldOfStudy,
      limit: 60,
      customToken: customToken || undefined,
    });

    // 4. Intelligent AI & Heuristic Matching Engine
    const matchResults = await matchCvWithOpportunities(
      cvText,
      parsedCv,
      realOpportunities
    );

    // 5. Safe Database Persistence (non-blocking if local DB is offline)
    let cvId: number | null = null;
    if (session?.id) {
      try {
        const newCv = await prisma.cv.create({
          data: {
            userId: session.id,
            filePath: file ? file.name : "manual_entry",
            parsedData: JSON.stringify(parsedCv),
          },
        });
        cvId = newCv.id;

        // Optionally persist top matches
        for (const m of matchResults.slice(0, 10)) {
          await prisma.match.create({
            data: {
              cvId: newCv.id,
              opportunityId: null, // real GIS IDs are external
              score: m.score,
            },
          }).catch(() => null);
        }
      } catch (dbErr: any) {
        console.warn("Database persistence skipped (DB offline or unconfigured):", dbErr?.message);
      }
    }

    return NextResponse.json({
      success: true,
      parsedCv,
      matches: matchResults,
      totalOpportunitiesCount: realOpportunities.length,
      cvId,
    });
  } catch (error: any) {
    console.error("CV upload and matching error:", error);
    return NextResponse.json(
      { error: error?.message || "CV upload & matching failed" },
      { status: 500 }
    );
  }
}
