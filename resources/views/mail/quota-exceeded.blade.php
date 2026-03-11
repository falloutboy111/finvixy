<x-mail::message>
# 📊 Receipt Quota Exceeded

Hi {{ $user->first_name ?? $user->name }},

You've used all **{{ $used }}/{{ $limit }}** receipts this month.

**Current Plan:** {{ $planName }}
**Usage:** {{ $used }} receipts (100%)
**Days remaining:** {{ $daysRemaining }}

Your quota resets on **{{ now()->endOfMonth()->format('F d, Y') }}**.

<x-mail::button :url="$upgradeUrl">
Upgrade Now
</x-mail::button>

To continue uploading receipts and access more features, upgrade your plan today.

— Finvixy
</x-mail::message>
