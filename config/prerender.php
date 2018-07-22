<?php return [

    /*
    |--------------------------------------------------------------------------
    | Enable Prerender
    |--------------------------------------------------------------------------
    |
    | Set this field to false to fully disable the prerender service. You
    | would probably override this in a local configuration, to disable
    | prerender on your local machine.
    |
    */

    'enable' => env('PRERENDER_ENABLE', true),

    /*
    |--------------------------------------------------------------------------
    | Prerender URL
    |--------------------------------------------------------------------------
    |
    | This is the prerender URL to the service that prerenders the pages.
    | By default, Prerender's hosted service on prerender.io is used
    | (https://service.prerender.io). But you can also set it to your
    | own server address.
    |
    */

    'prerender_url' => env('PRERENDER_URL', 'https://service.prerender.io'),

    /*
    |--------------------------------------------------------------------------
    | Prerender Token
    |--------------------------------------------------------------------------
    |
    | If you use prerender.io as service, you need to set your prerender.io
    | token here. It will be sent via the X-Prerender-Token header. If
    | you do not provide a token, the header will not be added.
    |
    */

    'prerender_token' => env('PRERENDER_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Prerender Whitelist
    |--------------------------------------------------------------------------
    |
    | Whitelist paths or patterns. You can use asterix syntax, or regular
    | expressions (without start and end markers). If a whitelist is supplied,
    | only url's containing a whitelist path will be prerendered. An empty
    | array means that all URIs will pass this filter. Note that this is the
    | full request URI, so including starting slash and query parameter string.
    | See github.com/JeroenNoten/Laravel-Prerender for an example.
    |
    */

    'whitelist' => [],

    /*
    |--------------------------------------------------------------------------
    | Prerender Blacklist
    |--------------------------------------------------------------------------
    |
    | Blacklist paths to exclude. You can use asterix syntax, or regular
    | expressions (without start and end markers). If a blacklist is supplied,
    | all url's will be prerendered except ones containing a blacklist path.
    | By default, a set of asset extentions are included (this is actually only
    | necessary when you dynamically provide assets via routes). Note that this
    | is the full request URI, so including starting slash and query parameter
    | string. See github.com/JeroenNoten/Laravel-Prerender for an example.
    |
    */

    'blacklist' => [
        '/api/*', // do not prerender pages starting with '/api/'
        '*.js',
        '*.css',
        '*.xml',
        '*.less',
        '*.png',
        '*.jpg',
        '*.jpeg',
        '*.gif',
        '*.pdf',
        '*.doc',
        '*.txt',
        '*.ico',
        '*.rss',
        '*.zip',
        '*.mp3',
        '*.rar',
        '*.exe',
        '*.wmv',
        '*.doc',
        '*.avi',
        '*.ppt',
        '*.mpg',
        '*.mpeg',
        '*.tif',
        '*.wav',
        '*.mov',
        '*.psd',
        '*.ai',
        '*.xls',
        '*.mp4',
        '*.m4a',
        '*.swf',
        '*.dat',
        '*.dmg',
        '*.iso',
        '*.flv',
        '*.m4v',
        '*.torrent'
    ],


    /*
    |--------------------------------------------------------------------------
    | Crawler User Agents
    |--------------------------------------------------------------------------
    |
    | Requests from crawlers that do not support _escaped_fragment_ will
    | nevertheless be served with prerendered pages. You can customize
    | the list of crawlers here.
    |
    */

    'crawler_user_agents' => [
        // googlebot, yahoo, and bingbot are not in this list because
        // we support _escaped_fragment_ and want to ensure people aren't
        // penalized for cloaking.

        // 'googlebot',
        // 'yahoo',
        // 'bingbot',

        // 20180721 As per prerender.io advice:
        // Make sure to update your Prerender.io middleware or manually add
        // googlebot and bingbot to the list of user agents being checked by
        // your Prerender.io middleware:
        'googlebot',
        'bingbot',
        // Also adding the following from:
        // https://prerender.io/documentation/google-support
        'bufferbot',
        'www.google.com/webmasters/tools/richsnippets',
        'slackbot',
        'vkShare',
        'W3C_Validator',
        'redditbot',
        'Applebot',
        'WhatsApp',
        'flipboard',
        'tumblr',
        'bitlybot',
        'SkypeUriPreview',
        'nuzzel',
        'Discordbot',
        'Google Page Speed',
        'Qwantify',
        // 20180721 END CHANGES
        'baiduspider',
        'facebookexternalhit',
        'twitterbot',
        'rogerbot',
        'linkedinbot',
        'embedly',
        'quora link preview',
        'showyoubot',
        'outbrain',
        'pinterest',
        'developers.google.com/+/web/snippet'
    ],

];