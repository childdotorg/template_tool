-----------------------------------------------------
##Jim's original Readme

*Massive work-in-progress, please contact me if you'd like to contribute.*

###Notes
 - Needs a deploy script to check permissions, create settings files  etc.
 - Currently includes FPDF and FPDI, should switch to pulling them separately as part of deploy
 - Currently has loads of workplace-specific content
 - Code has got uber messy due to rapid hacks, needs consolidation. (Switch to OOP?)
 
###field types
 
 - **Normal**: Displays as-is using positioning and formatting as given
 - **Data** - Takes input but not directly displayed -- can be used in "Wrapper" type fields. Ignores all formatting in favour of that of the "Wrapper"
 - **Wrapper**: Not editable, pulls text from other field values to fill in tokens. Tokens take the form {X} where X is the id of the related field (**Data** or **Normal**)

 --------------------------------------------------------------------------

##Install instructions
 - copy files from `/content/orginal` into `/content` and customise
 - Give write permissions on the `/storage/templates` folder
