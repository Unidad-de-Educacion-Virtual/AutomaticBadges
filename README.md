# Automatic Badges (local_automatic_badges)

Automatic Badges is a Moodle plugin that empowers course administrators and teachers to dynamically assign badges to users based on flexible rules and criteria.

## Requirements
- Moodle 4.5 or later (Requires version `2024100700` or newer).
- PHP 8.1 or newer.

## Capabilities
- Define rules per course or globally to issue badges.
- Assign badges based on activity grades, course completion, or forum posts.
- Apply bonus points natively depending on the criteria met.
- Extensive logging to keep track of all automatically issued badges via GDPR-compliant privacy structures.

## Installation
1. Log in to your Moodle site as an admin and go to **Site administration > Plugins > Install plugins**.
2. Upload the ZIP file with the plugin code.
3. Check the plugin validation report and finish the installation.
4. Alternatively, you can copy the `automatic_badges` directory into the `local/` directory of your Moodle installation and run the upgrade script via `php admin/cli/upgrade.php` or by visiting the Moodle administration page.

## Usage
Once installed, site administrators can enable the plugin globally or per-course from the plugin's main configuration page.
Teachers will find a new section within their course settings to define the automatic badge rules.

## License
This plugin is licensed under the [GNU General Public License v3 or later](http://www.gnu.org/licenses/gpl.html).

### Authors
* Daniela Alexandra Patiño Dávila
* Cristian Julian Lamus Lamus
