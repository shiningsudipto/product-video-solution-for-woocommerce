=== Product Video Solution for WooCommerce ===
Contributors: shiningsudipto
Tags: product video, woocommerce video, youtube, vimeo, elementor
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add WooCommerce product videos from the Media Library, YouTube, Vimeo or any URL, and show them anywhere on the product page. Elementor ready.

== Description ==

**Product Video Solution for WooCommerce** adds a video to any WooCommerce product and shows it exactly where you want on the single product page: after the description, in the product summary, after the tabs, or anywhere you drop the Elementor widget or shortcode.

Product videos help shoppers see how a product looks and works, which builds trust and increases sales. This plugin keeps it simple and fast: the video player only loads when the visitor clicks play, so product pages stay quick for SEO and Core Web Vitals.

= Key features =

* **Video from the WordPress Media Library** – upload an .mp4 or .webm, or choose one you already have.
* **YouTube product video** – paste any YouTube link (watch, youtu.be, Shorts or embed). Plays through the privacy-enhanced youtube-nocookie.com player.
* **Vimeo and external video URL** – paste a Vimeo link or a direct link to a video file on any server or CDN.
* **Choose the thumbnail** – automatic, any frame of the video, or your own image.
* **Show the video anywhere** – Elementor widget, `[pvsw_video]` shortcode, or a preset position.
* **Works with Elementor and ShopBuilder product templates** – preset positions attach after the matching product widget, even on builder pages.
* **Custom play icon** – four icon styles, with your own icon colour, background colour and size.
* **Playback options** – autoplay, start muted, loop, and show or hide player controls, per product.
* **Spacing controls** – margin and padding for every side (top, right, bottom, left) in px, %, em, rem or vw.
* **Fast and lightweight** – no jQuery on the front end, the player loads on click, and styles load only where a video is shown.
* **Responsive 16:9 player** for desktop, tablet and mobile.
* **Accessible** – keyboard-friendly play button with a descriptive label.
* **Compatible with WooCommerce High-Performance Order Storage (HPOS).**

= Where can the video go? =

* After the product description
* In the product summary, after the Add to cart button
* After the product summary
* After the product tabs
* Anywhere in an Elementor or ShopBuilder product template, with the **Product Video** widget
* Any page, post or widget area, with the `[pvsw_video id="123"]` shortcode

= Shortcode =

* `[pvsw_video]` – the video of the product being viewed.
* `[pvsw_video id="123"]` – the video of product 123, on any page.

= About the author =

Product Video Solution for WooCommerce is built and maintained by Sudipta Das.

* GitHub: [github.com/shiningsudipto](https://github.com/shiningsudipto)
* Email: shiningsudipto@gmail.com

== External services ==

This plugin can show videos hosted on third-party services. It only contacts a service for products where you chose that service as the video source.

**YouTube** (Google)

* Used to show YouTube product videos and their thumbnails.
* When a product page with a YouTube video is viewed, the thumbnail is loaded from `i.ytimg.com`. When the visitor clicks play (or straight away if autoplay is on), the player is loaded from `www.youtube-nocookie.com`. The visitor's browser sends its IP address and the requested video ID, as with any embedded YouTube video.
* The same thumbnail is loaded in the product editor when you paste a YouTube link.
* YouTube Terms of Service: https://www.youtube.com/t/terms
* Google Privacy Policy: https://policies.google.com/privacy

**Vimeo**

* Used to show Vimeo product videos.
* When the visitor clicks play (or straight away if autoplay is on), the player is loaded from `player.vimeo.com` with "do not track" turned on. The visitor's browser sends its IP address and the requested video ID.
* Vimeo Terms of Service: https://vimeo.com/terms
* Vimeo Privacy Policy: https://vimeo.com/privacy

**External video URL**

* If you enter a direct link to a video file on another website or CDN, the visitor's browser loads the file from that address. That site's own terms and privacy policy apply.

Media Library videos are served from your own site and do not contact any external service.

== Installation ==

1. Go to **Plugins → Add New**, search for "Product Video Solution for WooCommerce", then click **Install Now** and **Activate**. Or upload the `product-video-solution-for-woocommerce` folder to `/wp-content/plugins/`.
2. Make sure WooCommerce is active.
3. Go to **WooCommerce → Product Video** to choose the play icon, default playback options, position and spacing.
4. Edit a product, open **Product data → Product Video**, choose the video and update the product.
5. With an Elementor product template, you can also drag the **Product Video** widget wherever you want it.

== Frequently Asked Questions ==

= How do I add a video to a WooCommerce product? =

Edit the product, open the **Product Video** tab in the Product data box, choose a video from the Media Library or paste a YouTube, Vimeo or video file URL, then click Update.

= How do I show the product video after the product description? =

Go to **WooCommerce → Product Video** and set **Default position** to **After the product description**. You can also change the position for a single product in its Product Video tab.

= Does it work with Elementor and ShopBuilder? =

Yes. Drag the **Product Video** Elementor widget anywhere in your product template, or pick a preset position and the plugin adds the video after the matching ShopBuilder product widget.

= Can I add a YouTube video to a WooCommerce product? =

Yes. Choose **YouTube** as the video source and paste the link. The YouTube thumbnail is used automatically, or you can upload your own.

= Why does autoplay mute the video? =

Browsers only allow videos to autoplay with the sound off. When autoplay is on, the plugin starts the video muted so it plays reliably.

= Can I choose which frame is used as the thumbnail? =

Yes, for Media Library videos, and for external video files whose server allows it. Pause the preview on the frame you want and click **Use the current frame**. The frame is saved as an image in your Media Library.

= Will the video slow down my product page? =

No. Until the visitor clicks play, only a thumbnail image and a small icon are shown. The video player loads on click.

= Can I change the spacing around the video? =

Yes. **WooCommerce → Product Video → Spacing** has margin and padding for every side. In Elementor, the widget's Advanced tab works too.

= What happens to my videos if I uninstall the plugin? =

Uninstalling removes the plugin settings but keeps each product's video, so reinstalling brings them back.

== Screenshots ==

1. The Product Video tab on the product edit screen: video source, thumbnail, playback options and position.
2. Settings page: play icon style, colours and size, default playback, default position, and margin and padding.

== Changelog ==

= 1.1.0 =
* New: margin and padding settings for every side, with px, %, em, rem and vw units.
* New: "After the product description" position.
* Preset positions work on ShopBuilder (Elementor) product templates.
* First public release as Product Video Solution for WooCommerce.

= 1.0.0 =
* Initial private release.

== Upgrade Notice ==

= 1.1.0 =
First public release. Adds margin and padding controls and the "After the product description" position.
