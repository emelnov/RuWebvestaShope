# Commerce Email

This module lets you react to various Drupal Commerce events to send emails to customers, administrators, or other interested parties. The emails use token replacement to include order details in their text, and the email sender uses an inline conditions element to govern whether or not the email should be sent for the given order / event.

## Setting the event priority for EmailEvent plugins

The default event priority is 0 for all EmailEvent plugins.
But it can be changed for plugins defined in custom modules by setting
the `priority` directly in the plugin annotations. And for plugins defined
in the commerce_email module using `hook_commerce_email_event_info_alter()`.
