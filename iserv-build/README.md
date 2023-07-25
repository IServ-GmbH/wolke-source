# Building Docker Image

This folder contains everything needed to build the docker image. Run `./build.sh` to build it.

## How it works

The Image is based on the official Nextcloud Docker [nextcloud/docker](https://github.com/nextcloud/docker).
The source code in the image is then modified by [a number of patches disabling or altering the functionality](README.md#Documentation of patches).
These patches are located in `./adds/patches/`.\
Patches that affect the generated JS are generated automatically by the scripts in `./build-steps` and are located in `./adds/patches/generated/`. The patches to the JS source-code pre-compilation are located in `./build-steps/patches`.

The patches were created based on [nextcloud/server](https://github.com/nextcloud/server) and [nextcloud/activity](https://github.com/nextcloud/activity).

## How to get the source code itself

To get the source code found in this repo:

```shell
./build.sh
docker create --name source-image iserv/cloudfiles
mkdir source
docker cp source-image:/usr/src/nextcloud/. source/
docker rm source-image
```

The source-code will be located in the `source` folder.

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

- `remove_contactsmenu.patch`
   Hides contacts menu and disables related routes. #52541

- `cleanup_settings.patch`
   Removes unneeded options and information from settings page. Re-routes default-endpoint to security endpoint. Clicking profile image -> settings should not lead to an empty page. #52547

- `cleanup_activity.patch`
   Removes several unneeded stuff from the sidebar of the activity tab. #52547

- `deactivate_send_mails.patch`
   Disables sending mails when you share a file with a note to a specific user. #54341

- `deactivate_activity_jobs.patch`
   Deactivates unneeded background cron jobs. #54341

- `show_share_owner.patch`
   Always display name of user that shared a file, even if it's shared publicly. #52635

### Frontend

- `01_limit_link_share_edit.patch`
  Hides "Allow edit" menu entry when publicly sharing files that cannot be edited in the browser. #54278
- `02_hide_accessibility_description.patch`
  Hides the description text with links to Nextcloud's documentation on top of the theme/accessibility settings page. #52676
- `03_remove_webdav_documentation_link.patch`
  Removes link to Nextcloud's WebDAV documentation when opening Files - File settings. #52676

### Theme

- `theme/core/css/server.css` contains some further customizations

