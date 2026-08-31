---
paths:
  - 'app/Models/**'
---

# Models

## Use #[Fillable] attribute for mass assignment
Define mass assignment fields using the #[Fillable([...])] class attribute on Eloquent models, not the protected $fillable property array.
