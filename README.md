# oscommerce4 modules by hybiepoo

## aupost - online shipping module
aupost is a clone of the aupost module for zencart modified to work with oscommerce.
Since it appears that online modules will be "pay to play", I'll try to write my own free one.
<br>
Currently this module seems to work up until the point that the customer tries to change the shipping option.
The radio button is changed, but the shipping quote is not updated to reflect the new choice. I believe you could use this in production
if you just allowed a single shipping option.
<br>
Here is the caveat and the reason for my next module. 
Product dimensions are NOT in the free version of oscommerce, and it will cost you USD$59 to enable it. The aupost module requires the length_cm, width_cm and height_cm fields
 in the product database to request quotes from Australia Post.
 <br>
 Not only is the ability to edit these fields missing in oscommerce4, but they seem to NULL out the values for these (and the _in values are set to zero) when you submit any update to a product, 
 so we can't even manually set these fields in the database. I'm not sure whether this is intentional or a bug.
 <br>
 ## ProductDimensions extension
 This module is an attempt to extend the "Size and Packaging" tab on the product editing page to allow adding dimensions for shipping calculations.
 <br>
 As of right now, the extension can pull the length_cm, width_cm and height_cm fields and populate the form, but when the save button is used, the values are not saved properly.



