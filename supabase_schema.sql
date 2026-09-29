-- Supabase PostgreSQL Schema for AIESEC Opportunity Matcher & EP Pipeline

-- 1. Users Table
CREATE TABLE IF NOT EXISTS public.users (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    "passwordHash" TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'member',
    "createdAt" TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2. Opportunities Table
CREATE TABLE IF NOT EXISTS public.opportunities (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    skills TEXT NOT NULL DEFAULT '[]',
    location TEXT NOT NULL,
    "sourceUrl" TEXT NOT NULL,
    "createdAt" TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 3. CVs Table
CREATE TABLE IF NOT EXISTS public.cvs (
    id SERIAL PRIMARY KEY,
    "userId" INTEGER NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    "filePath" TEXT NOT NULL,
    "parsedData" TEXT NOT NULL,
    "createdAt" TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 4. Matches Table
CREATE TABLE IF NOT EXISTS public.matches (
    id SERIAL PRIMARY KEY,
    "cvId" INTEGER NOT NULL REFERENCES public.cvs(id) ON DELETE CASCADE,
    "opportunityId" INTEGER REFERENCES public.opportunities(id) ON DELETE SET NULL,
    score DOUBLE PRECISION NOT NULL DEFAULT 0,
    "isFavorite" BOOLEAN NOT NULL DEFAULT false,
    "createdAt" TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 5. EP Applications Table
CREATE TABLE IF NOT EXISTS public.ep_applications (
    id SERIAL PRIMARY KEY,
    "firstName" TEXT NOT NULL,
    "lastName" TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    phone TEXT NOT NULL,
    nationality TEXT NOT NULL,
    university TEXT NOT NULL,
    "fieldOfStudy" TEXT NOT NULL,
    "opportunityTitle" TEXT NOT NULL,
    country TEXT NOT NULL,
    organization TEXT NOT NULL,
    "applicationDate" TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    "opportunityLink" TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'Applied',
    "stageIndex" INTEGER NOT NULL DEFAULT 0,
    "folderName" TEXT NOT NULL,
    "createdAt" TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    "updatedAt" TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 6. EP Documents Table
CREATE TABLE IF NOT EXISTS public.ep_documents (
    id SERIAL PRIMARY KEY,
    "epId" INTEGER NOT NULL REFERENCES public.ep_applications(id) ON DELETE CASCADE,
    "documentType" TEXT NOT NULL,
    "originalName" TEXT NOT NULL,
    "fileName" TEXT NOT NULL,
    "filePath" TEXT NOT NULL,
    "mimeType" TEXT,
    "createdAt" TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 7. EP Status History Table
CREATE TABLE IF NOT EXISTS public.ep_status_history (
    id SERIAL PRIMARY KEY,
    "epId" INTEGER NOT NULL REFERENCES public.ep_applications(id) ON DELETE CASCADE,
    status TEXT NOT NULL,
    "stageIndex" INTEGER NOT NULL DEFAULT 0,
    "changedByLabel" TEXT,
    "createdAt" TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 8. EP Notifications Table
CREATE TABLE IF NOT EXISTS public.ep_notifications (
    id SERIAL PRIMARY KEY,
    "epId" INTEGER NOT NULL REFERENCES public.ep_applications(id) ON DELETE CASCADE,
    "notificationType" TEXT NOT NULL,
    message TEXT NOT NULL,
    "isRead" BOOLEAN NOT NULL DEFAULT false,
    "createdAt" TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- Insert Demo Opportunities
INSERT INTO public.opportunities (title, description, skills, location, "sourceUrl")
VALUES 
('Global Talent - Software Engineer', 'Full-stack development position in Hamburg, Germany focusing on React, Node.js, and Cloud Infrastructure.', '["React", "TypeScript", "Node.js", "Git", "Docker"]', 'Hamburg, Germany', 'https://aiesec.org/opportunity/1001'),
('Global Volunteer - SDG Quality Education Teacher', 'Teach English and IT literacy to youth in Istanbul, Turkey. Work alongside international volunteer team.', '["English", "Teaching", "Leadership", "Communication", "IT Literacy"]', 'Istanbul, Turkey', 'https://aiesec.org/opportunity/1002'),
('Global Teacher - STEM Educator', 'Secondary school STEM teacher in São Paulo, Brazil. Plan lessons, lead workshops, and manage lab activities.', '["Mathematics", "Physics", "Computer Science", "Portuguese", "Mentorship"]', 'São Paulo, Brazil', 'https://aiesec.org/opportunity/1003'),
('Marketing & Growth Specialist', 'Digital marketing manager in Kuala Lumpur, Malaysia. Lead social media campaigns, SEO, and content creation.', '["Digital Marketing", "SEO", "Copywriting", "Analytics", "Social Media"]', 'Kuala Lumpur, Malaysia', 'https://aiesec.org/opportunity/1004'),
('Business Development Trainee', 'International sales and partnership trainee position in Bucharest, Romania.', '["Sales", "Negotiation", "CRM", "English", "Market Research"]', 'Bucharest, Romania', 'https://aiesec.org/opportunity/1005')
ON CONFLICT DO NOTHING;
