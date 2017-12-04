### Hiding themes from user profiles:

All active theme directories should have their first character of the name capitalised. Delete
the directories or change the initial character to lower-case to hide them.

Do not disable the Default theme, as it is the fallback theme for any missing pages, including the entire admin part of the site.


--------------------------------------------------------------------------------
### How to make a new theme using the 'Default' theme:


Copy the Default directory, paste it into the themes directory with a new name (for example Mytheme).

It is recommended to use the Default theme as it supposed to be kept up to date with new features, so should have everything found in other themes (however, the JS framework is older - so you might want to use one of the other themes).

Open the Mytheme directory, go to templates/

Open basepage.tpl in a text editor.

Change `<link href="{$smarty.const.WWW_TOP}/themes/Default/styles/style.css" rel="stylesheet" media="screen">`

To `<link href="{$smarty.const.WWW_TOP}/themes/Mytheme/styles/style.css" rel="stylesheet" media="screen">`



Go back into the Default directory, go inside the styles directory.

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
