# AI DEVELOPMENT RULES

> **IMPORTANT:** This file defines the mandatory rules for any AI agent working on this project.

---

# 1. PROJECT DEVELOPMENT STRUCTURE

This project is developed collaboratively by two developers:

* **Backend Developer:** Responsible for backend, database, business logic, authentication, authorization, routes, controllers, models, and server-side functionality.
* **Frontend Developer:** Responsible for user interface, user experience, Blade templates, CSS, JavaScript, responsive design, and frontend assets.

The AI agent working with the Frontend Developer must respect this separation of responsibilities.

---

# 2. PRIMARY OBJECTIVE

When working with the Frontend Developer, the AI's primary objective is:

> **Improve, complete, maintain, and optimize the FRONTEND without modifying the existing BACKEND.**

The existing backend must be treated as an established system and as a READ-ONLY resource.

---

# 3. BACKEND IS READ-ONLY

The AI MAY inspect and read backend code when necessary to understand:

* Available routes
* Existing data
* Database fields
* Model relationships
* Controller responses
* Authentication flow
* Authorization requirements
* Existing business logic
* Existing API endpoints
* Existing form submission requirements

However:

> **READING BACKEND CODE DOES NOT GIVE PERMISSION TO MODIFY IT.**

The AI must NEVER modify backend files while working on frontend tasks.

---

# 4. BACKEND FILES THAT MUST NOT BE MODIFIED

The following directories/files are considered BACKEND and are READ-ONLY:

```text
app/
database/
routes/
config/
bootstrap/
```

The AI must not modify backend-related files such as:

```text
app/Models/
app/Http/Controllers/
app/Http/Middleware/
app/Http/Requests/
app/Providers/

database/migrations/
database/seeders/
database/factories/

routes/web.php
routes/api.php
routes/console.php
routes/channels.php

config/*
bootstrap/*
```

Also treat the following as protected:

```text
.env
.env.example
composer.json
composer.lock
artisan
```

Do not modify these files unless the Backend Developer explicitly authorizes the change.

---

# 5. FORBIDDEN BACKEND ACTIONS

The AI MUST NOT:

* Modify controllers
* Modify models
* Modify migrations
* Modify seeders
* Modify factories
* Modify middleware
* Modify authentication logic
* Modify authorization logic
* Modify routes
* Modify database structure
* Modify database relationships
* Modify backend validation
* Modify backend business logic
* Modify API logic
* Refactor backend code
* Delete backend code
* Rename backend files
* Move backend files
* Change backend architecture
* Change environment configuration
* Install backend packages
* Remove backend packages
* Run migrations that modify the database structure
* Create new backend functionality without explicit permission

---

# 6. FRONTEND DEVELOPMENT AREA

The AI may work primarily within:

```text
resources/views/
resources/css/
resources/js/
public/css/
public/js/
public/images/
public/assets/
```

The exact available directories may vary depending on the project structure.

Before creating a new directory, the AI should inspect the existing project structure and reuse the existing structure when appropriate.

---

# 7. FRONTEND FILES

The AI MAY create, modify, or improve:

### Blade

```text
resources/views/**/*.blade.php
```

### CSS

```text
resources/css/*
public/css/*
```

### JavaScript

```text
resources/js/*
public/js/*
```

### Frontend Assets

```text
public/images/*
public/assets/*
```

The AI may work on:

* Layout
* Navbar
* Sidebar
* Footer
* Dashboard UI
* Landing page
* Forms
* Tables
* Cards
* Buttons
* Modals
* Notifications
* Components
* Responsive design
* Mobile layout
* Desktop layout
* Typography
* Spacing
* Colors
* Icons
* Visual hierarchy
* Animations
* Loading states
* Empty states
* Error states
* Accessibility
* User experience

---

# 8. BACKEND CONTRACT

The AI must treat the existing backend as a contract.

For example, if the backend already provides:

```text
Route:
GET /produk
```

and returns data such as:

```text
id
nama_produk
harga
deskripsi
gambar
```

the AI should build the frontend based on that existing structure.

The AI must NOT change the backend simply because it prefers another structure.

---

# 9. WHEN FRONTEND REQUIRES BACKEND CHANGES

Sometimes a frontend feature may require backend functionality.

For example:

* A new route is required
* A controller response is missing
* A database field is missing
* A new API endpoint is required
* Existing authentication must be changed
* Existing validation must be changed
* Existing business logic must be changed

In this situation:

### DO NOT MODIFY THE BACKEND.

Instead:

1. Identify the backend requirement.
2. Explain why the frontend cannot complete the feature without it.
3. Identify the relevant backend file(s).
4. Explain the required change conceptually.
5. Report the requirement to the Backend Developer.
6. Continue frontend work only where possible without changing the backend.

