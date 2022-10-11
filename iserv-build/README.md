# Building Docker Image

This folder contains everything needed to build the docker image. Run `./build.sh` to build it.

## How it works

The Image is based on the official Nextcloud Docker [nextcloud/docker](https://github.com/nextcloud/docker).\
The source code in the image is then modified by a number of patches disabling or altering the functionality. These patches are located in `./adds/patches/`.\
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
