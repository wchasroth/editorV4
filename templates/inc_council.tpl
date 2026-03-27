
<td>New {$title}:&nbsp;&nbsp;</td>
<td>
   <input type="hidden" name="org" value="{$org}" form="addSeats2" />
   {if $dt != ""}
      {$dt} #&nbsp; <input type="text" name="subdist" size="2" style="border: 1px solid;" class="char1"
                            onClick="return continueIfDataUnChanged();" form="addSeats2" />&nbsp;&nbsp;
   {else}
      <input type="hidden" name="subdist" value="0" form="addSeats2" />
   {/if}
</td>
<td>&nbsp;<button type="submit" onClick="return continueIfDataUnChanged();" form="addSeats2">Add</button></td>