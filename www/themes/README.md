### Hiding themes from user profiles:

Add _ as the first character of the theme's folder name.


--------------------------------------------------------------------------------
### How to make a new theme using the 'simple' theme:


Copy the simple folder, paste it into the themes folder with a new name (for example mytheme).

Open the mytheme folder, go to templates/frontend/

Open basepage.tpl in a text editor.

Change `<link href="{$smarty.const.WWW_TOP}/themes/Simple/styles/style.css" rel="stylesheet" media="screen">`

To `<link href="{$smarty.const.WWW_TOP}/themes/mytheme/styles/style.css" rel="stylesheet" media="screen">`



Go back into the mythemes folder, go inside the styles folder.

Open style.css with a text editor.

In your browser (firefox for example) right click something ( the name of a release for example ).

Click inspect element, a menu will appear at the bottom.

Make sure you are on the "inspector" tab

On the bottom right you will see something like :

`label a.title{ font-weight:bold; } style.css:362`



This means at line 362 in style.css the font for the release's name is being set to bold.

Using a css website (w3schools.com for example) you can learn what the variables do.

You can change them in firefox by clicking the font-weight or bold part, and changing those or adding new ones.

When you find something you like you can add it to the style.css file (at line 362 for example).

--------------------------------------------------------------------------------
