#!/usr/bin/env python
"""Test script to diagnose CV parsing issues"""

import os
import sys
import json

print("=== CV Parsing Diagnostic ===\n")

# Check imports
print("1. Checking Python library availability:")
try:
    import fitz
    print("  ✓ PyMuPDF (fitz) available")
except ImportError as e:
    print(f"  ✗ PyMuPDF (fitz) NOT available: {e}")

try:
    import pdfplumber
    print("  ✓ pdfplumber available")
except ImportError as e:
    print(f"  ✗ pdfplumber NOT available: {e}")

try:
    import requests
    print("  ✓ requests available")
except ImportError as e:
    print(f"  ✗ requests NOT available: {e}")

# Check environment
print("\n2. Checking environment:")
groq_key = os.getenv("GROQ_API_KEY", "").strip()
print(f"  GROQ_API_KEY: {'SET' if groq_key else 'NOT SET'}")
groq_model = os.getenv("GROQ_MODEL", "llama3-8b-8192")
print(f"  GROQ_MODEL: {groq_model}")

# Try loading parse_cv module
print("\n3. Testing parse_cv module:")
try:
    sys.path.insert(0, os.path.dirname(__file__))
    import parse_cv
    print("  ✓ parse_cv module loaded")
    
    # Check functions
    print("  ✓ extract_text available")
    print("  ✓ groq_extract_profile available")
    print("  ✓ keyword_hits available")
except Exception as e:
    print(f"  ✗ Failed to load parse_cv: {e}")
    sys.exit(1)

# Look for uploaded CVs
print("\n4. Looking for uploaded CVs:")
uploads_dir = os.path.join(os.path.dirname(__file__), '..', 'uploads')
if os.path.exists(uploads_dir):
    pdfs = [f for f in os.listdir(uploads_dir) if f.endswith('.pdf')]
    if pdfs:
        print(f"  Found {len(pdfs)} PDF(s):")
        for pdf in pdfs[-3:]:  # Show last 3
            pdf_path = os.path.join(uploads_dir, pdf)
            size = os.path.getsize(pdf_path)
            print(f"    - {pdf} ({size} bytes)")
            
            # Try to extract text
            print(f"      Extracting text...")
            text = parse_cv.extract_text(pdf_path)
            if text:
                print(f"      ✓ Extracted {len(text)} characters")
                print(f"      Preview: {text[:100]}...")
            else:
                print(f"      ✗ No text extracted")
    else:
        print("  No PDF files found")
else:
    print(f"  uploads directory not found: {uploads_dir}")

print("\n=== End of Diagnostic ===")
