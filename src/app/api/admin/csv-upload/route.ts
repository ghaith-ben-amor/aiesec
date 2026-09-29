import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getSession } from "@/lib/auth";

export async function POST(req: Request) {
  try {
    const session = await getSession();
    if (session?.role !== "admin") {
      return NextResponse.json({ error: "Unauthorized. Admin privileges required." }, { status: 403 });
    }

    const formData = await req.formData();
    const file = formData.get("file") as File | null;

    if (!file) {
      return NextResponse.json({ error: "No CSV file provided" }, { status: 400 });
    }

    const csvText = await file.text();
    const lines = csvText.split(/\r?\n/).filter(line => line.trim().length > 0);

    if (lines.length <= 1) {
      return NextResponse.json({ error: "CSV file is empty or missing headers" }, { status: 400 });
    }

    // Header line: title,description,skills,location,sourceUrl
    const newOpportunities = [];

    for (let i = 1; i < lines.length; i++) {
      const line = lines[i];
      // simple CSV parsing handling quotes
      const cols = line.split(/,(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/).map(c => c.replace(/^"|"$/g, "").trim());

      if (cols.length >= 3) {
        const title = cols[0] || "AIESEC Opportunity";
        const description = cols[1] || "Global exchange position";
        const skillsStr = cols[2] || "General";
        const location = cols[3] || "International";
        const sourceUrl = cols[4] || "https://aiesec.org";

        const skillsArr = skillsStr.split(/;|\||,/).map(s => s.trim()).filter(Boolean);

        newOpportunities.push({
          title,
          description,
          skills: JSON.stringify(skillsArr),
          location,
          sourceUrl,
        });
      }
    }

    if (newOpportunities.length > 0) {
      // Clear old opportunities and insert new ones
      await prisma.opportunity.deleteMany();
      await prisma.opportunity.createMany({
        data: newOpportunities,
      });
    }

    return NextResponse.json({
      success: true,
      count: newOpportunities.length,
    });
  } catch (error: any) {
    return NextResponse.json({ error: error?.message || "CSV processing failed" }, { status: 500 });
  }
}
