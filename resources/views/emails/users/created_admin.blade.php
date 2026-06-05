<x-mail::message>
# Nuovo Utente Registrato

Un nuovo utente si è registrato sulla piattaforma.

**Dettagli Utente:**
- **Nome:** {{ $user->name }}
- **Email:** {{ $user->email }}
- **Ruolo:** {{ $user->role }}

<x-mail::button :url="config('app.url') . '/admin/users/' . $user->id . '/edit'">
Visualizza Utente in Admin
</x-mail::button>

Grazie,<br>
{{ config('app.name') }}
</x-mail::message>
