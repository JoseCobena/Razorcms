# razorCMS
File Based CMS (FBCMS)
Version 3.4.8

---


## Intro


**razorCMS** is a *File Based Content Management System*.
This means it helps you to build a website without the means of a database backend.
All data in **razorCMS** is stored into sqlite db files, using the sqlite DB engine.

It has been primarily designed for Apache on a *Unix* machine, although it may well function just fine on a *Microsoft* based environment. Please note that all testing is performed on *Unix* machines, and no guarantee is offered outside of this environment.

By using this software you, the user, are accepting that this software comes with no legal rights as to recompense from any issues that may arise. This software comes with no guarantees, and no promises. The developers of this software will not be held responsible for any issues that arise due to the use of this software. Again, by using this software you agree to these terms.


---

## Docker

If you run this on docker, via docker hub, you will not need to do anything else after this part of the readme, setup is automatic. If you want nwer versions, just pull down the latest image, down your container, build and up.

If you like using docker-compose like me, then here is an example file you can use...

```
version: '2'
services:
  razor:
    image: ulsmith/razorcms
    environment:
      - MAILGUN_KEY=...
      - MAILGUN_DOMAIN=...
      - MAILGUN_MAILER_ADDRESS=...
    labels:
      - "traefik.backend=razor"
      - "traefik.frontend.rule=Host:razor.docker.localhost"
    volumes:
      - "./storage:/var/www/html/storage"
      - "./extension:/var/www/html/extension"
      - "./extension-api:/var/www/html/rars/api/extension"
networks:
  default:
    external:
      name: docker_docker-localhost
```

One thing to note here is I use a seperate container for load balancing, this is why you see traefik stuff in the labes, you can just map ports if you wish. Also there is some mailgun stuff in there, you will need to set these if you want emails for things like forgotton password etc.

You will notice I have mapped a few volumes here...

* storage (for sqlite db, files and logs)
* extension, where you put extensions (UI)
* api-extension, where you put extensions (api access to UI extensions if needed)

This makes it easy fro us to extend and also manage razorCMS whilst keeping persistancy.

First I would create your docker-compose file in your folder where you want to run it, then create the three folders above, and give them write access by the containers apache user (you can 777 them or add groups etc.). Once you have done this you will be able to fire up the container and hit the page, this will generate some folders from the build folder in the container and get your system up and running whilst keeping persistancy. Don't worry, your files will not be 777 inside, this just allows the containers apache to write into them, anything inside them will be restricted to apache from the container!

If you do not want persistancy, you do not need to map the volumes, the website will then stay persistant for as long as the container is present, if you down the container and remove it you will loose your site changes unless you map the folders above and give them write permissions.


## Requirements


**To View, and Administer Correctly:**

A modern browser; that means *Google Chrome*, *Firefox*, *Safari*, *Opera*, and *IE9+*.
Tablets, and mobile devices are also supported as much as is humanly possible.


**To Run The System Correctly:**

Apache running on a Unix type machine.

You may find it possible to make **razorCMS** work outside of its comfort zone, awesome. I tilt my hat in your general direction. However, I do not support this. Too many developers are keeping institutions alive purely by supporting old defunct outdated crap. I will not do this. I loose enough sleep at night without the need to support things that should be put out of their misery.

The following modules are required in order to install and run razorCMS.

1. exif
2. session
3. xml
4. zip
5. zlib
6. json
7. suphp
8. mod rewrite
9. sqlite3 PDO

In addition to the above, it is also required that allow overrides is set to allow htaccess configurations to be parsed.

---


## Installation FULL

Due to the system not using any database backend, installation is a breeze.

**Follow These Steps:**

1. Download full version.
2. Unpack.
3. Upload the unpacked files. (Ensuring you select the *.htaccess* file too.)
4. Ensure you have permission to create folders on root, storage will try yo be created, if it cannot be, you will need to do manually or sort permissions (suphp).
5. Using your web browser, navigate to the location you installed **razorCMS**.
6. Append "**/login**" to the end of the URL. (Without the quotes.)
7. Login in with the following default credentials:
  - *Username/email*: **`razorcms@razorcms.co.uk`**
  -	   *Password*: **`password`**
8. Once logged in, click the **Dashboard Icon** to bring up the **Administration Overlay**. (Top left circular icon that looks like a *bullseye*.)
9. Click on profile, change the default username, and password, save the changes, and wait to be logged out.

Installation is now complete, you may now log in using the new credentials in the same manner you did above.


---


## Upgrading

**Prefered Upgrade Method**

The prefered upgrade method is to use the auto upgrade tool. This tool can be found in the settings area of the **Administration Overlay**. This tool is a simple two click method for updating your sytem to the latest release. During the process you will be able to download a complete system backup, should the process not be successful.

**Manual Upgrade - Follow These Steps:**

