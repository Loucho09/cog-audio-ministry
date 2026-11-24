# Timezone: Asia/Manila
Date: 2026-11-24
Status: accepted (owner confirmed)

Related: [[homepage-sections]], [[Project-Brief]], [[00-Index]]

Decision: `APP_TIMEZONE=Asia/Manila`.

Why:
- All users are in the Philippines.
- Manila has no daylight saving time, so stored times never shift.

Consequence:
- Eloquent timestamps and service times are stored in Manila time.
- Changing the timezone later would change the meaning of existing rows. Ask before changing it.
