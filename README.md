# oscommerce4 modules by hybiepoo

## aupost - online shipping module
aupost is a clone of the aupost module for zencart modified to work with oscommerce.
Since it appears that online modules will be "pay to play", I'll try to write my own free one.
<br>
Currently this module seems to work up until the point that the customer tries to change the shipping option.
The radio button is changed, but the shipping quote is not updated to reflect the new choice. I believe you could use this in production
if you just allowed a single shipping option.
<br>
<br>
For some reason, there are no dimensions available in oscommerce4 even though the fields are in the database for it.
For the aupost module to work, these fields are needed, and we can add them to the backend template.

Edit the following file:
> lib/backend/themes/basic/categories/productedit/size.tpl
<br>
Look for the following:
<br>
~~~html
 \<div class="dimmens_cm dimmens"\>
   \<div class="edp-line"\>
     \<label class="addcolonm"\>{$smarty.const.TEXT_WIGHT_KG}\</label\>
      \<input type="text" name="weight_cm" value="{$pInfo->weight_cm}" class="form-control form-control-small js_convert" data-target="weight_in" data-unit="kg"\>
    \</div\>
~~~
<br>
After this, add:
<br>
~~~html
\<div class="edp-line"\>
   \<label class="addcolonm"\>{$smarty.const.TEXT_LENGTH_CM}\</label\>
   \<input type="text" name="length_cm" value="{$pInfo-\>length_cm}" class="form-control form-control-small" data-target="length_in" data-unit="kg"\>
\</div\>
\<div class="edp-line"\>
  \<label class="addcolonm"\>{$smarty.const.TEXT_WIDTH_CM}\</label\>
  \<input type="text" name="width_cm" value="{$pInfo-\>width_cm}" class="form-control form-control-small" data-target="width_in" data-unit="kg"\>
\</div\>
\<div class="edp-line"\>
  \<label class="addcolonm"\>{$smarty.const.TEXT_HEIGHT_CM}\</label\>
  \<input type="text" name="height_cm" value="{$pInfo-\>height_cm}" class="form-control form-control-small" data-target="length_in" data-unit="kg"\>
  \</div\>
 ~~~
<br>
This should give you the fields required for the aupost module to work.



