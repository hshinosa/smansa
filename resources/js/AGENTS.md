# FRONTEND KNOWLEDGE BASE

**Generated:** 2026-02-12
**Stack:** React 18, Inertia.js (React), Tailwind CSS 4.0+, Vite

## OVERVIEW
Frontend logic for SMAN 1 Baleendah using Inertia.js to seamlessly integrate React components with Laravel routing and data flow.

## STRUCTURE
```
resources/js/
├── Components/       # Atomic UI components & Feature blocks
├── Hooks/            # Custom React hooks (e.g., useChat)
├── Layouts/          # Page layouts (Authenticated, Guest)
├── Pages/            # Inertia Pages (Routed from Laravel)
│   ├── Admin/        # Back-office admin panels
│   └── Public/       # Public-facing school website
├── lib/              # Utilities & Shared helpers
└── app.jsx           # Main entry point (Bootstraps React)
```

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| **Pages** | `resources/js/Pages/` | Core views mapped to Laravel Routes |
| **Reusable UI** | `resources/js/Components/` | Shared buttons, modals, cards |
| **Admin Panel** | `resources/js/Pages/Admin/` | Dashboard, settings, CRUD forms |
| **Styles** | `resources/css/app.css` | Global Tailwind directives |
| **Routing** | `routes/web.php` | (Laravel) Defines which Page component loads |

## KEY CONVENTIONS
- **Inertia Props**: Data is passed from Laravel Controllers as props to React Pages.
- **Routing**: Use `Link` from `@inertiajs/react` for internal navigation, NOT `<a>`.
- **Forms**: Use `useForm` hook from `@inertiajs/react` for form handling & validation errors.
- **Components**: Functional components only. Prefer composition over inheritance.
- **Styling**: Tailwind utility classes first. Custom CSS only when necessary.

## COMMANDS
```bash
# Development
npm run dev           # Starts Vite server (HMR)

# Build for Production
npm run build         # Compiles assets to public/build
```

## NOTES
- **State Management**: Local state (useState) + Inertia props. Complex global state avoided where possible.
- **Assets**: Images/media handled via Laravel's `asset()` helper or imported in JS.
- **CSRF**: Handled automatically by Inertia/Axios. No manual token needed in headers.