1. Download upgrade.
2. Unpack.
3. Upload the unpacked files to your system merging any folders, and overwriting any files.
4. Hit the url **yourwebsiteurl/storage/tmp/package/system\_upgrade\_post\_install.php** to run any post upgrade requirements.


---


## Installing Extensions

**Prefered Installation Method**

The prefered installation method is to use the auto installation tool. This tool can be found in the extensions area of the **Administration Overlay**. This tool is a simply one click method for installing any extension at the latest release, or upgrading an extension.

**Manual Installation - Follow These Steps:**

1. Download.
2. Unpack.
3. Upload to the location you installed **razorCMS**. (You can upload to the root location as the file structure is included in the download.)
4. To configure, click on the bullseye **Dashboard Icon** to bring up the **Administration Overlay**, click **Extensions**, expand your extension, and change your settings there.


---


## Using razorCMS


**razorCMS** has been designed to be as intuitive as possible, so we shouldn't have to tell you too much. The only things we should really explain is that the system only has one area, public. Most *CMS* solutions come with two areas, one where people view the website, and a second administration end where they manage it.

**razorCMS** has been built to work all from the public area. When you log in, you are actually instantiating the **Administration Overlay**. This is a separate *JavaScript* (*AngularJS*) overlay that lays on top of the public area, which is only shown when you are logged in. By taking this approach we are able to reduce the need for an administration interface, and help you to better visualize changes to the site as you edit the page right there in front of you.

The **Administration Overlay** requires a login. Once logged in, you can start the **Administration Overlay** by clicking the bullseye **Dashboard Icon**. From the **Administration Overlay** you can edit the properties/details of the page you are currently viewing, or you can manage other aspects of the site.

When the **Administration Overlay** is not showing, the page should look pretty much like it does to the public user, in order to edit the page, just click the edit icon in the **Administration Toolbar** up top. Once clicked, the page will be transformed to an editable page. From here you can reorder content, add content, and add **Extensions** all by using the controls near the content. You should be able to see the default content that is already in the system, this will help guide you, and help show you how to manage content. Once content has been added, it becomes available for any page to use. Please note that if you are using content across many pages, altering content on one page will alter it everywhere. Only re-use content if you want to be able to alter it everywhere at the same time. If you want stand-alone content, then always add the content as a new piece. The idea of *global* content is a means to help you create content that can be shared, as in information you want to show on every page.

In addition to content, **Extensions** are also added in this way, such as the **Contact Form Extension**. This allows you to easily add an interactive contact form. To configure any **Extension**, do this from the **Extensions** in the **Administration Overlay**. Simply navigate to the **Extension**, expand it, and alter its settings right there. (Such as the email for the contact form **Extension**.)

Managing **Menus** is also simple. Click on the "**x**" to remove a **Menu Item**, click on the **Menu Icon** to add a page. (As you create new pages they will show here too.) To order the **Menu Items**, click the **Menu Item** you want to move, and then click where you want it to move to. All other **Menu Items** will be moved back, or forward one place. Please note that all **Menu** alterations are *global*. If you change the **Menu Items** on one page, they change on all pages.

**Themes** in **razorCMS** are per page, this means you can set a different **Theme** for each page, allowing greater control of your site. (Like side **Menus** in some parts, and top **Menus** in others.) All **Themes** are added as **Extensions**, but they can come with multiple layouts per **Theme**, allowing better selection of what you want.


---


## Licensing


**razorCMS** is licensed under the *GPL V3*; you can find a copy by [Clicking Here] (//gnu.org/copyleft/gpl.html). So what does this mean? Well, you can do whatever the hell you would like with this software; create sites, extend it, change it, fork it; I don't really care either way.

All I ask is be kind, keep comments in place, and give a nod back to the guy that wrote it. If you purposely copy my code, and rebadge this software, I will point you out as a fraud, and will make sure everyone knows it too. It takes five seconds to show credit where it's due, and I have put more than five seconds into making this for you all to consume.

So you don't need to have a link in your site such as *Powered by xxxxxxxx*, or *This Site Uses xxxxxxx*. You make it look how you want it. Just don’t pass my work off as yours.

On a side note to this, the **razorCMS** website is Copyrighted to smiffy6969 (me). You are not permitted to use that **Theme**, or copy anything within that site.


---


## Summary


Questions, ideas, and/or bundles of cash you want to give me?

Well, in due course the **razorCMS** site will be updated, in due course the system will get more functionality/**Extensions**, and in due course the **Support**, and **Documentation** will be supplied. Please be patient, one man bands are hard to run, it will come with time as I find it.

If you want to help out, buy me an *Aston Martin*, then you can contact me through the website. [//razorcms.co.uk] (//razorcms.co.uk) (For now we are on v3.razorcms.co.uk but this will change soon.)
