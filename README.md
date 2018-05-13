# CiviCRM User

Operations on Drupal User entities based on a CiviCRM data source.
Useful when the process of creating users is not delegated to Drupal
(existing CiviCRM Contacts, frontend User registration disabled, ...).

Creates, updates or blocks Drupal Users from CiviCRM Contacts.

These operations are optionally based on a condition that can be
- a CiviCRM Tag applied to the Contact (e.g. "Has a Drupal user account")
- a CiviCRM Group (e.g. when a Contact belongs to a Group).

Conditions are recommended because CiviCRM allows several Contacts
to share the same email address, which is not the case (by default)
on Drupal.

When such conditions are met, adding or removing them triggers
the Drupal User operations.

Examples:

- Create CiviCRM Contact and assign a Tag: 
creates the Drupal User if it does not exist
or unblock it if it exists.
- Remove a Tag from a Contact:
blocks the Drupal User.

On Drupal 7, this was [delegated to Rules and CiviCRM Entity](https://wiki.civicrm.org/confluence/display/CRMDOC/Creating+a+Drupal+user+for+every+CiviCRM+contact).
At the time of writing, [CiviCRM Entity](https://www.drupal.org/project/civicrm_entity)
and [Rules](https://www.drupal.org/project/rules) for Drupal 8
do not cover this use case yet.

Further releases could just delegate to these modules.

## Roadmap

- Create User
- Update User
- Block User
- Set condition from the Drupal module configuration
- If a User is created under Drupal, reflect the condition in CiviCRM
- Logging of email addresses used for several Contacts that share the condition.