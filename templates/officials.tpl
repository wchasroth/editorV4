{nocache}
<!DOCTYPE html>
<html lang="en">
<head>
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto">
   <style>
      td, input { font-size: 90%;  margin: 0; padding: 0;}
      input { border: 0;  background-color: inherit; }
      td    { background-color: inherit; }

      body, td, li {
        font-family: 'Roboto';
        /* font-size: 0.8rem; */
      }
      .smaller { font-size: 85%; }
      .zebra {
         tr:nth-child(even) {
            background-color: #d2d2d2;
         }
         tr:nth-child(odd) {
            background-color: #FFFFFF;
         }
         tr:hover { background-color: aquamarine; }
         td,th { padding-right: 0.5em;  padding-top: 0.1em;  padding-bottom: 0.1em;}
      }
      .header { font-weight: bold; }
      .char1  { width: 1em; }
      .char4  { width: 2.75em; }
      .char7  { width: 4em; }
      .char12 { width: 12em; }
      .number { text-align: right; }
      .th1 {
         position: sticky;
         top: 0.0em;
         background-color: white;
         z-index: 100;
         text-align: left;
         font-size: 120%;
         height: 2em;
      }
      .th2 {
         position: sticky;
         top: 2.6em;
         background-color: #53d5fd;
         /* background-color: #d2d2d2; */
         z-index: 100;
         text-align: left;
         font-weight: bold;
      }
      .th2a {
         position: sticky;
         top: 2.6em;
         background-color: #d2d2d2;
         z-index: 100;
         text-align: left;
         font-weight: bold;
      }
      .expandable {
         transition: width 0.3s ease-in-out;
      }
      .expandable:focus {
         width: 20em;
      }
      .spacer    {  max-width:  2em;  }
      .col_email {  max-width: 11em;  }
      .col_name  {  max-width: 11em;  }
      .col_web   {  max-width: 18em;  }
      .col_addr  {  max-width: 15em;  }
      .col_phone {  max-width:  6em;  }
      .col_term  {  max-width:  3em;  }
      .col_year  {  max-width:  3em;  }

      .button {
         background-color: #0d6dfb;
         color: white;
         border-radius: 5px;
         border: none;
         height: 1.8em;
      }
      #pop-up-save {
          display: none;
          position: fixed;
          top: 100px;
          left: 200px;
          background-color: lightgreen;
          padding: 15px;
          border-radius: 5px;
          z-index: 1000;
          box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      }
      #pop-up-changed {
          display: none;
          position: absolute;
          background-color: lightcoral;
          padding: 15px;
          border-radius: 5px;
          z-index: 1000;
          box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      }
   </style>
   <script>
      function shrinkExpandLeftPanel() {
         let leftFrame = parent.document.getElementById('f1');
         let button = document.getElementById('shrinkExpand');
         if (button.src.includes("shrink")) {
            leftFrame.style.display = "none";
            button.src = "expand-10-48.png";
         }
         else {
            leftFrame.style.display = "block";
            button.src = "shrink-10-48.png";
         }
      }

      function setShrinkExpandButton() {
         let leftFrame = parent.document.getElementById('f1');
         if (leftFrame.style.display == "none") {
            let button = document.getElementById('shrinkExpand');
            button.src = "expand-10-48.png";
         }
      }

      function changed(id) {
         let fc = document.getElementById("fieldsChanged");
         fc.value = fc.value + id + ",";
      }

      function submitMainForm() {
         let mainForm = document.getElementById("mainForm");
         mainForm.requestSubmit();
      }

      function hasChanged() {
          let fc = document.getElementById("fieldsChanged");
          return (fc != null  &&  fc.value != "");
      }

      function continueIfDataUnChanged() {
          if (hasChanged()) { showPopUp('pop-up-changed'); return false; }
          return true;
      }

      function showPopUp(id) {
          const popup = document.getElementById(id);
          popup.style.display = "block";
          setTimeout(function() { popup.style.display = "none";}, 2000);
          return false;
      }

      function deleteThisSeat (id, name) {
          if (continueIfDataUnChanged()) {
              if (! confirm("Do you really want to delete " + name + "?")) return false;
              const ds = document.getElementById('deleteSeat');
              ds.value = id;
              mainForm = document.getElementById('mainForm');
              mainForm.submit();
              return false;
          }
          showPopUp('pop-up-changed');
          return false;
      }

      function expand(element) {
         element.oldzindex = element.style.zIndex;
         element.oldcolor  = element.style.backgroundColor;
         element.style.zIndex = 100;
         element.style.backgroundColor = "cyan";
      }

      function shrink(element) {
         element.style.zIndex          = element.oldzindex;
         element.style.backgroundColor = element.oldcolor;
      }
   </script>
