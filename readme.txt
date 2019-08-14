=== Advanced Custom Fields: Polyline Generator Field ===
Contributors: Aly Richardson
Tags: PLUGIN_TAGS
Requires at least: 3.6.0
Tested up to: 4.9.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A custom field type for Advanced Custom Fields to add a dynamically-generated polyline array using the Google Directions API

== Description ==

Welcome to the Advanced Custom Fields Polyline Generator repository on Github. This add-in for ACF allows a user to request directions data from Google Maps and easily cache the requested data as a field type. The direct request is stored as it is received and can be passed to the front-end for manipulation and for use in JavaScript-embedded Google Map applications.

How to use:

1. Input your Google Maps API Key in the ACF Polyline Options.
2. Add the Polyline Generator Field to an ACF Custom Field Group.
3. Provide start and end coordinates, and any waypoints.
4. Click "Generate" to send your request.

* Note: Only the Generator field is stored (not the coordinates themselves), so be sure to always click 'Generate' after adding your coordinates, and to Update the post/page to save your changes. On subsequent page loads, the coordinates are regenerated using the Generated data.

Future Ideas:

- Add a default limit to number of waypoints, with an option to remove the limit (since Google limits them for low-tiered users)
- Give option for type of data sent to front-end from ACF (steps vs. polyline, etc)
- Add back-end embedded maps to preview coordinate placement
- Store coordinates as well, in case the user forgets to Generate the line or wants to save progress

> Created using: https://github.com/AdvancedCustomFields/acf-field-type-template

= Compatibility =

This ACF field type is compatible with:
* ACF 5
* ACF 4

== Installation ==

1. Copy the `acf-polyline` folder into your `wp-content/plugins` folder
2. Activate the Polyline Generator plugin via the plugins admin page
3. Create a new field via ACF and select the Polyline Generator type
4. Read the description above for usage instructions

== Changelog ==

= 1.0.0 =
* Initial Release.