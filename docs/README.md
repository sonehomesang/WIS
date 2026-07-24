# Warehouse Inventory & Borrowing App

This is a comprehensive full-stack application built using React, Vite, Tailwind CSS, and Firebase. It allows an organization to manage warehouse inventory, tool borrowing, returns, extensions, and workflow approvals.

## 🚀 Key Features

*   **Inventory Management**: Track tools and materials with robust details (ID, Storage Location, Bin, Category, Quantity).
*   **Borrowing Workflow**: Users can select items, specify borrow periods, and submit requests.
*   **Approval System**: Multi-level workflow with Manager Acknowledgement and Warehouse Admin Approval.
*   **Tracking & Returns**: Monitors active borrowed items, tracks overdue records, and allows users to request extensions.
*   **Condition Reporting & Proof**: Upload photos when borrowing or returning items for condition verification.
*   **Printable Records (PDF & Image)**: Built-in support to snapshot and print Borrowing Record sheets with all signatures.
*   **Export to Excel**: Complete Excel Data generation for auditing and reporting.
*   **Activity Logs & Notifications**: Transparent tracking system that records all changes.

## 🏗️ Architecture and Tech Stack

*   **Framework**: React (v19) + Vite
*   **Language**: TypeScript for strong typing
*   **Styling**: Tailwind CSS v4
*   **Icons**: `lucide-react`
*   **Animations**: Motion / Framer Motion
*   **Export/Print**: `html2canvas-pro`, `jspdf`, `xlsx`, `html-to-image`
*   **Database & Auth**: Firebase (Firestore for realtime NoSQL DB, Authentication for users)

## 📂 Project Structure

```text
├── package.json               # Dependencies and scripts (npm run dev/build/lint)
├── index.html                 # App entry point
├── src/                       # Main source code
│   ├── main.tsx               # React Render and Context Providers
│   ├── App.tsx                # Main application component & core routing / views
│   ├── types.ts               # Interface definitions (InventoryItem, BorrowRecord, ActivityLog)
│   ├── firebase.ts            # Firebase initialization and setup
│   ├── index.css              # Global styles & layout rules
│   ├── lib/                   # Utility helpers (e.g., tailwind merge cn tool)
│   └── components/            # Specialized UI pieces (e.g., EditBorrowRecordModal)
├── .env.example               # Environment Variables configuration
└── AGENTS.md                  # Strict instructions for AI Assistant workflows
```

## 🛠️ Setup for Local Development

1.  **Install Dependencies**
    ```sh
    npm install
    ```
2.  **Firebase Setup**
    Ensure you define the `.env` file using `.env.example` as a template with all necessary `VITE_FIREBASE_*` variables.
3.  **Start Development Server**
    ```sh
    npm run dev
    ```
4.  **Build**
    ```sh
    npm run build
    ```

## 🔍 Code Guidelines

*   **Tailwind ONLY**: Do not write raw CSS definitions outside of base utilities in `index.css`.
*   **Component Modularity**: If modifying large files like `App.tsx`, consider abstracting logic into the `components` folder.
*   **Dates & Locales**: Handled predominantly via `date-fns`.
*   **Data Models**: Follow `src/types.ts`. All Firestore interactions should be typed based on these interfaces.

## 👥 Roles & Access

*   **User/Borrower**: Can see inventory, request borrows, view their own borrowing history, return items, and request deadline extensions.
*   **Admin/Warehouse Team**: Can manage the entire inventory, approve/reject borrowing, confirm returns, review all activity logs, configure notifications, and export data. Admins are validated securely in the environment/database.
