# Media Explorer

**Contributors:** automattic, johnbillion, garhdez, djpaul  
**Tags:** media, social media, twitter, youtube, media explorer  
**Requires at least:** 6.4  
**Tested up to:** 7.1  
**Requires PHP:** 7.4  
**Stable tag:** 1.2  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Insert social media content into your posts, straight from the classic editor's media window.

## Description

Media Explorer adds external media services to the classic editor's **Add Media** window, so you can find an X post, video or photo and insert it into a post without leaving the editor. Selected items are inserted as URLs, which WordPress turns into embeds.

It does not work in the block editor. See [Does it work with the block editor?](#does-it-work-with-the-block-editor).

It ships with three services:

- **X**: search posts by keyword, by hashtag, by or to a user, or by location. Reposts are left out of the results.
- **YouTube**: search videos by keyword or by user.
- **Instagram**: browse popular photos, or search by tag or user. With a user's own access token (see [Keyring](#installation)), it also shows their photos and feed.

Each service needs API credentials before it can return results. See [Installation](#installation).

Developers can add their own services by extending `MEXP_Service`. See [Adding a service](#adding-a-service).

## Installation

1. Download the plugin from [GitHub](https://github.com/Automattic/media-explorer), or clone it into `wp-content/plugins`:

   ~~~
   git clone https://github.com/Automattic/media-explorer.git
   ~~~

2. Activate **Media Explorer** in the Plugins screen.
3. Get credentials for the services you want to use:
   - **X**: create an app in the [X developer portal](https://developer.x.com/) and note its consumer key and secret, and its access token and secret.
   - **YouTube**: in the [Google Cloud console](https://console.cloud.google.com/), create a project (or use an existing one), enable the **YouTube Data API v3**, and create an API key.
   - **Instagram**: get an access token for the account whose photos you want to browse.
4. Supply the credentials through environment variables, or through filters from your theme or a plugin of your own.

### Environment variables

Set these as environment variables on the server. On WordPress VIP, set them as [environment variables](https://docs.wpvip.com/infrastructure/environments/manage-environment-variables/), which the plugin reads with `vip_get_env_var()`.

| Service | Environment variables |
|---|---|
| X | `MEXP_TWITTER_CONSUMER_KEY`, `MEXP_TWITTER_CONSUMER_SECRET`, `MEXP_TWITTER_OAUTH_TOKEN`, `MEXP_TWITTER_OAUTH_TOKEN_SECRET` |
| YouTube | `MEXP_YOUTUBE_DEVELOPER_KEY` |
| Instagram | `MEXP_INSTAGRAM_ACCESS_TOKEN` |

### Filters

The filters receive the values from the environment variables, so a filter overrides them.

~~~php
add_filter( 'mexp_twitter_credentials', function () {
	return array(
		'consumer_key'       => '',
		'consumer_secret'    => '',
		'oauth_token'        => '',
		'oauth_token_secret' => '',
	);
} );

add_filter( 'mexp_youtube_developer_key', function () {
	return '';
} );

add_filter( 'mexp_instagram_credentials', function () {
	return array(
		'access_token' => '',
	);
} );
~~~

The plugin also includes `mexp-creds.php`, which registers these filters as a separate plugin, **MEXP oAuth Credentials**. You can fill in your credentials there and activate it instead, but your changes will be overwritten when you update Media Explorer, so the filters above are the better choice.

If you use the [Keyring](https://wordpress.org/plugins/keyring/) plugin, activate **MEXP Keyring Credentials** (`mexp-keyring-user-creds.php`) to have the Instagram service use each user's own Keyring connection.

## Frequently Asked Questions

### Does it work with the block editor?

No. Media Explorer adds its services to the classic editor's **Add Media** window, which the block editor doesn't use. In the block editor, paste a YouTube or other supported URL into the post and WordPress embeds it.

### Why does a service show an error instead of results?

Usually because its credentials are missing. Check that the environment variables or the filter for that service supply non-empty values.

### Do all the services still work?

The X service uses version 1.1 of the X API, and the Instagram service uses Instagram's original API. Both platforms have since restricted or retired those APIs, so these services may not return results without changes. The YouTube service uses the current YouTube Data API v3.

### Where can I report a bug or contribute?

See [Support](#support) and [Contributing](#contributing).

## Adding a service

A service is a class extending `MEXP_Service`, registered with the `mexp_services` filter. The bundled services in [`services/`](https://github.com/Automattic/media-explorer/tree/develop/services) are working examples.

Hooks:

| Hook | Type | Purpose |
|------|------|---------|
| `mexp_services` | Filter | Register services, keyed by service ID. |
| `mexp_tabs` | Filter | Tabs shown for each service. |
| `mexp_labels` | Filter | Labels shown for each service. |
| `mexp_ajax_request_args` | Filter | Request arguments before a service runs a search. |
| `mexp_init` | Action | Fires once services are loaded. |
| `mexp_enqueue` | Action | Fires when the media manager scripts are enqueued. |
| `mexp_ajax_request_success` | Action | Fires after a successful search request. |
| `mexp_ajax_request_error` | Action | Fires after a failed search request. |
| `mexp_twitter_credentials` | Filter | X API credentials. |
| `mexp_youtube_developer_key` | Filter | YouTube Data API key. |
| `mexp_instagram_credentials` | Filter | Instagram access token for the site. |
| `mexp_instagram_user_credentials` | Filter | Instagram access token for the current user. |

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](https://github.com/Automattic/media-explorer/blob/develop/CONTRIBUTING.md) for how to set up a development environment, run the checks and submit a pull request.

## Support

Report bugs and request features in [GitHub issues](https://github.com/Automattic/media-explorer/issues). To report a security vulnerability, follow the [security policy](https://github.com/Automattic/media-explorer/blob/develop/.github/SECURITY.md) rather than opening a public issue.

## Credits

Originally built by [Code For The People](https://github.com/codeforthepeople), and maintained by [Automattic](https://automattic.com/).

## License

Media Explorer is licensed under the GPLv2 or later. See [LICENSE](https://github.com/Automattic/media-explorer/blob/develop/LICENSE).

## Changelog

### 1.2

- **Fixed:** The "get more" button now loads further results for the same Twitter search.
- **Changed:** Retweets are hidden from Twitter search results.

### 1.1

Unreleased internal version.

### 1.0

First stable release.
