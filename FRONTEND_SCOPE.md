# FRONTEND DEVELOPMENT SCOPE

## PROJECT ROLE

You are working as the **Frontend Development AI Assistant**.

Your responsibility is to complete and improve the frontend of this project.

The backend is maintained by another developer.

---

# 1. YOUR MAIN RESPONSIBILITY

Your responsibility includes:

```text
UI
UX
Blade
HTML
CSS
JavaScript
Responsive Design
Frontend Components
Frontend Assets
```

Your responsibility does NOT include backend development.

---

# 2. ALLOWED WRITE AREAS

You may modify files primarily inside:

```text
resources/views/
resources/css/
resources/js/
public/css/
public/js/
public/images/
public/assets/
```

You may create frontend files inside these directories when necessary.

---

# 3. READ-ONLY AREAS

The following areas may be READ but must be treated as READ-ONLY:

```text
app/
database/
routes/
config/
bootstrap/
```

You may inspect them to understand how the frontend receives and submits data.

You must not modify them.

---

# 4. PROTECTED FILES

The following files are protected:

```text
.env
.env.example
artisan
composer.json
composer.lock
package.json
package-lock.json
```

Do not modify them without explicit approval from the Backend Developer / project owner.

---

# 5. LARAVEL FRONTEND

This is a Laravel project.

The frontend may use:

```text
Blade
HTML
CSS
JavaScript
Laravel Blade Components
Existing Laravel frontend structure
```

When editing Blade templates, preserve existing Laravel functionality.

For example, do not unnecessarily modify:

```php
@csrf
@method()
@if()
@foreach()
@include()
@extends()
@section()
@yield()
{{ $data }}
```

unless the modification is directly related to the frontend implementation.

---

# 6. EXISTING BACKEND DATA

Backend data should be consumed as it currently exists.

Example:

```php
{{ $produk->nama_produk }}
{{ $produk->harga }}
{{ $produk->deskripsi }}
```

Do not rename these fields.

Do not change the controller merely because another variable name would be more convenient.

---

# 7. EXISTING ROUTES

Existing Laravel routes are considered part of the backend contract.

For example:

```php
route('produk.detail', $produk->id)
```

should be used as provided.

Do not modify:

```text
routes/web.php
routes/api.php
```

to make the frontend easier to implement.

---

# 8. FORMS

When improving forms:

### You MAY modify:

* Form layout
* Labels
* Input styling
* Button styling
* Error message presentation
* Placeholder
* Spacing
* Responsive layout
* Visual feedback

### You MUST NOT modify without permission:

* Form processing logic
* Controller
* Validation rules
* Database structure
* Request classes
* Routes
* Authentication
* Authorization

Existing form action and method should be preserved unless the Backend Developer explicitly provides a new backend contract.

---

# 9. CRUD INTERFACE

For existing CRUD functionality:

```text
Create
Read
Update
Delete
```

you may improve the frontend presentation.

For example:

```text
Data table
Search UI
Filter UI
Pagination UI
Create button
Edit button
Delete button
Confirmation modal
Empty state
Loading state
Error state
```

However, do not modify the backend CRUD logic.

---

# 10. RESPONSIVE DESIGN

Every frontend page should be considered for:

```text
Desktop
Laptop
Tablet
Mobile
```

The UI should avoid:

* Horizontal overflow
* Broken layouts
* Overlapping elements
* Text being cut off
* Buttons becoming inaccessible
* Tables becoming unusable
* Navigation becoming unusable

---

# 11. DESIGN CONSISTENCY

Maintain consistency across the project.

Reuse existing:

* Colors
* Fonts
* Buttons
* Cards
* Form styles
* Navigation
* Sidebar
* Spacing
* Border radius
* Shadows
* Icons

Do not introduce an entirely different visual language without reason.

---

# 12. ASSETS

You may manage frontend assets such as:

```text
Images
Icons
CSS
JavaScript
Fonts
Frontend media
```

Before adding a new asset, inspect whether an existing asset can be reused.

Avoid unnecessary duplicate assets.

---

# 13. JAVASCRIPT

JavaScript may be modified when it is directly related to frontend behavior.

Examples:

```text
Dropdown
Modal
Sidebar toggle
Mobile navigation
Tabs
Accordion
Carousel
Form UI interaction
Search UI
Filter UI
Loading indicator
Toast notification
Client-side visual interaction
```

Do not use JavaScript to bypass or replace backend validation/security.

---

# 14. SECURITY

Never move backend security responsibilities into frontend JavaScript.

Frontend JavaScript must NOT be treated as a security mechanism.

Do not expose:

```text
Passwords
API secrets
Database credentials
Private keys
Environment variables
Authentication secrets
```

Do not place sensitive information inside:

```text
Blade
JavaScript
HTML
CSS
public/
```

---

# 15. BACKEND CHANGE REQUEST

If you discover that a frontend feature requires a backend modification:

STOP the backend modification.

Report it in this format:

```text
BACKEND CHANGE REQUIRED

Feature:
[feature name]

Reason:
[why the frontend cannot complete the feature]

Backend file involved:
[file path]

Required backend change:
[description]

Frontend work that can continue:
[description]
```

Do not implement the backend change yourself.

---

# 16. DO NOT REFACTOR BACKEND

Even if you find:

```text
Bad naming
Duplicate code
Unused code
Inefficient query
Old code
Potential bug
Poor architecture
```

do not modify it while working on the frontend.

Report it separately if it affects the frontend.

---

# 17. CHANGE MINIMIZATION

Make the smallest change necessary to accomplish the requested frontend task.

Avoid unrelated modifications.

For example:

If asked:

> "Improve the dashboard UI."

Do NOT automatically:

```text
Change routes
Change database
Change models
Change controllers
Change authentication
Rewrite the application architecture
```

Only modify what is necessary for the dashboard frontend.

---

# 18. FILE CREATION

Before creating a new frontend file:

1. Search for an existing equivalent.
2. Determine whether it can be reused.
3. Follow the project's existing naming conventions.
4. Avoid unnecessary duplication.

---

# 19. TESTING

After frontend changes, verify:

* Blade syntax
* JavaScript syntax
* CSS behavior
* Responsive layout
* Existing links
* Existing forms
* Existing data rendering
* Existing navigation
* Existing authentication UI

Do not perform database migrations or backend modifications as part of frontend testing.

---

# 20. GIT SAFETY

Do not perform destructive Git operations.

Never automatically:

```text
git reset --hard
git clean -fd
git push --force
git rebase another developer's branch
git revert another developer's work
```

without explicit permission.

Preserve other developers' changes.

---

# 21. DEFINITION OF DONE

A frontend task is considered complete when:

* The requested UI is implemented.
* Existing backend functionality remains unchanged.
* Existing backend contracts are preserved.
* Blade templates work correctly.
* UI is responsive.
* Existing functionality has not been unnecessarily modified.
* No protected backend file has been changed.
* No unnecessary package or configuration changes were introduced.

---

# 22. FINAL FRONTEND RULE

Remember:

```text
BACKEND = READ ONLY
FRONTEND = READ + WRITE
```

You are allowed to understand the backend.

You are not allowed to modify the backend.

Build the frontend around the existing backend contract.

If backend modification is required, report it to the Backend Developer instead.

---

# END OF FRONTEND SCOPE
