{nocache}
<!DOCTYPE html>
<html lang="en">
<head>
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto">
   <link rel="stylesheet" href="photo2.css">
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

      function changed(name) {
         let fc = document.getElementById("fieldsChanged");
         fc.value = fc.value + name + ",";
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

      function partyMenuShow(base) {
         let sel = document.getElementById(base + "sel");
         sel.style.visibility = 'visible';
         sel.focus();
         sel.showPicker();
         sel.selectedIndex = -1; /* so that ANY selection fires onChange */
      }

      function partyMenuHide(base) {
         let sel = document.getElementById(base + "sel");
         sel.style.visibility = 'hidden';
      }

      function partyMenuSet(base, name) {
         let sel = document.getElementById(base + "sel");
         let inp = document.getElementById(base + "inp");
         inp.value = sel.value;
         partyMenuHide(base);
         changed(name);
      }

      function photoOpen(canId, name, headshot) {
         if (name == '') {
             confirm("Please enter a name first.");
             return false;
         }
         name = name.replaceAll("'", "\\\'");   /* E.g. Diane O'Connell */
         name = encodeURIComponent(name);

         var photoDiv = document.getElementById('photoDiv');
         photoDiv.style.display = 'block';
            
         var photoFrame = document.getElementById('photoFrame');
         photoFrame.src = "photo.php?canId=" + canId + "&name=" + name + "&headshot=" + headshot;
         return false;
      }

      function descOpen(canId, name) {
          var descDiv = document.getElementById('descDiv');
          descDiv.style.display = 'block';

          var descFrame = document.getElementById('descFrame');
          var candidateNameSpan = descFrame.contentWindow.document.getElementById('candidateName');
          candidateNameSpan.innerHTML = name;

          var canIdField = descFrame.contentWindow.document.getElementById('canId');
          canIdField.value = canId;

          var longDesc = document.getElementById(canId + "-description");
          descFrame.contentWindow.setText(longDesc.innerHTML);
      }


      /* Photo upload div close button */
      window.addEventListener("message",
          function (e) {
              var photoDiv = document.getElementById('photoDiv');
              photoDiv.style.display = 'none';

              if (e.data.startsWith("closePhotoDiv:")) {
                  var parts = e.data.split(":"); /* 1 is canId;  2 is filename */
                  var img = document.getElementById("photo-" + parts[1]);
                  img.src = "PHOTOS/" + parts[2];

                  var fieldChanged = "i:" + parts[1] + ":headshot";
                  var uploadedPhotoInput = document.getElementsByName(fieldChanged)[0];
                  uploadedPhotoInput.value = parts[2];
                  changed(fieldChanged);
              }
          }
      );


   </script>
</head>

<body style="margin-top: 0;"  onLoad="setShrinkExpandButton();">
<form id="mainForm" method="post" action="candidates.php?county={$county}&orgs={$qsOrgs}&district={$qsDistrict}&show={$qsShow}">
<input type="hidden" name="fieldsChanged" id="fieldsChanged" value="" />

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
            <input type="button" onClick="submitMainForm();"; return false;" value=" Save Changes " class="button" />
         {/if}
      </td>
   </tr>
   <tr>
      <td class="th2">&nbsp;Office</td>
      {if $showDistrict } <td class="th2">Dist</td>    {/if}
      {if $showSubDist  } <td class="th2">{$regionColumnName}</td> {/if}
      {if $showSeat     } <td class="th2">S#</td>      {/if}
      <td class="th2">TL</td>
      <td class="th2a">&nbsp;Name</td>
      <td class="th2a">&nbsp;Photo</td>
      <td class="th2a">&nbsp;Statement</td>
      <td class="th2a" colspan='2'>Web</td>
      <td class="th2a" colspan='2'>Email</td>
      <td class="th2a" colspan='2'>Phone</td>
   </tr>
   {foreach from=$rows item=row}
      <tr valign="top">
         <td style="white-space: nowrap;"     class="smaller">&nbsp;{$row['shortname']}</td>
         {if $showDistrict} <td align='right' class="smaller">{$row['district']}</td> {/if}
         {if $showSubDist}
             <!-- <td align='right' class="smaller"> -->
             <td><input name="s:{$row['id']}:subdist"   type="text"  size="1"  class="char1 number"  pattern="[0-9]*" onChange="changed(this.name);"
                                 value="{$row['subdist']}"/></td>
         {/if}
         {if $showSeat}
            {if $row['seatmax'] * 1 != 1 } <td align='right' class="smaller">{$row['seatnum']}</td> {else} <td></td> {/if}
         {/if}
         <td><input name="s:{$row['id']}:termlen"   type="text"  size="1"  class="char1 number"  pattern="[0-9]*" onChange="changed(this.name);"  value="{$row['termlen']}"/></td>
         <td><input name="i:{$row['can_id']}:name"  type="text"  size="22"                                        onChange="changed(this.name);"  value="{$row['name']}"/></td>
         <td>
            {if $row['headshot'] != ''}
               <a href="#" onClick="return photoOpen({$row['can_id']}, '{$row['name']}', '{$row['headshot']}');"
                  ><img id='photo-{$row['can_id']}' src="PHOTOS/{$row['headshot']}" width="40"/></a>
            {else}
               <a href="#" onClick="return photoOpen({$row['can_id']}, '{$row['name']}', '');"
                  ><img id='photo-{$row['can_id']}' src="IMG/noPerson2.png"         width="40"/></a>
            {/if}
            <input type="hidden" name="i:{$row['can_id']}:headshot" value="{$row['headshot']}"/>
         </td>

         <td>
            <div id='{$row['can_id']}-shortdesc' onClick="descOpen({$row['can_id']}, '{$row['name']}');">{$row['shortdesc']}</div>
            <div id='{$row['can_id']}-description' style="display: none;" name="i:{$row['can_id']}:description"
                >{$row['description']}</div>
         </td>

         <td style="vertical-align: bottom;">
             {if $row['url'] != ''}
                <a href="{$row['url']}" target="_blank"><img src="external3.png" width="15"></a>
             {/if}
         </td>

         <td class="col_web"><input type="text" name="i:{$row['can_id']}:web"  size="60" value="{$row['web']}"
             style="position: relative; z-index: 3;"
             onFocus="expand(this);" onBlur="shrink(this);" onChange="changed(this.name);" /></td>

         <!-- <td class="spacer" style="position: relative; z-index: 4;">&nbsp;&nbsp;</td> -->

         <td class="col_email"><input type="text" name="i:{$row['can_id']}:email" size="45"  value="{$row['email']}"
             style="position: relative; z-index: 5;"
             onFocus="expand(this);" onBlur="shrink(this);" onChange="changed(this.name);" /></td>

         <td class="spacer" style="position: relative; z-index: 6;">&nbsp;&nbsp;</td>

         <td class="col_phone"><input name="i:{$row['can_id']}:phone"     type="text"  size="18" class="char12"  onChange="changed(this.name);"
               value="{$row['phone']}" style="position: relative; z-index: 7;" /></td>

         <!-- <td class="spacer" style="position: relative; z-index: 8;">&nbsp;&nbsp;</td> -->
      </tr>
   {/foreach}
</table>
</form>
<p/>



<pre>
   <!--
qsOrgs    ={$qsOrgs}
qsDistrict={$qsDistrict}
qsShow={$qsShow}
fieldsChanged={$fieldsChanged}
-->

{$error}
{$rowText}
</pre>

<!--- divs for pop-open boxes: description, photos -->
<div id="descDiv" class="descDivCss" style="display: none;">
   <iframe id='descFrame' class="descFrameCss" src="summer.html"></iframe>
   <input type='hidden' id='descDivRownum' />
</div>

<div id="photoDiv" class="photoDivCss" style="display: none;">
   <center>
      <iframe id="photoFrame" class="photoFrameCss" src="photo.php"></iframe>
   </center>
</div>

</body>
</html>
{/nocache}
