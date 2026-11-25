<?php

/*
|--------------------------------------------------------------------------
| Ministry details
|--------------------------------------------------------------------------
|
| Contact details and feature flags for the public site. Views read these
| values; never hardcode them in Blade. Empty values are null and the
| matching link/section is hidden until the owner fills it in.
|
*/

// Keep only https:// URLs. Anything else (javascript:, http:, typos) becomes null.
$httpsUrl = static function (?string $url): ?string {
    $url = trim((string) $url);

    return str_starts_with($url, 'https://') ? $url : null;
};

// Keep only a valid email address. Anything else becomes null.
$validEmail = static function (?string $email): ?string {
    $email = trim((string) $email);

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
};

return [

    'name' => env('MINISTRY_NAME', 'COG Audio Ministry'),

    'subheader' => env('MINISTRY_SUBHEADER', 'Dasmariñas'),

    'tagline' => env('MINISTRY_TAGLINE', 'Serving together with heart and harmony.'),

    // Theme verse, as used on the ministry's Facebook page. Shown in the hero; hidden if the text is empty.
    'verse' => [
        'text' => env('MINISTRY_VERSE_TEXT', 'Praise our God, all peoples, let the sound of his praise be heard.'),
        'reference' => env('MINISTRY_VERSE_REFERENCE', 'Psalm 66:8 (NIV)'),
    ],

    'facebook_url' => $httpsUrl(env('MINISTRY_FACEBOOK_URL', 'https://www.facebook.com/COGAudioDasma')),

    'email' => $validEmail(env('MINISTRY_EMAIL', 'cogdasmaaudio@gmail.com')),

    // TODO: owner to provide the Messenger link.
    'messenger_url' => $httpsUrl(env('MINISTRY_MESSENGER_URL')),

    // TODO: owner to provide the church address (the map shows only when filled in).
    'address' => env('MINISTRY_ADDRESS') ?: null,

    // Max branch-admin accounts per branch. 'default' applies unless the branch slug
    // is listed under 'branches'. Change here; no code change needed.
    'branch_admin_limit' => [
        'default' => 1,
        'branches' => ['dasma' => 2],
    ],

    // "Meet the Team" section and nav link. Default OFF.
    'show_team' => filter_var(env('MINISTRY_SHOW_TEAM', false), FILTER_VALIDATE_BOOLEAN),

    // TODO: owner to provide the number of years serving. Null hides the stat.
    'years_serving' => ((int) env('MINISTRY_YEARS_SERVING')) > 0 ? (int) env('MINISTRY_YEARS_SERVING') : null,

];
