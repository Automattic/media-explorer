Media Explorer
==============
Media Explorer gives you the ability to insert content from social media
services in your posts.

Setup
-----
In order to get this working in your WordPress installation, you have to follow
the next steps:

* Clone this repo in the plugins folder of your WordPress install with `git
clone https://github.com/Automattic/media-explorer.git`.
* Get your credentials:
  * [Twitter](https://dev.twitter.com)
  * [Instagram](https://instagram.com/developer).
  * [YouTube](https://developers.google.com/youtube/v3/).
    * For YouTube, you'll have to create or use an existing project in your [Google Developers Console](https://cloud.google.com/console/project)
    * Ensure that this project has the "YouTube Data API v3" API enabled.
    * Create and use a public access API Key for your project.
* Write your credentials in [mexp-creds.php](https://github.com/Automattic/media-explorer/blob/master/mexp-creds.php)
* Activate the "MEXP oAuth Creditials" plugin to enable the configured API keys.
* Enjoy!
