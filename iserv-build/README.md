# Building Docker Image

This folder contains everything needed to build the docker image. Run `./build.sh` to build it.
This depends on you being logged into the `git.iserv.eu` docker registry. (`docker login git.iserv.eu:443 -u <dein.name>`)
(Caution: Do not login on your VM where others might have access to your session.)

## How it works

- The image is based on the official Nextcloud Docker [nextcloud/docker](https://github.com/nextcloud/docker).
- The source code in the image is then modified by [a number of patches or added files disabling or altering the functionality](#documentation-of-patches--customizations).
  - These patches are located in `./source/patches/`.
  - There is exactly one patch file for every changed file.
  - Completely new files are located in `./source/added/`.
- The patches were created based on [nextcloud/server](https://github.com/nextcloud/server) and [nextcloud/activity](https://github.com/nextcloud/activity).

When the Docker image is being built, in the first stage, `clone_and_apply_patches.sh` applies patches and copies the added files into a freshly checked out working copy of Nextcloud.
Then `create_combined_patches.sh` builds the JavaScript assets and creates temporary (binary) patch files representing all changes.
These binary patches will be applied to the production code in the second build stage.

## How to create new patches

### 1. Clone NC and apply existing patches
  - `./docker/cloudfiles/source/clone_and_apply_patches.sh 28.0.9 ~/nextcloud-server`
    - arg1: Nextcloud version that is currently used for the image
      - you can find the current version in the [Dockerfile](Dockerfile)
    - arg2: destination path for the repo that gets temporarily checked out.

  - The existing patches should have been applied correctly, and the `~/nextcloud-server` repo shows several changes/untracked files when calling `git status`.

### 2. Code your patch 
- Do your modifications **without committing anything** to the nextcloud project.
- Remember to add your changes to the [bottom of this page](#documentation-of-patches--customizations).

### 3. Extract patches
- come back to this project and call
- `./docker/cloudfiles/source/extract_patches.sh ~/nextcloud-server`
  - This will create or update the files in `./source/added/` and `./source/patches/`.

### 4. Building
**A) Building locally**

You can build locally and transfer the tarball to the VM like this:
- Run `./docker/cloudfiles/build.sh`
- transfer the image to the dev vm

If you are on another architecture than linux/x86_64, then the image ID comparison in iservchk will fail, bc docker somehow generates a different one:
- make sure you have the newest tarball in data folder then build the image:
- `iservchk cloudfiles`
  - it will say that it failed on updating and importing the image (bc of id mismatch cant get repaired)
  - it should start the container though regardless
- `docker inspect --format='{{.Id}}' iserv/cloudfiles:latest`
  - get the image ID that the target architecture references
- put it into [image.id](../../data/image.id)
- now iservchk won't complain anymore

**B) Building on the vm**

- ssh into your vm
- remove running containers created from cloudfiles image
  ```bash
  RUNNING_CONTAINERS=$(docker ps --filter "ancestor=iserv/cloudfiles" -q)
  if [ -n "$RUNNING_CONTAINERS" ] ; then
    docker rm -f $RUNNING_CONTAINERS
  fi
  ```
- remove image<br>
`docker image rm iserv/cloudfiles`
- Ensure that your automatic deployment (e.g. rsync) does not overwrite the image created in the next step.
- run build.sh <br>
`./docker/cloudfiles/build.sh`
- if building the image fails, and you get a sigint then you probably need more memory [-> OpenNebula](https://cloud0.iserv.eu/), 16gb should be sufficient

### 5. installing
- `iservmake iservinstall`<br>
  (not necessary on subsequent builds)
- `iservchk`

### 6. Comitting
- if image was built on remote, download the new `image.id` and `image.tar.xz` first
  ```bash
  scp VM-name:/root/git/docker-cloudfiles/data/image.id iserv/iserv4/docker-cloudfiles/data/
  scp VM-name:/root/git/docker-cloudfiles/data/image.tar.xz iserv/iserv4/docker-cloudfiles/data/
  ```
- commit build artifacts
  - `data/image.id`
  - `data/image.tar.xz`
  - `./source/added/`
  - `./source/patches/`.

If patches need to be created for Nextcloud apps, corresponding changes must be made to scripts `clone_and_apply_patches.sh` , `create_combined_patches.sh` , and `extract_patches.sh`.

### Patching a new app

In case the patches need to be applied to an app that has not been patched yet, make sure to update the following scripts as well.

* Dockerfile
* clone_and_apply_patches.sh
* create_combined_patches.sh
* extract_patches.sh

## How to upgrade

- `./docker/cloudfiles/source/clone_and_apply_patches.sh 28.0.9 ~/nextcloud-server`
  - call this using the new version number you want to upgrade to
- Check if all patches have been applied successfully.

### handle failed patches

1. Move the affected patch file out of `./source/patches`.
2. Run `clone_and_apply_patches.sh 28.0.9 ~/nextcloud-server` again.
3. Repeat steps 1 and 2 until all remaining patches have been successfully applied.
4. Manually apply the changes of the moved patch files to the affected files in the working copy `~/nextcloud-server`.
5. Check if (none-)core apps need to be upgraded.
6. Run `extract_patches.sh ~/nextcloud-server` to re-generate the patches.

Once all patches can be applied to the new version

* update the version number in the Dockerfile,
* add/commit the patches in case there have been changes,
* Run `build.sh` and commit `data/image.id` and `data/image.tar.xz`.

## Running the image

This image is specifically tailored towards IServ's use-case. It can be embedded into an IServ as an iframe. It fetches users, authenticate them with IServ and allows other IServ modules to access its data in a secure way. 
This means some volumes and environment variables that are required may not be useful to everyone, like `LDAP_BASE_DN` or `/ldap_pass.txt`.

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
- Added config option for SAML ForceAuthn #83117
  - apps/user_saml/lib/Controller/SAMLController.php

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
