<x-mail::message>
# Nuova Iscrizione alla Newsletter

Un nuovo utente si è iscritto alla newsletter.

**Dettagli Iscrizione:**
- **Email:** {{ $subscription->email }}

Grazie,<br>
{{ config('app.name') }}
</x-mail::message>
