
<td>New {$title}&nbsp;&nbsp;</td>
<td>
    <input type="hidden" name="org" value="{$org}" form="addSeats1" />
    <select name="office" onClick="return continueIfDataUnChanged();" form="addSeats1">
        <option value="">(choose one)</option>
        {foreach from=$offices item=office}
            <option value="{$office.office}">{$office.shortname}</option>
        {/foreach}
    </select>
</td>
<td>&nbsp;<button type="submit" onClick="return continueIfDataUnChanged();" form="addSeats1">Add</button></td>
