<x-mail::message>
# Quota Increase Request

A user has requested a quota increase on Finvixy.

**User:** {{ $user->name }}
**Email:** {{ $user->email }}
**Organisation:** {{ $user->organisation?->name ?? 'N/A' }}
**Current plan:** {{ $user->plan?->name ?? 'None' }}
**Monthly limit:** {{ $currentLimit }} receipts
**Used this month:** {{ $currentUsage }} receipts

Please review and upgrade their plan if appropriate.

<x-mail::button :url="'mailto:' . $user->email">
Reply to User
</x-mail::button>

— Finvixy System
</x-mail::message>
