{nocache}
<!DOCTYPE html>
<html lang="en">
<head>
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto">
   <style>
      body, td, li {
        font-family: 'Roboto';
        /* font-size: 0.8rem; */
      }
      .smaller { font-size: 85%; }
      .zebra {
         tr:nth-child(even) {
            background-color: #d2d2d2;
         }
         tr:hover { background-color: aquamarine; }
         td,th { padding-right: 0.5em;  padding-top: 0.1em;  padding-bottom: 0.1em;}
      }
      .header { font-weight: bold; }
      .char1  { width: 1em; }
      .char4  { width: 2.75em; }
      .char7  { width: 4em; }
      .char12 { width: 7em; }
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
      input {
         /* background-color: inherit; border: 1px solid #000; */
         background-color: inherit; border: 0;
      }
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

      function showPopUp() {
          const popup = document.getElementById("pop-up-save");
          popup.style.display = "block";
          setTimeout(function() { popup.style.display = "none";}, 2000);
          return false;
      }
   </script>
</head>

<body style="margin-top: 0;"  onLoad="setShrinkExpandButton();">
<form id="mainForm" method="post" action="officials.php?orgs={$qsOrgs}&district={$qsDistrict}&show={$qsShow}">
<input type="hidden" name="fieldsChanged" id="fieldsChanged" value="" />

<div id="pop-up-save" class="pop-up">
     Changes saved.
</div>
{if $showSaved == 1} <script>showPopUp();</script> {/if}

<table class="zebra" cellpadding="0" cellspacing="0">
   <tr>
      <td class="th1" colspan="1"
         ><img id="shrinkExpand" src="shrink-10-48.png" style="height: 75%;  margin-top: 5px;"
                onClick="shrinkExpandLeftPanel();"/></td>
      <td class="th1" colspan="4"><b>{$name}</b></td>
      <td class="th1" colspan="16"><input type="button" onClick="submitMainForm();"; return false;" value="Save Changes" class="button"/></td>
   </tr>
   <tr>
      <td class="th2">Office</td>
      {if $showDistrict } <td class="th2">Dist</td>    {/if}
      {if $showSubDist  } <td class="th2">{$regionColumnName}</td> {/if}
      {if $showSeat     } <td class="th2">S#</td>      {/if}
      <td class="th2">TL</td>
      <td class="th2">&nbsp;Next</td>
      <td class="th2">Name</td>
      <td></td>
      <td class="th2">Web</td>
      <td class="th2">Email</td>
      <td class="th2">Phone</td>
      <td class="th2">Address</td>
      <td class="th2">Pty</td>
      <td class="th2">Pct</td>
   </tr>
   {foreach from=$rows item=row}
      <tr>
         <td style="white-space: nowrap;"     class="smaller">{$row['shortname']}</td>
         {if $showDistrict} <td align='right' class="smaller">{$row['district']}</td> {/if}
         {if $showSubDist}
             <td align='right' class="smaller">
                {if $row['subdist'] > 0} {$row['subdist']} {/if}
             </td>
         {/if}
         {if $showSeat}
            {if $row['seatmax'] != 1 } <td align='right' class="smaller">{$row['seatnum']}</td> {else} <td></td> {/if}
         {/if}
         <td><input name="s:{$row['id']    }:termlen"   type="text"  size="1"  class="char1 number"  pattern="[0-9]*" onChange="changed(this.name);"  value="{$row['termlen']}"/></td>
         <td><input name="s:{$row['id']    }:termcycle" type="text"  size="4"  class="char4 number"  pattern="[0-9]*" onChange="changed(this.name);"  value="{$row['termcycle']}"/></td>
         <td><input name="i:{$row['inc_id']}:name"      type="text"  size="22"                                        onChange="changed(this.name);"  value="{$row['name']}"/></td>
         <td>
             {if $row['url'] != ''}
                <a href="{$row['url']}" target="_blank"><img src="linkout3.jpg"></a>
             {/if}
         </td>
         <td><input name="i:{$row['inc_id']}:web"       type="text"  size="27"                                        onChange="changed(this.name);"  value="{$row['web']}"/></td>
         <td><input name="i:{$row['inc_id']}:email"     type="text"  size="27"                                        onChange="changed(this.name);"  value="{$row['email']}"/></td>
         <td><input name="i:{$row['inc_id']}:phone"     type="text"  size="12" class="char12"                         onChange="changed(this.name);"  value="{$row['phone']}"/></td>
         <td><input name="i:{$row['inc_id']}:address"   type="text"  size="27"                                        onChange="changed(this.name);"  value="{$row['address']}"/></td>
         <td><input name="i:{$row['inc_id']}:party"     type="text"  size="1"  class="char1"                          onChange="changed(this.name);"  value="{$row['party']}"/></td>
         <td align='right' class="smaller">{$row['PCT']}%</td>
      </tr>
   {/foreach}
</table>
</form>
<p/>

<p>&nbsp;</p>
{if $expandableOrgs|count > 0}
   <form method="POST">
      If there are actually more offices than shown above, you may add new ("empty") seats:
      <p/>
      <table style="margin-left: 2em;">
         {foreach from=$expandableOrgs item=org}
            <tr>
               {if     $org == 'cnty'}
                  <td>New county office:</td>
                  <td>
                      <select name="office">
                          <option value="">(choose one)</option>
                          {foreach from=$offices item=office}
                              <option value="{$office.office}">{$office.shortname}</option>
                          {/foreach}
                       </select>
                  </td>
               {elseif $org == 'cnty-cou'}
                  <td>New county commissioner:&nbsp;&nbsp;</td>
                  <td>District #&nbsp; <input type="text" name="seatnum" size="2" style="border: 1px solid;" class="char1"/>&nbsp;&nbsp;</td>
               {elseif $org == 'city'}     <td>New city office:</td>          <td>(select office)</td>
               {elseif $org == 'city-cou'} <td>New city council:</td>         <td>(select ward/subdist, can be 0) (select seatnum)</td>
               {elseif $org == 'town'}     <td>New town office:</td>          <td>(select office)</td>
               {elseif $org == 'town-cou'} <td>New town council:</td>         <td>(select ward/subdist, can be 0) (select seatnum)</td>
               {elseif $org == 'schl-cou'} <td>New school board:</td>         <td>(select ward/subdist, can be 0) (select seatnum)</td>
               {else}
                  <td>New Judge:</td>
                  <td>Seat# <input type="text" name="seatnum" size="2" style="border: 1px solid;" class="char1"/>&nbsp;&nbsp;</td>
               {/if}
            </tr>
         {/foreach}
         <tr>
             <td></td><td align="right">&nbsp;<button>Add</button></td>
         </tr>
      </table>
   </form>
{/if}

<pre>
qsOrgs    ={$qsOrgs}
qsDistrict={$qsDistrict}
qsShow={$qsShow}

{$error}

{$rowText}
</pre>

</body>
</html>
{/nocache}
