# Pantheon Update Tools
## Before You Begin
The first thing that you need to do when using this repository is to make sure that your computer has a machine token that is used. The command you need to run should look something like this with your own machine token used:
```sh
terminus auth:login --machine-token=<machine_token>
```
More information on creating and using machine tokens can be found [here](https://pantheon.io/docs/machine-tokens).

## Initialize Script
Before you do anything in this repository, it is important that you run this following script to make sure that everything is set up properly.
```sh
php other/initialize.php
```
It is **strongly recommended** that you answer in the affirmative to letting it build the siteList. This will help to reduce hassle later. This script can only be run once (On subsequent runs, the script will do nothing).

## Before Running Any Other Scripts
If you didn't answer in yes to the question in the initialize script, you **must** run refreshSiteList.php script before any other scripts. This is because the siteList.txt file is built out by this script and this is the foundation for all of your scripts to run off of. From the root directory of the project, use this command:
```sh
php refreshSiteList.php
```
Keep in mind that this script takes a while (around 20 minutes) and should be run before each session of doing updates. I personally like to run it at the end of the day when I know that no one else will be running any updates so when I come in the next morning the list is up to date and I don't have to worry about waiting around for the list to update.

## Script Information (Top Level Scripts)
The following sections include some helpful information about the different scripts that have been made for your use. These scripts are stored in the top level of the directory to be accessible from the beginning.

### Startup
This is by far one of the most useful scripts. This script can draw from the automatically generated list of sites that is created with the refreshSiteList.php script (assuming the siteInfo/siteList.txt is up to date). This script then will backup (if opted to) and prepare an 'updates' environment for as many or as few sites as you would like. Other options this script includes is to invert the site order, skip sites, and/or manually enter the sites to update. To run this script, use:
```sh
php startup.php
```
When running the script follow the directions in the script and you can choose what path down the function fits your needs the best. One thing to note, this scripts' default location for you to run site updates is in an 'updates' multidev environment. If this environment doesn't exist, the script will create one on its own.

When doing many updates concurrently, I personally like to do them in batches of 5 sites per terminal window and have at least 10 sites going overall. But there is no reason not to do more than that at a time. This is done by opening multiple terminal windows and taking advantage of the skip functionality during setup. To get sites 6-10, select the auto-generate option, 10 sites, yes to skip items, and 5 to how many. This will update sites 6-10 from the siteInfo/siteList.txt.

Another way to update if you want to do many sites but only have two terminal windows is to use the same selection but invert the order of one of them. This would allow for the two scripts to work to deplete the same pool of sites, but make sure to skip sites or finish early when the two scripts meet in the middle to prevent the scripts from doing redundant work on the same sites.

### Finisher
This script is similar in scope to the startup function but works on the other end of the updates process. After *manually* updating the sites and checking them for errors following using the startup script, you can use the finisher.php script to auto commit code from the 'updates' environment to the live environment. To run this script, use:
```sh
php finisher.php
```
It should be noted that unless you are working in an alternate multidev environment without modifying this script or you don't want code to be committed at all, or committing changes manually set up, the manually selecting option should be left off. This will 'commit' sites with no changes but that is completely fine. This will just temporarily add the site to the siteInfo/siteSkip.txt file with the current date and time. This will be taken into account when using the refreshSiteList.php script and will adjust the order of the sites as if that date were the date of the last update.

If there is a site that you don't want to have the code committed on, there are two options. (Preferred Method) You can either set the connection of the 'updates' multidev environment to git and back to sftp to wipe code changes and then 'commit' the changes with the finisher.php script. (Alternate Method) Alternatively, you can go into the siteInfo/sitesToFinish.txt file and manually remove the site.

### Refresh Site List
As mentioned above, this is a critical script to run often. This script is the backbone of the startup.php script and by extension the finisher.php script. This script requires no user input and can be run and left to its' own devices. It is run with the following:
```sh
php refreshSiteList.php
```
It will go through each of the upstreams that get updated: Drupal 7, Drupal 8, and the Tribute Media D7 Base and determines first if they can be updated (Verifies that they are both not frozen and not sandboxed), The script then determines how recently the site was updated (by looking for the most recent instance of 'Update Modules' in the commit log). This list is ordered and the sites most recently updated are sent to the bottom.

As mentioned above, it is very important to run this script as often as at least once a day (between the last time someone did any work on sites and when you are running updates), even more often if more than one person is running updates concurrently. While it is technically possible to run the startup.php script for a few days after the refreshSiteList.php script was last run. This is not recommended because you will may run into issues with missing sites that are new to Pantheon or other people running updates that your siteInfo/siteList.txt isn't aware of.

## Script Information (In Other/)
These scripts are more utility than general use. These functions have their uses, but won't be used nearly as often as the top level scripts due to a more narrow focus.

### Core Updates
This is for use whenever there are core updates available for any of the currently used upstreams: Drupal 7, Drupal 8, and the Tribute Media D7 Base or when looking for sites that fail to cleanly update to the new upstream. To run this script, use:
```sh
php other/coreUpdates.php
```
When running this script, you will initially be asked to determine which upstream to update. The script will then pull down a list of all sites in that upstream, determine if they need updates to be run, and if they need updates, adds them to a list to have each site updated. If the update succeeds, the dashboard and live site will be opened for visual inspection. From here, if the site looks good, feel free to continue the Core Updates, or if the site looks bad, make a note of the site name in the siteInfo/failedUpstreams.txt file and roll back the live environment. If the site fails, it will be added to the siteInfo/failedUpstreams.txt file for your convenience.

One thing to keep in mind. When running normal site updates with startup.php, the sites with upstream updates available will be updated, but if they fail they will not be logged. Only the coreUpdates.php script will log the ones with errors.

### Views Replacement
When there is a speed filter fix needed for a product catalog (and there isn't a readily available sftp client) run this script with:
```sh
php other/viewsReplace.php
```
This will ask for the site that has the issue, and will then give the commands necessary to fix the speed filter assuming the TMD7 repository is stored here:
```sh
~/scripts/github/
```
Otherwise, you may need to modify the script or the location of your repository for convenience. Alternatively, you could just use an sftp client (recommended).

### Open Repository
This is a very simple script that only opens this github repository in your default browser. This is a quality of life thing more than anything else. This script is run with:
```sh
php other/openRepo.php
```

## Site Info Folder
This folder (siteInfo/) contains many documents that either include critical information for scripts to interact with or quality of life documents that make your life as a user simpler, or more convenient.

### Failed Upstreams
The siteInfo/failedUpstreams.txt file is a great place to store and look at all of the files that have failed their upstream updates in one way or another. After the other/coreUpdates.php script has been run, all (if any) sites that had conflicts will automatically have those sites added. You can also manually add sites to this file.

Just keep in mind, if you add a site to this file and you run the other/coreUpdates.php script again and the site gets updated, the site will be removed from the file, regardless of whether or not the site is actually updated correctly in regard to a visual inspection. Meaning, if the updates are applied and no conflicts appear, but the site breaks in some way, the site will still be removed. Long story short, always check the live site for issues after running the other/coreUpdates.php script.

### Site Ignore
The siteInfo/siteIgnore.txt file is for sites that can't be updated. refreshSiteList.php will look here and remove any site that is placed in the file from the update process. The only way to get sites in and out of this file is manually. So be careful when putting sites in this file that you don't forget about the causing them to be left without being updated for extended periods of time without reason.

### Site List
The siteInfo/siteList.txt file is the backbone of the entire repository. This file tells the auto-select functionality of the startup.php script which sites to select and what order to select them. This list shouldn't be manually edited. This file is updated by re-running the refreshSiteList.php script.

### Sites To Finish
The siteInfo/sitesToFinish.txt file is the file that the startup.php script writes to when the site has been prepped for updates. This file acts as a queue of sites that need (or will soon need to depending on whether or not site updates have been made yet) to have their code committed from the updates multidev environment to the live environment. This file should only be modified sparingly. As mentioned above, the only time the script should be accessed is when wanting to remove a site from the finishing queue.

### Site Skip
The siteInfo/siteSkip.txt file serves as a temporary holding ground for modifying the dates in which the siteInfo/siteList.txt uses for sorting the sites in their update order. By default, the refreshSiteList.php script will parse the sites' commit log and order the sites based on how recently they were updated. But when a site is included in the siteInfo/siteSkip.txt file, the date that is more recent between the two (commit log and siteInfo/siteSkip.txt) is selected and used in the sorting

This file can be accessed and modified both manually and automatically. As described above, sites will be added and removed automatically as needed when sites that have no code to commit are added to the siteInfo/siteSkip.txt file. This will get removed when the date displayed in the siteInfo/siteSkip.txt file is older than the actual update date.

### Update Notes
The siteInfo/updateNotes.txt file gives the ability to keep track of update notes for your sites. All adding and removing of comments from this file is done manually. To add a site, just add the site name to a new line (make sure that it is the machine that pantheon uses, not the display name) and follow it with the comment. After doing this, the comment will appear in the updates.php header when updating. 