---

# 10. DO NOT "FIX" BACKEND AUTOMATICALLY

The AI may encounter backend code that appears:

* inefficient
* outdated
* repetitive
* poorly structured
* inconsistent
* unnecessarily complex
* potentially improvable

The AI must NOT automatically fix it.

Do NOT perform:

```text
Backend refactoring
Backend optimization
Backend cleanup
Backend redesign
Backend restructuring
Backend modernization
```

unless the Backend Developer explicitly authorizes it.

---

# 11. GIT SAFETY

The AI must be careful when performing Git-related actions.

Do NOT:

* Reset the repository
* Delete branches
* Force push
* Rewrite commit history
* Revert another developer's work
* Delete another developer's changes
* Checkout another branch and overwrite local work
* Perform destructive Git operations

Before any potentially destructive Git operation, stop and request explicit confirmation.

---

# 12. EXISTING CODE FIRST

Before creating new frontend code, the AI must inspect the existing project.

Prefer:

```text
Reuse existing components
Reuse existing layouts
Reuse existing CSS
Reuse existing JavaScript
Reuse existing Blade structures
Reuse existing assets
```

Avoid unnecessarily creating duplicate:

```text
Layouts
Components
CSS
JavaScript
Pages
Assets
```

The goal is to improve the existing project rather than create an unrelated frontend structure.

---

# 13. DO NOT BREAK EXISTING FUNCTIONALITY

Frontend changes must not intentionally break:

* Existing routes
* Existing forms
* Existing authentication
* Existing authorization
* Existing backend data
* Existing CRUD functionality
* Existing JavaScript functionality
* Existing Laravel functionality

If a frontend modification may affect an existing backend function, the AI must inspect the relevant integration before changing it.

---

# 14. DATA AND VARIABLE PRESERVATION

When existing Blade variables are used, preserve their names and expected structure unless the Backend Developer explicitly changes the backend contract.

For example:

```php
$produk
$user
$pengumuman
$booking
```

should not be renamed arbitrarily.

Existing fields should also be preserved.

Example:

```php
$produk->nama_produk
```

should not automatically be changed to:

```php
$produk->name
```

because the backend may depend on the existing field.

---

# 15. AUTHENTICATION AND AUTHORIZATION

Authentication and authorization are BACKEND responsibilities.

The AI may:

* Display login/logout UI
* Display user information
* Hide/show frontend elements based on existing available authentication data
* Consume existing authentication state

The AI must NOT modify:

```text
Authentication logic
Authorization logic
Middleware
Roles
Permissions
User database structure
Login processing
Registration processing
Password handling
```

---

# 16. DATABASE

The database is BACKEND territory.

The AI may inspect existing database-related structures to understand frontend data.

The AI must NOT:

* Create migrations
* Modify migrations
* Delete migrations
* Change database tables
* Change database columns
* Change relationships
* Change database constraints
* Modify production data

---

# 17. PACKAGE MANAGEMENT

Do not install or remove packages without explicit permission.

Especially do not automatically modify:

```text
composer.json
composer.lock
package.json
package-lock.json
```

If a frontend package is genuinely required, explain:

1. Package name
2. Why it is needed
3. What it will change
4. Whether there is an existing alternative

Then request approval before installing it.

---

# 18. VISUAL QUALITY STANDARD

Frontend work should prioritize:

* Clean UI
* Consistent design
* Responsive layout
* Good spacing
* Clear typography
* Accessible contrast
* Consistent components
* Intuitive navigation
* Professional appearance
* Desktop compatibility
* Mobile compatibility

Do not sacrifice existing functionality merely to improve appearance.

---

# 19. BEFORE MAKING CHANGES

Before modifying files, the AI should:

1. Inspect the relevant frontend files.
2. Inspect related backend files only for understanding.
3. Understand existing data flow.
4. Identify dependencies.
5. Determine the smallest safe change.
6. Modify only files within the permitted frontend scope.

---

# 20. FINAL SAFETY RULE

If there is uncertainty about whether a file is frontend or backend:

> **DO NOT MODIFY THE FILE.**

Treat uncertain files as READ-ONLY until the Frontend Developer receives clarification from the Backend Developer.

---

# 21. HIGHEST PRIORITY RULE

These rules have higher priority than convenience.

The AI must never modify the backend simply because doing so would make the frontend implementation easier.

The correct approach is:

```text
Understand Backend
        ↓
Respect Backend Contract
        ↓
Build Frontend Around Existing Backend
        ↓
Request Backend Developer When Backend Changes Are Required
```

---

# END OF AI RULES
