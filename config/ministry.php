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

return [

    'name' => env('MINISTRY_NAME', 'COG Audio Ministry'),

    'subheader' => env('MINISTRY_SUBHEADER', 'Dasmariñas'),

    'facebook_url' => env('MINISTRY_FACEBOOK_URL', 'https://www.facebook.com/COGAudioDasma'),

    'email' => env('MINISTRY_EMAIL', 'cogdasmaaudio@gmail.com'),

    // TODO: owner to provide the Messenger link.
    'messenger_url' => env('MINISTRY_MESSENGER_URL') ?: null,

    // TODO: owner to provide the church address (the map shows only when filled in).
    'address' => env('MINISTRY_ADDRESS') ?: null,

    // "Meet the Team" section and nav link. Default OFF.
    'show_team' => filter_var(env('MINISTRY_SHOW_TEAM', false), FILTER_VALIDATE_BOOLEAN),

];
