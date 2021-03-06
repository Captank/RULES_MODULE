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

**Automated install on Windows**
_________________________________
*(Note: Even if I mention this first, read the other parts of the installation steps, too, for better understanding)*

For Windows I have prepared an installation tool, just make sure that your files are within the Budabot directory,
then double click the `install.bat` (or run it from command line) and follow the instructions.

That installation tool can also be used to just modify the later created configuration files (for example, when you create a new bot) for using modules in the `proprietary` directory.

*It will also care itself about the differences between Budabot v3.0 and v3.1*


**Include the files to your Budabot manually**
_____________________________________
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
		'./proprietary'
	);
```
**Budabot v3.0 vs. Budabot v3.1**
_________________________________
Budabot v3.1 uses namespaces, Budabot v3.0 does not. The reprository code is now for Version 3.1
If you are running v3.0, there is an easy way to make it compatible, just comment out
line #6.
Thats the original line:

```php
	<?php
	/*DELETE_FOR_V3.0*/
	namespace Budabot\User\Modules;

	use \Budabot\Core\AccessManager;
	use \Budabot\Core\Modules\AltsController;
	/*DELETE_FOR_V3.0*/
	
	/**
 	* Author:
 	*  - Captank (RK2)
```

And here it's modified:

```php
	<?php
	
	/**
 	* Author:
 	*  - Captank (RK2)
```

***If your bot(s) are running, restart them now.***

The commands
------------

Commands are split into two sections, user commands and administration commands.

**User commands**
_________________

* **!rules**
This command will show you all rules that are assigned to your group.
At the end of the rules will be the link to sign the rules.

* **!rules changes**
This command shows you all rules that are assigned to your group and you have not signed yet.

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

* **!rulesadmin spam**
This command will spam the rules that are not signed to each online member/people that are in private channel.

* **!rulesadmin add 'title' 'text'**
This command will create a new rule the first word will be the rules title, the rest the rules text. If you want more than one word as the title you need to surround it by single or double quotes (Example !rulesadmin add 'the title' a random and senseless text)

* **!rulesadmin edit 'id' groups|title|text [text]**
If the parameter is 'groups' the bot will send you a formular with the current assigned rules and links to assign to or remove from a group.
If the parameter is 'title' the text will be set as the new rules title. Same goes for 'text'

* **!rulesadmin rem 'id'**
This command actually just removes all relations to any groups.

Settings
--------

* **maxdays**
Default: 30 Options: 5, 10, 20, 30, 40 - Inactive rules will be deleted after a specific time, this setting defines after how many days without touching the rule, it will be deleted.

* **private_rules**
Default: false Options: true, false - If this setting is active (true), instead of spamming the rules directly in organization/private channel, a link to spamm them as tell will be spammed. The affected commands are: '!rules' and '!rules changes'

* **sign_alts**
Default: true Options: true, false - If this setting is active (true), rules will be signed for all validated alts if signer is either main or validated alt.

Additional information
----------------------

**Behavior**
____________

Rules you have not signed yet, will spammed in a tell to you
* on log on: `admin`,`mod` and `guild`
* on private channel join: `member`,`all`

**Deleting rules**
__________________
Rules can not get deleted manually. You can mark them as inactive, which means that they are not assigned to any group. After some day (depending on how many you set up) they will get deleted automatically. Also, all changes to rules keep who changed it.
