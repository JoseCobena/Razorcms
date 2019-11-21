razorCMS File Based CMS (FBCMS)
===============================

Intro
-----

razorCMS is a File Based Content Management System, this means it helps you to build a website without the means of a database backend.
All data in razorCMS is stored in files and uses a database type engine called razorDB to use these files like a database.

It has been primarily designs for apache servers on a unix type machine, although it may well function just fine on a MS based environment. Please note that all testing is performed on unix type machines and no guarentee will be offered due to it's usabability outside of this environment.

By using this software, you the user are excepting that this software comes with no legal rights as to recompense, from any issues that may arise; This software comes with no guarentees, no promises. The developers will not be held responsible for any issues that arise due to the use of this software. Again, by using this software you agree to these terms.

Requirements
------------

To view and administer correctly....

A modern browser, that means google chrome, firefox, safari, opera, IE9 and up.
Tablets and mobiles are also supported as much as is humanly possible.

To run the system correctly....

Apache running on a unix type machine.

You may find it possible to make razorCMS work outside of it's comfort zone, awesome, I tilt my hat in your gneral direction, but I do not support this. Too many developers are keeping instituations alive purely by supporting old defunct outdated crap, I will not do this, I loose enough sleep at night without the need to support things that should be put out of their misery.

Installation FULL
-----------------

Due to the system not using any database backend, installation is a breeze:-

1. Download full version.
2. Unpack.
3. Upload the unpacked files (ensuring you select the .htaccess file too).
4. Using your web browser, navigate to the location you installed razorCMS.
5. Add '/login' to the end of the URL (without the quotes).
6. Login in with the following credentials:-
	Username/email: razorcms@razorcms.co.uk
	Password: password
7. Once logged in, click the dashboard icon (top left circular icon that looks like a bulls eye).
8. Click on profile, change the default username and password, save the changes and wait to be logged out.

Installation is now complete, you may now log in using the new credentials in the same manner you did above.

Installation UPGRADE
--------------------

1. Download upgrade.
2. Unpack.
3. Upload the unpacked files to your system merging any folders and overwriting any files.

Installing Extensions
---------------------

To install an extension:-

1. Download.
2. Unpack.
3. Upload to the location you installed razorCMS (you can upload to the root location as the file structure is included in the download).
4. To configure, start the admin overlay, click extensions, expand your extension and settings can be changed there.

Using razorCMS
--------------

razorCMS has been designed to be as intuitive as possible, so we shouldn't have to tell you too much. The only things we should really explain is that the system only has one area, public. Most CMS solutions come with two areas, one where people view the website, and a second admin end where they manage it.

razorCMS has been built to work all from the public area. When you log in, you are actually instantiating the admin overlay. This is a seperate javascript (angular) overlay that lays on top of the public area, only showing when you are logged in. By taking this approach we are able to reduce the need for a admin interface and help you to better visualize changes to the site, as you edit the page right there in front of you.

To admin overlay requires a login, once logged in, you can start the overlay by clicking the bulls eye circular top left icon. From the overlay you can edit the properties/details of the page you are currently viewing, or you can manage other aspects of the site.

When the admin overlay is not showing, the page should look pretty much like it does to the public user, in order to edit the page, just click the edit icon in the admin toolbar up top. Once clicked, the page will be transformed to an editable page. From here you can reorder content, add content, add extensions all by using the controls near the content. You should be able to see the default content that is already in the system, this will help guide you and help show you how to manage content. Once content has been added, it becomes available for any page to use, please not that if using content accross many pages, altering on one page will alter it everywhere. Only re-use content if you want to be able to alter it everywhere at the same time. If you want stand alone content, then always add the content. The idea of global content is a means to help you create cotnent that can be shared, like info you want to show on every page.

In addition to content, extensions are also added in this way, such as the contact form extension. This allows you to easily add an interactive contact form. To configure any extension, do this from the extension are on the admin overlay. Simply navigate to the extension, expand it and alter setting right there (such as the email for the contact form extension).

Managing menus is also simple, click on the 'x' to remove, click on the menu icon to add a page (as you create new pages they will show here too). To order the menu items, click the item you want to move, then click where you want it to move too. All other items will be moved back or forward one place. Please note that all menu alteration are global. If you change the menu items on one page, they change on all pages.

Themes in razorCMS are per page, this means you can set a theme per page, allowing greater control of your site (like side menus in some parts and top menus in others). All themes are added as extensions, but they can come with multiple layouts per theme allowing better selection of what you want.

Licensing
--------- 

razorCMS is licensed under the GPL V3, you can find a copy here www.gnu.org/copyleft/gpl.html or any other area that has a legitimate copy of the license. So what does this mean, well you can do what the hell you like with this software, create sites, extend it, change it, fork it, I don't really care either way.

All I ask is be kind, keep comments in place, and give a nod back to the guy that wrote it. If you purposely copy my code and rebadge this software, I will point you out as frauds and will make sure everyone knows it too. It takes five seconds to show credit where it's due and I have put more than five seconds into making this for you all to consume.

So you don't need to have a link in your site such as powered by xxxxxxxx, or this site uses xxxxxxx, you make it look how you want it, just dont pass my work off as yours.

On a side note to this, the razorCMS website is copywrite to smiffy6969 (me), you are not permitted to use that theme or copy it.

Summary
-------

Questions?, ideas?, bundles of cash you want to give me?...

Well in due course, the razorCMS site will be updated, in due course the system will get more functionality/extensions and in due course support and documentation will be supplied. Please be patient, one man bands ar hard to run, it will come with time, as I find it.

If you want to help out, by me an Aston Martin, then you can contact me through the website www.razorcms.co.uk (for now we are on v3.razorcms.co.uk but this will change soon).