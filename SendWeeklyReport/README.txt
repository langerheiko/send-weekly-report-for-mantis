SendWeeklyReport-Plugin for MantisBT
(c) by eCola GmbH, Hannover - Heiko Schneider-Lange, Feb. 2013
contact: hsl [at] ecola [dot] com; http://www.lebensmittel.de 

This plugin makes it easier to send a report with open and done entries to 
specified users.



INSTALLATION
------------
Put the ./SendWeeklyReport folder into the MantisBT-Plugin-Folder. Take care
that your web-server user/group has access to the folder.

Install the plugin using the MantisBT manage_plugin_page.php. 

Setup a cronjob that sends 
/path/to/mantis/plugins/SendWeeklyReport/core/send_weekly_report.php regularly.


USAGE
-----
Use the plugins config page to define the users, that should get the email in 
german or english language.


REMARKS
-------
MantisBT does not support HTML emails by default. To send the report with tables
I wanted to have HTML emails. So I defined a function "plugin_email_send". It's
the same than function "email_send" from email_api.php except that it supports
html body.

I have tested this plugin with MantisBT 1.2.14 only. That's why it registers
with this version or above only. Feel free to edit the $this_requires value in
SendWeeklyReport.php file to install it on lower versions.
