=== AI News Poster ===
Contributors: ainewsposter
Tags: news, AI, article generation, content creation
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

AI News Poster is an innovative WordPress plugin that automates news summarization and aggregation using AI technology.

== Description ==

AI News Poster is a cutting-edge plugin designed to help WordPress site owners automatically generate summarized and rewritten news content. By integrating with various APIs like Bing News Search API, PagePixels, and OpenAI, the plugin fetches, processes, and rewrites news articles, making content creation effortless and efficient.

Key Features:
- Fetch latest news articles based on user-defined topics.
- Summarize and rewrite articles using advanced AI technology.
- Option to automatically publish or save articles as drafts.
- Support for setting custom post authors, categories, and tags.
- Automatically detects duplicate articles and discards them before writing a new post.

Third-Party APIs: 
- [Bing Search API](https://www.microsoft.com/en-us/bing/apis/bing-news-search-api) retrieves relevant articles based on your configuration criteria. They provide a free tier of 1,000 searches per month.
- [The PagePixels Screenshot API](https://pagepixels.com/pricing) retrieves the full article content from the news URL. They provides a free tier of 25 captures per month. 
- [OpenAI API](https://openai.com/pricing) rewrites / summarizes the articles. Their pricing depends on the model used. The default model `gpt-3.5-turbo` can generate ~20-30 articles per day for $0.15 to $0.25 cents.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/ainewsposter` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings->AI News Poster screen to configure and use the plugin. Remember to set your API Keys in the API Keys section.

== Frequently Asked Questions ==

= Can I customize the news topics? =
Yes, AI News Poster allows you to specify the news topics you want to aggregate.

= Is it possible to control the publishing of articles? =
Absolutely! You can choose to automatically publish articles or save them as drafts.

= Is support available? = 
Absolutely

== Screenshots ==

1. Setup API Keys
2. Post Configuration
3. Bing News Search API Setup
4. OpenAI Setup
5. Generate Posts

== Changelog ==

= 1.0.0 =
- Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial Release.

== Terms and Privacy Policies for Third Party API service providers ==

Bing News API (Bing Search API): [Privacy Policy and Terms](https://www.microsoft.com/en-us/bing/apis/legal)

Open AI: [Privacy Policy and Terms](https://openai.com/policies)

PagePixels: [Privacy Policy](https://pagepixels.com/privacy) and [Terms](https://pagepixels.com/terms)

== Additional Information ==

For more information, visit the official [AI News Poster website](https://ainewsposter.com/).
