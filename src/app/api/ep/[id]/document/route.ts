import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";

export async function POST(req: Request, { params }: { params: { id: string } }) {
  try {
    const id = parseInt(params.id);
    const formData = await req.formData();
    const file = formData.get("file") as File | null;
    const documentType = (formData.get("documentType") as string) || "General Document";

    if (!file) {
      return NextResponse.json({ error: "No file provided" }, { status: 400 });
    }

    const doc = await prisma.epDocument.create({
      data: {
        epId: id,
        documentType,
        originalName: file.name,
        fileName: `${Date.now()}_${file.name}`,
        filePath: `uploads/ep_${id}/${file.name}`,
        mimeType: file.type,
      },
    });

    return NextResponse.json({ success: true, document: doc });
  } catch (error: any) {
    return NextResponse.json({ error: error?.message || "Failed to upload document" }, { status: 500 });
  }
}
