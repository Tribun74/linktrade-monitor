=== Linktrade Monitor ===
Contributors: 3task
Tags: backlink, backlink monitor, link exchange, link building, backlink checker
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A backlink monitor that tracks BOTH sides of link exchanges. Check backlinks, analyze link health and detect when partners remove their links.

== Description ==

**Linktrade Monitor** is the backlink monitor built specifically for **link exchange management** in WordPress. Unlike other backlink checker tools, it tracks YOUR links to partners AND their links back to you - giving you a complete backlink analysis from inside your WordPress dashboard.

= Why Linktrade Monitor? =

Most backlink checker tools only track incoming links. But if you do **link exchanges** or reciprocal linking, you need to know:

* Is your partner's link still online?
* Is YOUR link to them still required?
* Are you being treated fairly?

**Linktrade Monitor answers all these questions with its Fairness Score.**

= Key Features =

* **Link Exchange Tracking** - Monitor both sides of link exchanges
* **Fairness Score** - Know when partners remove their links
* **Three Link Categories** - Track exchanges, paid links, and free backlinks separately
* **Instant Check on Add** - Every new link is verified immediately
* **Automatic Checking** - Monthly automated link verification (once a month)
* **Exchange Duration Tracking** - Track start dates and expiration for time-limited exchanges
* **HTTP Status Monitoring** - Track 200, 301, 404, and other status codes
* **nofollow/noindex Detection** - Get warned about link attribute changes
* **Email Notifications** - Receive alerts for expiring or problematic links
* **Domain Rating Tracking** - Store DR values for all your link partners
* **Partner Contact Management** - Keep partner emails organized
* **Expiration Reminders** - Never miss a paid link renewal
* **GDPR Friendly** - All data stays on your server

= Perfect For =

* **SEO professionals** managing link building campaigns and backlink monitoring
* **Website owners** doing link exchanges, reciprocal linking or guest posting
* **Agencies** tracking client backlinks and partnerships
* **Bloggers** who trade links with other bloggers and need to check backlinks regularly
* Anyone who wants to **monitor backlinks** and protect their link investments

= What Makes Us Different? =

| Feature | Linktrade Monitor | Other Tools |
|---------|-------------------|-------------|
| Track incoming backlinks | Yes | Yes |
| Track YOUR outgoing links | **Yes** | No |
| Fairness Score | **Yes** | No |
| Link Exchange Management | **Yes** | No |
| Paid Link Expiration | **Yes** | No |
| Partner Categories | **Yes** | No |

= Looking for More? =

**Linktrade Monitor Pro** takes link exchange management to the next level with 10+ advanced features:

* **Project Management** - Track links across multiple websites from one dashboard
* **ROI Tracking & Analytics** - Calculate cost, value, and return on your link investments
* **Anchor Text Analysis** - Monitor anchor text distribution across all your links
* **Webhook & Slack Notifications** - Get instant alerts via Slack or custom webhooks
* **Configurable Check Frequency** - Check links hourly, daily, or weekly instead of monthly
* **On-Demand Checking** - Check all links instantly with one click
* **Tags & Link Organization** - Organize links with custom tags and categories
* **Sitemap Picker** - Select URLs from your sitemap when adding new links
* **Unlimited Links** - No restrictions on the number of tracked links
* **German Language Support** - Full German translation included