</head>

<body style="margin-top: 0;"  onLoad="setShrinkExpandButton();">
<form id="mainForm" method="post" action="officials.php?county={$county}&orgs={$qsOrgs}&district={$qsDistrict}&show={$qsShow}">
<input type="hidden" name="fieldsChanged" id="fieldsChanged" value="" />
<input type="hidden" name="deleteSeat"    id="deleteSeat"    value="" />

<div id="pop-up-save" class="pop-up">
     Changes saved.
</div>

{if $showSaved == 1} <script>showPopUp('pop-up-save');</script> {/if}

<table class="zebra" cellpadding="0" cellspacing="0">
   <tr>
      <td class="th1" colspan="2"
         ><img id="shrinkExpand" src="shrink-10-48.png" style="height: 75%;  margin-top: 5px;"
                onClick="shrinkExpandLeftPanel();"/></td>
      <td class="th1" colspan="5"><b>{$name}</b></td>
      <td class="th1" colspan="18">
         {if $canEdit }
            <input type="button" onClick="submitMainForm();"; return false;" value="Save Changes" class="button"/>
         &nbsp;&nbsp;&nbsp;&nbsp;
            <span class="font-size: 95%;">Reviewed:</span>
            <input type="checkbox" {$reviewedChecked} name="reviewed" value="1" style="accent-color: lightgreen;" onChange="changed(this.name);"/>
            {if $reviewedChecked != ""}
               <span style="font-size: 70%;">(by {$reviewedBy} at {$reviewedDt})</span>
            {/if}
         {/if}
      </td>
   </tr>
   <tr>
      <td class="th2"></td>
      <td class="th2">Office</td>
      {if $showDistrict } <td class="th2">Dist</td>    {/if}
      {if $showSubDist  } <td class="th2">{$regionColumnName}</td> {/if}
      {if $showSeat     } <td class="th2">S#</td>      {/if}
      <td class="th2">TL</td>
      <td class="th2">&nbsp;Next</td>
      <td class="th2a">&nbsp;Name</td>
      <td class="th2a">Pty</td>
      <td class="th2a"></td>
      <td class="th2a" colspan='2'>Web</td>
      <td class="th2a" colspan='2'>Email</td>
      <td class="th2a" colspan='2'>Phone</td>
      <td class="th2a">Address</td>
   </tr>
   {foreach from=$rows item=row}
      <tr>
         <td><a href="#" onClick="return deleteThisSeat({$row['id']}, '{$row['shortname']}: {$row['name']}');"><img src="trash.png" width="14"/></a></td>
         <td style="white-space: nowrap;"     class="smaller">{$row['shortname']}</td>
         {if $showDistrict} <td align='right' class="smaller">{$row['district']}</td> {/if}
         {if $showSubDist}
             <td align='right' class="smaller">
                {if $row['subdist'] > 0} {$row['subdist']} {/if}
             </td>
         {/if}
         {if $showSeat}
            {if $row['seatmax'] * 1 != 1 } <td align='right' class="smaller">{$row['seatnum']}</td> {else} <td></td> {/if}
         {/if}
         {$hidden = ($row['appointed'] == '1' ? "hidden" : "") }
         <td><input {$hidden} name="s:{$row['id']}:termlen"   type="text"  size="1"  class="char1 number"  pattern="[0-9]*" onChange="changed(this.name);"  value="{$row['termlen']}"/></td>
         <td><input {$hidden} name="s:{$row['id']}:termcycle" type="text"  size="4"  class="char4 number"  pattern="[0-9]*" onChange="changed(this.name);"  value="{$row['termcycle']}"/></td>
         <td><input           name="i:{$row['inc_id']}:name"  type="text"  size="22"                                        onChange="changed(this.name);"  value="{$row['name']}"/></td>
         <td><input           name="i:{$row['inc_id']}:party" type="text"  size="1"  class="char1"      pattern="[A-Za-z]*" onChange="changed(this.name);"  value="{$row['party']}"/></td>
         <td style="vertical-align: bottom;">
             {if $row['url'] != ''}
                <a href="{$row['url']}" target="_blank"><img src="external3.png" width="15"></a>
             {/if}
         </td>

         <td class="col_web"><input type="text" name="i:{$row['inc_id']}:web"  size="60" value="{$row['web']}"
             style="position: relative; z-index: 3;"
             onFocus="expand(this);" onBlur="shrink(this);" onChange="changed(this.name);" /></td>

         <td class="spacer" style="position: relative; z-index: 4;">&nbsp;&nbsp;</td>

         <td class="col_email"><input type="text" name="i:{$row['inc_id']}:email" size="45"  value="{$row['email']}"
             style="position: relative; z-index: 5;"
             onFocus="expand(this);" onBlur="shrink(this);" onChange="changed(this.name);" /></td>

         <td class="spacer" style="position: relative; z-index: 6;">&nbsp;&nbsp;</td>

         <td class="col_phone"><input name="i:{$row['inc_id']}:phone"     type="text"  size="18" class="char12"  onChange="changed(this.name);" 
               value="{$row['phone']}" style="position: relative; z-index: 7;"
             onFocus="expand(this);" onBlur="shrink(this);" onChange="changed(this.name);" /></td>

         <td class="spacer" style="position: relative; z-index: 8;">&nbsp;&nbsp;</td>

         <td><input name="i:{$row['inc_id']}:address"   type="text"  size="40"  onChange="changed(this.name);"  value="{$row['address']}"
               style="position: relative; z-index: 9;" /></td>
      </tr>
   {/foreach}
