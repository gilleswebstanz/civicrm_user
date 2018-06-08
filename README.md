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

## Configuration

Head to 'Configuration > People > CiviCRM User' 
(/admin/config/civicrm_user/settings).

#### Mandatory

- Pay extra attention the the Domain ID, if your are working with multiple
front-ends for CiviCRM (e.g. 2 Drupal sites).
- Configure at least one filter for the user creation (group or tag),
as importing all your CiviCRM contacts is probably not what you want and it 
will give you control later on if you want to block user accounts.
- Configure the desired operations on the users, the recommended settings
are at least 'create' and 'update' to preserve database integrity.

#### Optional

- Default Drupal username.
- Default Drupal roles.
- Set Drupal operations as read only: may be useful when you do not want users to edit
directly their accounts and, as a consequence, the CiviCRM contact data.

## Usage

- Once the configuration fits your needs, you may want to **preview** the processing
of the operations that will be executed. It will report potential errors like 
existing duplicate users (example: contact that are sharing the same email address) and will allow 
to dedupe some entities if you are coming from a legacy database.
- **Create** the queue.
- **Process** the queue.

## Roadmap

- Process queue with cron.
- Provide a list of potential duplicate users for the same queue, currently
the check is only based on existing users and users to be created.
- If a user is created under Drupal, reflect the condition in CiviCRM
(assign the tags or groups).
- Review a way to trigger the operations on a user by adding or 
removing a CiviCRM tag or group.
- Filter the queue preview by changes and/or errors (it can lead to time out with
large data sets).
