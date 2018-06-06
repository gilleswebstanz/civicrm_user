# CiviCRM User

Operations on Drupal _User_ entities based on a CiviCRM _Contact_ data source.
Useful when the process of creating users is not delegated to Drupal
(existing CiviCRM contacts, frontend user registration disabled, ...).

Creates, updates or blocks Drupal users from CiviCRM contacts.

These operations are based on filters:
- The CiviCRM domain id for the Drupal site
- 0 to multiple CiviCRM _Tags_ applied to the contact 
(e.g. "Has a Drupal user account")
- 0 to multiple CiviCRM _Groups_ (e.g. when a Contact belongs to Groups).

Filters are recommended because CiviCRM allows several contacts
to share the same email address, which is not the case (by default)
on Drupal.

Examples:

- Create or update a CiviCRM contact and assign a tag: 
creates the Drupal user if it does not exist
or unblock and update it if it exists.
- Remove a tag from a contact:
blocks the Drupal user.

The operations are processed by a queue that can be created
manually or via a cron.

On Drupal 7, this use case was 
[delegated to Rules and CiviCRM Entity](https://wiki.civicrm.org/confluence/display/CRMDOC/Creating+a+Drupal+user+for+every+CiviCRM+contact).
At the time of writing, [CiviCRM Entity](https://www.drupal.org/project/civicrm_entity)
and [Rules](https://www.drupal.org/project/rules) for Drupal 8
do not cover the use case yet.

Further releases could just delegate to these modules.

## Roadmap

- Set filters from the Drupal module configuration
- Create user
- Update user
- Block user
- Process queue manually
- Process queue with cron
- If a user is created under Drupal, reflect the condition in CiviCRM
(assign the tags or groups)
- Review a way to trigger the operations on a user by adding or 
removing a CiviCRM tag or group
- Logging of email addresses used for several contacts 
that are sharing the filters.
