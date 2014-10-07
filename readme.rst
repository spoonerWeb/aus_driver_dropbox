What does it do?
================

This is a driver for the file abstraction layer (FAL) to support Dropbox.

You can create a file storage which allows you to upload/download and link the files to Dropbox. So you can upload your files in the TYPO3 CMS backend to Dropbox and link them in the frontend. Website visitor download the files from paths like \https://dl.dropboxusercontent.com/1/view/iwojriojfiajsdfj/fileadmin/big-file.iso

It also supports the TYPO3 CMS image rendering but it is not recommended because the Dropbox API isn't very fast with processing the files.

Requires TYPO3 CMS 6.2





Installation
------------

Add a new file storage with the "Dropbox" driver to root page (pid = 0).

Target group: **Administrators**


Dropbox authentication
----------------------

Follow the instructions (see above) to connect your Dropbox account with TYPO3 CMS:

1. Click on the link
2. Click "Allow" (you might have to log in first)
3. Copy the authorization code and paste to the field

After saving the storage settings, you should see the success message:

**Authentication successful**

If you do not see this message, something went wrong.





Base path:
^^^^^^^^^^

The base path is the directory in your Dropbox, which will be used by this driver.