[Learn more about Linktrade Monitor Pro](https://www.3task.de/linktrade-monitor-pro/)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/linktrade-monitor/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Linktrade' in your admin menu
4. Start adding your backlinks!

== Frequently Asked Questions ==

= How often are links checked? =

Links are checked instantly when you add them, plus automatic checks once a month.

= What does the Fairness Score mean? =

The Fairness Score shows if both sides of a link exchange are holding up their end:

* **100%** - Both links are online and healthy
* **60%** - Your link is dofollow, partner's is nofollow
* **50%** - Both links are offline
* **0%** - Your link is online, but partner removed theirs

= Is my data secure? =

Yes! All your link data is stored in your own WordPress database. We don't send any data to external servers. GDPR friendly.

= Can I track nofollow links? =

Absolutely. Linktrade Monitor detects nofollow, noindex, and sponsored attributes and will alert you if a link changes.

= What's the difference between Exchange, Paid, and Free links? =

* **Exchange** - You link to them, they link to you (tracked with Fairness Score)
* **Paid** - You pay for the backlink (tracked with expiration reminders)
* **Free** - Guest posts, mentions, directories (no reciprocal tracking needed)

= Does it work with other SEO plugins? =

Yes! Linktrade Monitor works alongside Yoast SEO, Rank Math, AIOSEO, and any other SEO plugin.

= Is there a Pro version? =

Yes! Linktrade Monitor Pro adds project management, ROI tracking, anchor text analysis, webhook notifications, configurable check frequency, and more. Visit [3task.de/linktrade-monitor-pro](https://www.3task.de/linktrade-monitor-pro/) for details.

== Screenshots ==

1. Dashboard - Overview of all your links and their status
2. All Links - Complete list with filtering and search
3. Fairness Tracker - Monitor link exchange reciprocity
4. Add New Link - Simple form to track new backlinks

== Changelog ==

= 1.3.1 =
* New: Compact 2-column form layout - reduces scrolling by 60%
* New: Side-by-side Incoming/Outgoing link sections with visual indicators
* New: 3-column partner info row for better space usage
* Improved: Visual arrows (← / →) show link direction clearly
* Improved: Color-coded columns (green for incoming, blue for outgoing)
* Improved: Responsive layout adapts to tablet and mobile screens

= 1.3.0 =
* New: Link Health Score - visual 0-100 score showing overall link quality at a glance
* New: CSV Export - download all your links as a CSV file for backup or analysis
* New: CSV Import - bulk import links from CSV files with duplicate detection
* New: Import/Export tab with complete field documentation
* Improved: Health Score calculation based on status, attributes, DR, age, and fairness
* Improved: Table now shows Health Score column for quick quality assessment

= 1.2.0 =
* New: Complete admin interface redesign with 3task Plugin Design System
* New: Animated gradient header with modern styling
* New: Tab navigation with animated underline effects
* New: Stats cards with hover animations and gradient accents
* New: Status badges with pulse animation for online links
* New: Modal animations for smoother user experience
* Improved: Category tags with gradient backgrounds
* Improved: Fairness score visualization with animated bars
* Improved: Form sections with better visual hierarchy
* Improved: Responsive design for all screen sizes
* Improved: Table styling with hover effects

= 1.1.2 =
* Fixed: Fairness Score now includes DR comparison in calculation
* Improved: Fairness is recalculated when DR values are changed
* New: Fairness reflects value imbalance when your DR is higher than partner's DR

= 1.1.1 =
* New: Start date field moved to top of form for better workflow
* New: "My DR" field per link for accurate DR comparison across multiple projects
* New: DR comparison column in Fairness tab shows Partner DR vs My DR with difference indicator
* New: Backlink anchor text field for reciprocal links
* Improved: Form layout with side-by-side DR fields (Partner DR | My DR)
* Improved: Visual DR difference indicators (+green for benefit, -red for partner benefit)

= 1.1.0 =
* New: Exchange start date tracking - know when each link exchange began
* New: Expiration date support for time-limited exchanges (e.g. 1 year agreements)
* New: Visual expiration indicators in link overview (expired, expiring soon)
* Improved: Link overview now shows start date and expiration status

= 1.0.0 =
* Initial release
* Link tracking for exchanges, paid, and free links
* Automatic monthly link checking
* HTTP status, nofollow, noindex detection
* Fairness score for link exchanges
* Email notifications for expiring links
* Domain rating tracking
* Partner contact management
* Multi-language support (English, German)

== Upgrade Notice ==

= 1.3.1 =
Compact form layout! Add new links with 60% less scrolling. Side-by-side incoming/outgoing columns make link relationships crystal clear.

= 1.3.0 =
New Link Health Score shows link quality at a glance! Plus CSV import/export for easy backup and migration.

= 1.2.0 =
Major visual update! New admin interface with animated gradient header, modern stats cards, and improved user experience. All functionality remains the same.

= 1.1.2 =
Fairness Score now properly reflects DR imbalance. Update recommended for accurate fairness tracking.

= 1.1.1 =
New: Track your own DR per link for accurate fairness comparison across multiple projects. Improved form workflow.

= 1.1.0 =
New: Track exchange start dates and expiration for time-limited link agreements.

= 1.0.0 =
First stable release. Start tracking your backlinks and link exchanges today!
