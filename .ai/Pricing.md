# College School Management System
## Packages, Pricing & Support

**All prices are quoted in Ghana Cedis (GHS).**

This document outlines the dual pricing structures implemented within the system:
1. **Public Landing Page Quote Calculator** (used by prospective schools to generate quote receipts and Proforma PDFs).
2. **Internal Admin Settings License Calculator** (used in the administrative panel to calculate subscription fees based on active students and selected modules).

---

## 1. Public Landing Page Quote Calculator
This pricing structure is used by the frontend quote calculator at the landing page (implemented via `App\Services\QuoteCalculationService` and gated/processed in `LandingController`).

### Core Academic Licence (Upfront & Renewal)
The core licence covers baseline academic features (Academic Structure, Students, Grading, Student/Teacher portals). Fees depend on the estimated student band:

| Student Band | Upfront Core Fee (GHS) | Annual Renewal Fee (GHS) | Module Multiplier |
|--------------|-------------------------|--------------------------|-------------------|
| **1 – 500** | 4,500.00 | 1,200.00 | 1.0x |
| **501 – 1,000** | 6,500.00 | 1,600.00 | 1.3x |
| **1,001 – 2,000** | 9,000.00 | 2,200.00 | 1.6x |
| **2,001 – 3,500** | 12,500.00 | 3,000.00 | 2.0x |
| **3,500+** | Custom / Contact Us | Custom / Contact Us | Custom |

### Add-on Modules (Scaled by Band Multiplier)
Modules selected in addition to the Core licence are charged upfront and upon renewal. The base prices below are multiplied by the corresponding student band's **Module Multiplier** (e.g., 1.3x for the 501-1,000 band):

| Module | Upfront Base Price (GHS) | Renewal Base Price (GHS) |
|--------|--------------------------|--------------------------|
| **Financial Portal** (`finance`) | 2,200.00 | 550.00 |
| **Staff & HR Management** (`staff_hr`) | 1,800.00 | 450.00 |
| **Advanced Reports & Charts** (`reports`) | 1,400.00 | 350.00 |
| **Teacher Evaluations** (`evaluations`) | 1,600.00 | 400.00 |
| **Student Welfare & Disciplinary** (`student_welfare`) | 1,500.00 | 380.00 |
| **Student Promotion & Graduation** (`progression`) | 1,200.00 | 300.00 |
| **Advanced Administration** (`system_admin`) | 1,000.00 | 250.00 |
| **Advanced Teacher Tools** (`teacher_tools`) | 900.00 | 220.00 |
| **Secure Messaging Portal** (`messaging`) | 1,500.00 | 380.00 |
| **Teaching Practice Portal** (`practicum`) | 2,000.00 | 500.00 |

### Landing Page Discounts & Add-ons
- **Founding Client Discount**: A **15% discount** is applied to both the upfront and renewal Core Licence fees.
- **Bundle Discount**: A **12% discount** is applied to the sum of selected modules (both upfront and renewal) if **4 or more modules** are selected.
- **Server & Hosting Setup Setup Fee**:
  - Self-Hosted Server Setup: **GHS 1,200.00** (One-time)
  - Managed Cloud Setup: **GHS 1,600.00** (One-time)
- **One-time Implementation Extras**:
  - System Configuration & Data Entry: **GHS 800.00**
  - Legacy Data Migration: **GHS 2,000.00**
- **One-time Training Services**:
  - Remote Admin Training: **GHS 600.00** per session
  - Remote Lecturer Training: **GHS 500.00** per session
  - On-Site Training Days: **GHS 1,500.00** per day

---

## 2. Internal Admin Settings License Calculator
This pricing structure is displayed in the system settings panel (`LicenceSettingsPage` component) and represents the billing calculations for active school subscriptions.

### Core Annual Subscription & Implementation
- **Base Annual Fee**: **GHS 12,000.00** (scaled by the Multiplier below)
- **Implementation Fee**: **GHS 3,500.00** (One-time, non-scaled)
- **Hosting Annual Fee**: **GHS 1,500.00** (Fixed, non-scaled)

### Subscription Student Bands & Multipliers
The multiplier is determined by the **maximum active student capacity** set on the school licence:

| Active Student Capacity | Multiplier | Band Label |
|-------------------------|------------|------------|
| **1 – 100** | 1.0x | 1 - 100 Students |
| **101 – 500** | 1.25x | 101 - 500 Students |
| **501 – 1,000** | 1.5x | 501 - 1000 Students |
| **Over 1,000** | 2.0x | Over 1000 Students |

### Module Pricing (Scaled by Multiplier)
For each module enabled in the admin licence panel:
- **Module Annual Fee** = Module Base Price (shown below) × Student Band Multiplier.
- **Module Setup Fee** = **GHS 500.00** × Student Band Multiplier.

| Module | Base Price (GHS) |
|--------|------------------|
| **Financial Portal** (`finance`) | 2,200.00 |
| **Staff & HR Management** (`staff_hr`) | 1,800.00 |
| **Advanced Reports & Charts** (`reports`) | 1,400.00 |
| **Teacher Evaluations** (`evaluations`) | 1,600.00 |
| **Student Welfare & Disciplinary** (`student_welfare`) | 1,500.00 |
| **Student Promotion & Graduation** (`progression`) | 1,200.00 |
| **Advanced Administration** (`system_admin`) | 1,000.00 |
| **Advanced Teacher Tools** (`teacher_tools`) | 900.00 |
| **Secure Messaging Portal** (`messaging`) | 1,500.00 |
| **Teaching Practice Portal** (`practicum`) | 2,000.00 |

### Admin Subscription Discounts
- **All-Modules Discount**: A **20% discount** is applied to the total modules annual fee if **all modules** are active.

---

*Document version 3.0 · June 2026*
