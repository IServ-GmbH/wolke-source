# Building Docker Image

This folder contains everything needed to build the docker image. Run `./build.sh` to build it.

## How it works

The image is based on the official Nextcloud Docker [nextcloud/docker](https://github.com/nextcloud/docker).
The source code in the image is then modified by [a number of patches or added files disabling or altering the functionality](README.md#Documentation of patches).
These patches are located in `./source/patches/`.
There is exactly one patch file for every changed file.
Completely new files are located in `./source/added/`.
The patches were created based on [nextcloud/server](https://github.com/nextcloud/server) and [nextcloud/activity](https://github.com/nextcloud/activity).

When the Docker image is being built, in the first stage, `clone_and_apply_patches.sh` applies patches and copies the added files into a freshly checked out working copy of the Nextcloud version to be built.
Then `create_combined_patches.sh` builds the JavaScript assets and creates temporary (binary) patch files representing all changes.
These binary patches will be applied to the production code in the second build stage.

## How to create new patches

Call `clone_and_apply_patches.sh 27.1.8 ~/nextcloud-server` from this project's root directory.
The arguments are the Nextcloud version that is currently used for the image and a destination path for the checked-out repository.
The checked-out repository will be cloned to a directory called nextcloud-server in /home/<username> directory.
The existing patches should have been applied correctly, and the checked-out code shows several changes/untracked files when calling `git status`.

Do your modifications **without committing anything** to the nextcloud project.
Remember to add your changes to the [bottom of this page](#documentation-of-patches--customizations).

Then come back to this project and call `extract_patches.sh ~/nextcloud-server` in the project root directory.
This will create or update the files in `./source/added/` and `./source/patches/`.

Run `build.sh` and commit `data/image.id` and `data/image.tar.xz`, as well as the changes in `./source/added/` and `./source/patches/`.

If patches need to be created for Nextcloud apps, corresponding changes must be made to scripts `clone_and_apply_patches.sh` , `create_combined_patches.sh` , and `extract_patches.sh`.

### Patching a new app

In case the patches need to be applied to an app that has not been patched yet, make sure to update the following scripts as well.

* Dockerfile
* clone_and_apply_patches.sh
* create_combined_patches.sh
* extract_patches.sh

## How to upgrade

Call `clone_and_apply_patches.sh 27.1.8 ~/nextcloud-server` (using the new version number you want to upgrade to).

Check if all patches have been applied successfully.

If a patch has failed:
1. Move the affected patch file out of `./source/patches`.
2. Run `clone_and_apply_patches.sh 27.1.8 ~/nextcloud-server` again.
3. Repeat steps 1 and 2 until all remaining patches have been successfully applied.
4. Manually apply the changes of the moved patch files to the affected files in the working copy `~/nextcloud-server`.
5. Check if (none-)core apps need to be upgraded.
6. Run `extract_patches.sh ~/nextcloud-server` to re-generate the patches.

Once all patches can be applied to the new version

* update the version number in the Dockerfile,
* add/commit the patches in case there have been changes,
* Run `build.sh` and commit `data/image.id` and `data/image.tar.xz`.

## Running the image

This image is specifically tailored towards IServ's use-case. It can be embedded into an IServ as an iframe, gets users and authenticating them with IServ and allows other IServ modules to access the data in a secure way. This means some volumes and environment variables that are required may not be useful to everyone, like `LDAP_BASE_DN` or `/ldap_pass.txt`.

Also, to avoid storing the source code on the host in a volume, we're using `tmpfs` for the whole `/var/www/html` folder and handling installation/updates/restarts in our entrypoint script.

All variables documented [here](https://github.com/nextcloud/docker/blob/20327851c8d9f7b40606844dfdccef5ee2230355/README.md#auto-configuration-via-environment-variables) are available in addition to:
* `ISERV_DOMAIN`: used to set csp to allow iframe embed
* `LDAP_BASE_DN`: used for ldap config

The following files/volumes are required by this image:
- `/version`: used by our entryscript to manage updates
- `/var/log/cloudfiles`: if using our `iserv.config.php`, logs will be saved here.
- `/sp_certificate.pem`
- `/sp_key.pem`
- `/idp_certificate.pem`
- `/ldap_pass.txt`

If using `tmpfs` for the `/var/www/html` folder, as mentioned earlier, the `NEXTCLOUD_DATA_DIR` environment variable also has to be set to a volume-mounted persisted path.

## Documentation of patches / customizations

### Backend

- Hide contacts menu and disable related routes. #52541
  - core/routes.php
  - core/templates/layout.user.php
- Remove unneeded options and information from settings page. Re-route default-endpoint to security endpoint. Clicking profile image -> settings should not lead to an empty page. #52547
  - apps/dav/appinfo/info.xml
  - apps/federatedfilesharing/appinfo/info.xml
  - apps/settings/appinfo/info.xml
  - apps/settings/appinfo/routes.php
  - apps/activity/appinfo/info.xml
- Remove several unneeded stuff from the sidebar of the activity tab. #52547
  - apps/dav/appinfo/info.xml
- Disable sending mails when you share a file with a note to a specific user. #54341
  - lib/private/Mail/Mailer.php 
- Deactivate unneeded background cron jobs. #54341
  - apps/activity/appinfo/info.xml
- Always display name of user that shared a file, even if it's shared publicly. #52635
  - apps/files_sharing/lib/DefaultPublicShareTemplateProvider.php 
- Remove settings for office integration. #70070
  - apps/richdocuments/appinfo/info.xml
- Desable app passwords for cloudsafe #81355
  - apps/settings/lib/Settings/Personal/Security/Authtokens.php
  - apps/settings/lib/Controller/AuthSettingsController.php
- Rename Nextcloud Office to IServ Office #81827
  - apps/richdocuments/lib/Service/CapabilitiesService.php

### Frontend

- Disabled "Allow edit" menu entry when publicly sharing files that cannot be edited in the browser. #54278
  - apps/files_sharing/src/views/SharingDetailsTab.vue
  - Hide "Allow edit", "Allow delete" for folders
- Hide the description text with links to Nextcloud's documentation on top of the theme/accessibility settings page. #52676
  - apps/theming/src/UserThemes.vue
- Remove link to Nextcloud's WebDAV documentation when opening Files - File settings. #52676
  - apps/files/src/views/Settings.vue
- Remove Pop-Up for quick select of editing rights because we could not only patch a certain entry (custom permissions) of the Pop-Up.
  - apps/files_sharing/src/components/SharingEntry.vue
  - apps/files_sharing/src/components/SharingEntryLink.vue
- Remove namings of Nextcloud from office integration #70070
  - apps/richdocuments/src/view/Office.vue
  - apps/richdocuments/src/document.js
- Add internet links as files. #70071
  - apps/files_linkeditor/src/views/Editor.svelte
- Added configuration option to disable download buttons. #75297
  - iconf/etc/iserv/docker-cloudfiles/iserv.config.php/00docker-cloudfiles.sh
  - lib/private/Template/JSConfigHelper.php
  - apps/files/src/components/FilesListTableHeaderActions.vue
  - apps/files/src/components/FileEntry/FileEntryActions.vue
  - apps/files_versions/src/components/Version.vue
  - apps/viewer/src/views/Viewer.vue
- Added option to filter share targets to local users. #81450
  - lib/private/Template/JSConfigHelper.php
  - apps/files_sharing/src/components/SharingInput.vue
- Hide options to alter background color and images on the theme settings page. #81451
  - apps/theming/src/UserThemes.vue
- Rename Nextcloud Office to IServ Office #81827
  - apps/richdocuments/src/view/Office.vue
  - apps/richdocuments/src/components/AdminSettings.vue
  - core/src/components/setup/RecommendedApps.vue.patch
  - apps/richdocuments/cypress/e2e/open.spec.js

### Theme

- `theme/core/css/server.css` contains some further customizations
