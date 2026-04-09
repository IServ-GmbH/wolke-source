# Building Docker Image

This folder contains everything needed to build the Docker image.  
The Docker image is built in the GitLab CI pipeline and stored in the IServ Docker registry.  
If you make changes to the patches or the Dockerfile and want to test a new image, you can either build it locally or in the pipeline.

## How it works

- The image is based on the official Nextcloud Docker [nextcloud/docker](https://github.com/nextcloud/docker).
- The source code in the image is then modified by [a number of patches or added files that disable or alter functionality](#documentation-of-patches--customizations).
  - These patches are located in `./source/patches/`.
  - There is exactly one patch file for every changed file.
  - Completely new files are located in `./source/added/`.
- The patches were created based on [nextcloud/server](https://github.com/nextcloud/server) and [nextcloud/activity](https://github.com/nextcloud/activity).

The Docker image build uses multiple parallel stages for fast iteration:

1. **clone** — Clones upstream Nextcloud and app repositories. Cached by VERSION alone, so patch changes don't invalidate this layer.
2. **js-main / js-richdocuments / js-viewer / js-linkeditor** — Each stage applies only its relevant `src/` patches, runs `npm ci` + JS build, and produces a binary diff of the compiled output. These stages run in parallel.
3. **php-patches** — Applies all patches (via `clone_and_apply_patches.sh`) and produces diffs excluding JS source files. No JS compilation happens here.
4. **production** — Collects all patch files from the parallel stages and applies them to the production Nextcloud image.

This means PHP-only changes skip all JS compilation, and JS changes only rebuild the affected app.

## Build the image

The [build_image.sh](build_image.sh) script builds the image from the original nextcloud-server repo and applies the patches. Be aware - it can take up to 20 minutes.

### Locally

* Run `CI=1 docker/cloudfiles/build_image.sh` to build the image.
* The image will be saved as `./data/image.tar.xz`.
* You can then transfer the image to your IServ VM and run `iservchk cloudfiles` to restart the container with the new image.

### In CI

* Simply push your changes and wait for the pipeline to finish.
* The image will be built and pushed to the IServ Docker registry, tagged with your last commit SHA.
* Run `download_image.sh` to download the image tagged with the current commit SHA from the IServ Docker registry.
* You can also pass a specific tag to `download_image.sh` to download a particular image version (e.g., `latest`).

_To download images from the registry, you must be logged into the `git.iserv.eu:443` and the `registry.git.iserv.eu` Docker registries._
To log in, create an [Access Token](https://git.iserv.eu/-/user_settings/personal_access_tokens) with the `read_registry` and `write_registry` scope and run 
```
docker login git.iserv.eu:443 -u <your.username>
docker login registry.git.iserv.eu -u <your.username>
```  
Enter the token as the password.
(Caution: Do not log in on your VM if others might have access to your session.)

### Building on the VM

You can also build the image directly on your IServ VM.

- SSH into your VM
- Ensure your automatic deployment (e.g., rsync) does not overwrite the image created in the next step.  
- Run `./docker/cloudfiles/build_image.sh`  
- If building the image fails and you receive a SIGINT, you probably need more memory [-> OpenNebula](https://cloud0.iserv.eu/); 16GB should be sufficient.

## Manually restarting the container with a new image

If for some reason you need to manually restart the container with a new image, you can do so by following these steps:

- Remove running containers created from the cloudfiles image
  ```bash
  RUNNING_CONTAINERS=$(docker ps --filter "ancestor=registry.git.iserv.eu/iserv/docker-cloudfiles" -q)
  if [ -n "$RUNNING_CONTAINERS" ] ; then
    docker rm -f $RUNNING_CONTAINERS
  fi
  ```  
- Remove the image  
  `docker image rm registry.git.iserv.eu/iserv/docker-cloudfiles`
- Import the new image tarball  
  `docker load -i data/image.tar.xz`
- Start the container with the new image `iservchk cloudfiles`


## How to create new patches

### 1. Clone Nextcloud and apply existing patches  
- `rm -rf ~/nextcloud-server`
- `./docker/cloudfiles/source/clone_and_apply_patches.sh 31.0.14 ~/nextcloud-server`  
  - arg1: Nextcloud version currently used for the image  
    - You can find the current version in the [.env](.env) file.  
  - arg2: destination path for the repo that gets temporarily checked out.

- The existing patches should be applied correctly, and the `~/nextcloud-server` repo should show several changes/untracked files when running `git status`.

### 2. Code your patch  
- Make your modifications **without adding and committing anything** to the Nextcloud project.
  - If you create a new patch, PHPStorm adds it directly.
  - To prevent this go to Settings > Version Control > Confirmation > Set "When files are created" to "Do not add"
- Remember to add your changes here [Patches_documentation.md](../../doc/patch_list.md).

### 3. Extract patches  
- Return to this project and run  
- `./docker/cloudfiles/source/extract_patches.sh ~/nextcloud-server`  
  - This will create or update files in `./source/added/` and `./source/patches/`.

### 4. Installing/Testing
- Build the Docker image (see section [Building the container](#Build-the-image)). 
- `iservchk` to restart the container with the new image.
- Test your changes.

### 5. Committing  
- Do not commit the Docker image tarball `./data/image.tar.xz` or the image ID `./data/image.tag` to git.  
- Commit patches:  
  - `./source/added/`  
  - `./source/patches/`.

If patches need to be created for Nextcloud apps, corresponding changes must be made to the scripts `clone_and_apply_patches.sh`, `extract_patches.sh`, and the `Dockerfile`.

### Patching a new app

If patches need to be applied to an app that has not been patched yet, make sure to update the following scripts as well:

* Dockerfile (add a new JS stage if the app has frontend patches, and update the production stage)
* clone_and_apply_patches.sh
* extract_patches.sh

## Handling failed patches

1. Move the affected patch file out of `./source/patches`.
2. Run `clone_and_apply_patches.sh 31.0.14 ~/nextcloud-server` again.  
3. Repeat steps 1 and 2 until all remaining patches have been applied successfully.
4. Manually apply the changes of the moved patch files to the affected files in the working copy `~/nextcloud-server`.  
5. Check if (non-)core apps need to be upgraded.
6. Run `extract_patches.sh ~/nextcloud-server` to regenerate the patches.

> if the clone_and_apply script hangs, then it could be that the source file was removed or renamed. Look in the source code and rename/remove patch

## Running the image

This image is specifically tailored for IServ's use-case. It can be embedded into IServ as an iframe. It fetches users, authenticates them with IServ, and allows other IServ modules to access its data securely.  
This means some volumes and environment variables required may not be useful to everyone, such as `LDAP_BASE_DN` or `/ldap_pass.txt`.

Also, to avoid storing the source code on the host in a volume, we use `tmpfs` for the entire `/var/www/html` folder and handle installation/updates/restarts in our entrypoint script.

All variables documented [here](https://github.com/nextcloud/docker/blob/20327851c8d9f7b40606844dfdccef5ee2230355/README.md#auto-configuration-via-environment-variables) are available in addition to:  
* `ISERV_DOMAIN`: used to set CSP to allow iframe embedding  
* `LDAP_BASE_DN`: used for LDAP configuration

The following files/volumes are required by this image:  
- `/version`: used by our entry script to manage updates  
- `/var/log/cloudfiles`: if using our `iserv.config.php`, logs will be saved here  
- `/sp_certificate.pem`  
- `/sp_key.pem`  
- `/idp_certificate.pem`  
- `/ldap_pass.txt`

If using `tmpfs` for the `/var/www/html` folder as mentioned earlier, the `NEXTCLOUD_DATA_DIR` environment variable must also be set to a volume-mounted persistent path.

## Logging in via admin super user

Configuration of Nextcloud System is done via OCC commands at the start of the container. The usual IServ-Admin should not be able to alter those settings. 
To access the Nextcloud admin page for development, follow these steps:

1. Logout from IServ or if on cloudfiles.domain logout from cloudfiles

2. copy the password from
   ```bash
   cat /var/lib/iserv/docker-cloudfiles/pwd/admin.pwd
   ```

3. Go to https://cloudfiles.mein-iserv.dev/index.php/login?direct=1
   - user: 00_admin
   - password: from the file

   Because of [#53856](https://redmine.iserv.eu/issues/53856) we had to rename the default admin user to 00_admin.

4. Go to Account Settings Bubble -> Administratoreinstellungen

## Misc

```bash
# See live NC config:
docker exec -it cloudfiles php occ config:list system
```
