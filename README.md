# AccessCode File Downloader

Easily protect and distribute your files with an access code in WordPress.  
Users must enter a valid code to download files, ensuring secure and controlled file distribution. Perfect for private resources, premium content, or confidential documents.

---

## Features

- Create a **Custom Post Type** for secure downloads.
- Assign **access codes** and file URLs to each download.
- Generate a **download button shortcode** for embedding anywhere.
- Modal popup for **access code input** before download.
- AJAX-based **code validation** for a smooth user experience.
- Temporary secure download links with **token system**.
- Admin columns showing **Post ID** and **Shortcode**, with copy-to-clipboard functionality.
- Fully **responsive** and lightweight, no extra dependencies.

---

## Installation

1. Download the plugin ZIP or clone this repository.  
2. Upload to the `/wp-content/plugins/` directory.  
3. Activate the plugin through the WordPress admin dashboard.  
4. Create **Secure Downloads** from the admin menu.  
5. Add the **access code** and **file URL** for each download.  
6. Use the shortcode `[acfd_button id="POST_ID" text="Download File"]` to display the download button anywhere on your site.

---

## Usage

### Shortcode

```php
[acfd_button id="POST_ID" text="Download File" align="center"]
