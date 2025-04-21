Ignis Plugin Setup Guide
Overview
The Ignis Plugin enhances the Madara theme with features like points, currency, store, airdrops, and user engagement tools. This guide covers installation, activation, and configuration.
Requirements

WordPress 5.8 or higher
WP Manga (Madara-Core) plugin
PHP 7.4 or higher
MySQL 5.7 or higher

Installation

Download the Plugin

Obtain the ignis-plugin.zip from the official repository or Telegram channel (@IgnisReborn).


Upload to WordPress

Navigate to Plugins > Add New > Upload Plugin in your WordPress admin panel.
Select ignis-plugin.zip and click Install Now.


Activate the Plugin

After installation, click Activate Plugin.
Ensure the WP Manga plugin is active, or you’ll see a dependency error.



Configuration

Access Ignis Control Center

Go to Ignis Control in the WordPress admin menu.
The dashboard displays stats (points distributed, active users).


General Settings

Navigate to Settings under Ignis Control.
Enable/disable modules (Points, Currency, Store, etc.).
Enter private keys for Shortener and Chatroom modules if applicable.
Click Save Changes.


Points Module Configuration

Go to Points under Ignis Control.
Set points rules (e.g., 2 points for first chapter read, 1 for comments).
Configure daily limits and lifetime caps.
Customize toast and milestone messages.
Save settings.


Other Modules

Configure each module (e.g., Currency, Store) via their respective submenus.
Refer to module-specific documentation in docs/api.md.



Shortcodes

[ignis_scoreboard limit="10"]: Displays the top users by points with emoji rankings.

Troubleshooting

Dependency Error: Ensure WP Manga is installed and activated.
Points Not Awarded: Check if the Points Module is enabled and rules are set.
API Issues: Verify private keys for Shortener/Chatroom modules.
Contact support via Telegram (@IgnisReborn).

Next Steps

Explore the REST API for Shortener and Chatroom integrations (docs/api.md).
Test the plugin using PHPUnit (tests/ folder).
Translate the plugin by editing languages/ignis-plugin-en_US.po.

