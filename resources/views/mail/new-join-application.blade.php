<x-mail::message>
# A new friend wants to serve

Someone has applied to join the {{ $ministry }}. Here is a quick summary.

- **Name:** {{ $name }}
- **Branch:** {{ $branch }}
- **Interests:** {{ $interests }}
- **Submitted:** {{ $submitted }}

<x-mail::button :url="$url">
Review in the admin panel
</x-mail::button>

Please review the application when you can. Thank you for your care for our new applicants.
</x-mail::message>
