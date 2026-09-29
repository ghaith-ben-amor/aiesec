import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";

const STAGES = [
  "Applied",
  "Accepted",
  "Payment",
  "Confirmed",
  "Preparation Survey",
  "Midway Survey",
  "Experience Survey",
  "Completed",
];

export async function GET(req: Request, { params }: { params: { id: string } }) {
  try {
    const id = parseInt(params.id);
    const ep = await prisma.epApplication.findUnique({
      where: { id },
      include: {
        documents: true,
        statusHistory: { orderBy: { createdAt: "desc" } },
        notifications: true,
      },
    });

    if (!ep) {
      return NextResponse.json({ error: "EP record not found" }, { status: 404 });
    }

    return NextResponse.json({ ep });
  } catch (error: any) {
    return NextResponse.json({ error: error?.message || "Failed to fetch EP" }, { status: 500 });
  }
}

export async function PUT(req: Request, { params }: { params: { id: string } }) {
  try {
    const id = parseInt(params.id);
    const body = await req.json();
    const { status, stageIndex } = body;

    const newStageIndex = stageIndex !== undefined ? stageIndex : STAGES.indexOf(status);

    const ep = await prisma.epApplication.update({
      where: { id },
      data: {
        status,
        stageIndex: Math.max(0, newStageIndex),
        statusHistory: {
          create: {
            status,
            stageIndex: Math.max(0, newStageIndex),
            changedByLabel: "Manager",
          },
        },
      },
    });

    return NextResponse.json({ success: true, ep });
  } catch (error: any) {
    return NextResponse.json({ error: error?.message || "Failed to update EP status" }, { status: 500 });
  }
}

export async function DELETE(req: Request, { params }: { params: { id: string } }) {
  try {
    const id = parseInt(params.id);
    await prisma.epApplication.delete({ where: { id } });
    return NextResponse.json({ success: true });
  } catch (error: any) {
    return NextResponse.json({ error: error?.message || "Failed to delete EP" }, { status: 500 });
  }
}
