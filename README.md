# Hanja WordPress Plugin

Current version: **0.2**

This plugin embeds the Hanja Proficiency Test React application into WordPress. It includes a shortcode to show a login form and stores each user's score.

### Usage

1. Upload the `hanja-plugin` folder to your `wp-content/plugins` directory.
2. Activate **Hanja Test Plugin** from the Plugins page.
3. Place `[hanja_register]` on a page where users will sign in or register.
4. Use `[hanja_test]` on the page that displays the test.
5. Admins can review results under **Hanja Results** in the dashboard.

The plugin automatically loads Tailwind CSS and Google fonts so the interface looks the same as the standalone app.

When you modify the plugin code, remember to increase the version number in `hanja-plugin.php` so WordPress recognizes the update.
