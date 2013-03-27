RULES_MODULE
============

How does it work?
-----------------
The most important thing, how this module is designed is, that rules are assigned to different groups.
That makes it possible that a guild member sees only rules that are ment for guild members, but not the rules ment for - for example - guests or moderators.

**Groups**
__________
There are 5 different groups:
* `admin`: The superadmin and all admins
* `mod`: all moderators
* `guild`: all guild members
* `member`: all guest channel members that were registered by !adduser <name>
* `all`: this includes all member that have less access (basicly people who got invited by !invite <name>)

*Note: It will always only get your highest rank. So a administrator, that is a guild member as well will only see rules that are assigned to the `admin` group.*

**Sign status**
_______________
There are 4 different sign states:
* `not signed`: has never signed the rules
* `need to resign`: has signed the rules, but at least one rule changed after that
* `has nothing to sign`: no rules are assign to his group
* `has signed`: he has signed the rules and no rules changed meanwhile

How to install?
---------------

**Requirements**
________________
There isn't much the RULES_MODULE requires. Basicly the requirements of Budabot.
The module was developed on Budabot v.3 RC5. It should work on any Budabot v.3.

**Get the files**
_________________
You have two choices how to get the files.
* Download the zip file from https://nodeload.github.com/Captank/RULES_MODULE/zip/master and unzip it into a directory named `RULES_MODULE`
* Clone it from github directly.

```sh
mkdir RULES_MODULE
git clone git://github.com/Captank/RULES_MODULE.git RULES_MODULE
```

**Include the files to your Budabot**
_____________________________________
There are two ways how you got Budabot installed.
Either you downloaded the zip file or you are using nightly builds.
* Downloaded zip:
Move the RULES_MODULE directory to <buda>/modules/
* Nighlty builds:
This is a bit more difficult, if you place the RULES_DIRECTORY to <buda>/modules/ next time you pull the repository the folder will get deleted.
So in first place you create a directory for your custom modules. In this example it will be named `proprietary`.
Open your Budabot directory and create `proprietary` in there.
Move the `RULES_MODULE` directory into `proprietary`.
Now open the <buda>/conf directory. The following steps you have to do for each config file for the bots you want to the RULES_MODULE.
Open the config file with a plain text editor.
Find the following part:

```php
  $vars['module_load_paths'] = array(
		'./modules'
	);
```

Change it to:

```php
  $vars['module_load_paths'] = array(
		'./modules',
  	'./proprietary',
	);
```
If your bot(s) are running, restart them now.

The commands
------------

Commands are split into two sections, user commands and administration commands.

**User commands**
_________________

* **!rules**
This command will show you all rules that are assigned to your group.
At the end of the rules will be the link to sign the rules.

* **!rules 'id'**
This command will show you a specific rule, IF it is assigned to your group. (Except, if you are mod or admin)

* **!rules search 'keywords'**
This command will show you all rules that contain any of the keywords in the rules title or rules text that are assigned to your group. (Except if you are mod or admin) Key words are separated by white spaces.

**Admin commands**
__________________
* **!signed characters|all**
If the parameter is 'all' is given it will show the sign status of all online players of the groups 'guild', 'mod' and 'admin' and all players if the the groups 'all' and 'member' that are in the private channel.
If you want it for specific players, simply write the names separated by white spaces (Example: !signed Name1 Name2 Name3)

* **!rulesadmin show [group|inactive] [long]**
Without any parameters (besides show) this command will show ALL rules. The rules text will be shortened to the next white space after 25 characters (not sure about the number yet)
If the parameter long is given as well, rules texts doesnt get shortened.
If a group (or 'inactive') is given, it will only show the rules that are assigned to the given group (or the inactive).

* **!rulesadmin add 'title' 'text'**
This command will create a new rule the first word will be the rules title, the rest the rules text. If you want more than one word as the title you need to surround it by single or double quotes (Example !rulesadmin add 'the title' a random and senseless text)

* **!rulesadmin edit 'id' groups|title|text [text]**
If the parameter is 'groups' the bot will send you a formular with the current assigned rules and links to assign to or remove from a group.
If the parameter is 'title' the text will be set as the new rules title. Same goes for 'text'

* **!rulesadmin rem 'id'**
This command actually just removes all relations to any groups.

Additional information
----------------------

**Behavior**
____________
You have to sign the rules for each character separatly.

Rules you have not signed yet, will spammed in a tell to you
* on log on: `admin`,`mod` and `guild`
* on private channel join: `member`,`all`

**Deleting rules**
__________________
Rules can not get deleted manually. You can mark them as inactive, which means that they are not assigned to any group. After some day (depending on how many you set up) they will get deleted automatically. Also, all changes to rules keep who changed it.
