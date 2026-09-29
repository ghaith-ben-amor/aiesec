import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";

export async function GET(req: Request) {
  try {
    const { searchParams } = new URL(req.url);
    const search = searchParams.get("search") || "";
    const status = searchParams.get("status") || "";

    const where: any = {};
    if (search) {
      where.OR = [
        { firstName: { contains: search } },
        { lastName: { contains: search } },
        { email: { contains: search } },
        { university: { contains: search } },
        { opportunityTitle: { contains: search } },
      ];
    }
    if (status) {
      where.status = status;
    }

    const eps = await prisma.epApplication.findMany({
      where,
      include: {
        documents: true,
        statusHistory: { orderBy: { createdAt: "desc" } },
        notifications: true,
      },
      orderBy: { createdAt: "desc" },
    });

    return NextResponse.json({ eps });
  } catch (error: any) {
    return NextResponse.json({ error: error?.message || "Failed to fetch EP records" }, { status: 500 });
  }
}

export async function POST(req: Request) {
  try {
    const body = await req.json();
    const {
      firstName,
      lastName,
      email,
      phone,
      nationality,
      university,
      fieldOfStudy,
      opportunityTitle,
      country,
      organization,
      opportunityLink,
    } = body;

    if (!firstName || !lastName || !email) {
      return NextResponse.json({ error: "First name, last name, and email are required" }, { status: 400 });
    }

    const folderName = `ep_${email.replace(/[^a-zA-Z0-9]/g, "_")}_${Date.now()}`;

    const ep = await prisma.epApplication.create({
      data: {
        firstName,
        lastName,
        email: email.toLowerCase(),
        phone: phone || "",
        nationality: nationality || "Tunisia",
        university: university || "AIESEC LC",
        fieldOfStudy: fieldOfStudy || "General",
        opportunityTitle: opportunityTitle || "Global Talent Exchange",
        country: country || "Germany",
        organization: organization || "AIESEC Partner",
        opportunityLink: opportunityLink || "https://aiesec.org",
        status: "Applied",
        stageIndex: 0,
        folderName,
        statusHistory: {
          create: {
            status: "Applied",
            stageIndex: 0,
            changedByLabel: "System Admin",
          },
        },
      },
    });

    return NextResponse.json({ success: true, ep });
  } catch (error: any) {
    return NextResponse.json({ error: error?.message || "EP registration failed" }, { status: 500 });
  }
}