</table>
</form>
<p/>

{if $expandableOrgs|count > 0  and  $canEdit}
   <div id="pop-up-changed" class="pop-up" style="width: 300px;">
       Save changes first!
   </div>
   <form id="addSeats1" method="post" action="officials.php?county={$county}&orgs={$qsOrgs}&district={$qsDistrict}&show={$qsShow}"></form>
   <form id="addSeats2" method="post" action="officials.php?county={$county}&orgs={$qsOrgs}&district={$qsDistrict}&show={$qsShow}"></form>
      <div style="max-width: 40em;">
         <b>New Seats:</b> If this page is missing some offices or seats, you can add new "empty" seats, below.
         Then proceed to fill in the data for each new seat.
      </div>
      <p/>
      <table style="margin-left: 2em;" border="0">
         {foreach from=$expandableOrgs item=org}
            <tr>
               {if     $org == 'cnty'}       {include file="inc_office.tpl"  org=$org title="county office"}

               {elseif $org == 'cnty-com'}   {include file="inc_council.tpl" org=$org title="county commissioner"  dt="District"}

               {elseif $org == 'city'}       {include file="inc_office.tpl"  org=$org title="city office:"}
               {elseif $org == 'city-cou'}
                  {$ward = ($showSubDist ? 'Ward' : '') }
                  {include file="inc_council.tpl" org=$org title="city council"         dt=$ward}

               {elseif $org == 'town'}       {include file="inc_office.tpl"  org=$org title="township office:"}
               {elseif $org == 'town-cou'}   {include file="inc_council.tpl" org=$org title="township trustee/council"}

               {elseif $org == 'vil'}        {include file="inc_office.tpl"  org=$org title="village office:"}
               {elseif $org == 'vil-cou'}    {include file="inc_council.tpl" org=$org title="village trustee/council"}

               {elseif $org == 'schl-cou'}   {include file="inc_council.tpl" org=$org title="school board"}

               {elseif $org == 'comcol-cou'} {include file="inc_council.tpl" org=$org title="board member"}

               {elseif $org|substr:0:3 == 'crt'}  {include file="inc_council.tpl" org=$org title="judge"}
               {/if}
            </tr>
            <tr><td>&nbsp;</td></tr>
         {/foreach}
      </table>
   </form>

   <div style="max-width: 40em;">
      <b>Deleting Seats:</b>  We may list seats that don't actually exist.&nbsp;
      This is usually the result of missing information in the county election reports, such as term length.&nbsp;
      (If we don't know the term length, we don't know when they were replaced.)&nbsp;
      Resignations can also cause problems.
      <p/>
      You can delete a set with the trash-can icon at the left of each row.&nbsp;
      But be <b>very careful</b> about deleting seats.&nbsp;
      In particular, don't delete a seat if you're simply replacing one person with another -- just edit their
      name, contact information, and so on.
   </div>
{/if}


<pre>
   <!--
qsOrgs    ={$qsOrgs}
qsDistrict={$qsDistrict}
qsShow={$qsShow}
-->
sql={$debugSql}
   canEdit={$canEdit}
   Admin={$debugAdmin}
   Ad={$debugAd}
   Ed={$debugEd}

{$error}
{$rowText}
</pre>

</body>
</html>
{/nocache}
