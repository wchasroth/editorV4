{nocache}
<!DOCTYPE html>
<html lang="en">
<head>
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto">

   <style>
      body, td, li, div {
         font-family: 'Roboto', sans-serif;
         -webkit-font-smoothing: antialiased;
      }
      #paste-zone {
         max-width: 5em;
         height: 2em;
         border: 2px dashed #ccc;
         color: #666;
         padding: 0.5em;
      }
   </style>

   <script>
       let photoPastedSuccess = false;
       let photoPastedName    = "";

       /* This is pretty schizoid.  It acts one way for copy-paste, and another for form-post-upload.  Sigh. */
       function closeMe(canId, headshot, usecropped) {
           if (photoPastedSuccess) parent.postMessage("closePhotoDiv:" + canId + ":" + photoPastedName, '{$parent}');
           else {
              {if $photoChanged == 1} parent.postMessage("closePhotoDiv:" + canId + ":" + headshot + ":" + usecropped, '{$parent}');
              {else}                  parent.postMessage("close", '{$parent}');
              {/if}
           }
       }

       function confirmFileSelected() {
           var photo = document.getElementById('uploadphoto');
           if (photo.files.length != 1) {
               alert("Please choose a file first.");
               return false;
           }
           return true;
       }
   </script>
</head>

<body>
<form action='photo.php?canId={$canId}&name={$encodedName}&headshot={$headshot}'
      method='POST'  enctype='multipart/form-data'>

<table>
   <tr valign='top'>
      <td>
         <center>
         <div id="canPhoto">
            {if $headshot == ''}
               <img id='canPhoto' src="IMG/noPerson2.png"      width='200'/>
            {else}
               <a href="PHOTOS_CAN/{$headshot}" target="_blank"><img id='canPhoto' src="PHOTOS_CAN/{$headshot}" style="max-width: 200px; width: auto; max-height: 190px;"/></a>
            {/if}
         </div>
         <div>
            {if $headshot != ''  &&  $headcropped == 1}
               <a href="PHOTOS_CAN/{$cropshot}" target="_blank"><img id='canPhoto' src="PHOTOS_CAN/{$cropshot}" style="max-width: 200px; width: auto; max-height: 190px;"/></a>
            {/if}
         </div>
         <!--
         <i style="font-size: 90%;">(You can click on the photo to see the full-sized version.)</i>
         -->
         </center>
      </td>
      <td>&nbsp; </td>
      <td>
         {if $headshot == ''}
            There is no photo for {$name}.&nbsp;
            You can add a photo in one of two ways:<p/>
         {elseif $headcropped == 1}
            The top image is the current photo for {$name}.&nbsp;
            The bottom is an automated headshot.
         {else}
            This is the current photo for {$name}.&nbsp;
            You can replace it in several ways:<p/>
         {/if}

         <p/>
         {$option = 1}
         {if $headcropped == 1}
            <b>Option {$option}:</b>
               <a href="photo.php?canId={$canId}&name={$encodedName}&headshot={$cropshot}&usecropped=1">Use the automated headshot</a> instead.
            {$option = $option + 1}
         {/if}

         <p/>
         <b>Option {$option}:</b> copy-paste a photo
         <span id="paste-zone" tabindex="0">here</span>
         {$option = $option + 1}

         <p/>
         <b>Option {$option}:</b> upload a photo:
         <ul style="padding-left: 1.4em;">
            <li>Click&nbsp;<input type='file' name='uploadphoto' id='uploadphoto' /></li>
            <li style="margin-top: 0.4em;">Then&nbsp;<input type='submit' onClick="return confirmFileSelected();" value='Upload photo' /></li>
         </ul>

         When you are done, click on
         <button onClick="closeMe({$canId}, '{$headshot}', {$usecropped});">Close window</button>

         <p/>
         (Remember to click on <b>Save Changes</b> at the very top of the page!)

         <p/>
         <div id="status"></div>
      </td>
   </tr>
</table>
</form>
<script>
   const pasteZone  = document.getElementById('paste-zone');
   const statusDiv  = document.getElementById('status');
   const previewDiv = document.getElementById('canPhoto');

   pasteZone.addEventListener('paste', function(e) {  // Listen for paste event on the zone
      // Access clipboard items
      const items = (e.clipboardData || e.originalEvent.clipboardData).items;

      for (let i=0;  i < items.length;  i++) {
         if (items[i].type.indexOf('image') !== -1) {   // Look specifically for images
            const blob    = items[i].getAsFile();
            const reader  = new FileReader();
            reader.onload = function(event) {
               const base64Image = event.target.result;
               confirm ("base64Image: " + base64Image);

               {literal}
                  previewDiv.innerHTML = `<img src="${base64Image}" width=200 style="max-width:200px; max-heigh: 200px;"/>`;
               {/literal}

               sendToPHP(base64Image);
            };

            reader.readAsDataURL(blob);
            break; // Stop looking after finding the first image
         }
      }
   });

   function sendToPHP(base64Data) {
      const formData = new FormData();
      formData.append('pasted_image', base64Data);

      fetch('pasteImage.php?can_id={$canId}&name={$encodedName}', {
         method: 'POST',
         body: formData
      })
      .then(response => response.json())
      .then(data => {
         {literal}
            if (data.success) {
               photoPastedSuccess = true;
               photoPastedName    = data.file;
               /* statusDiv.innerHTML = `<span style="color:green;">Success! Saved as <strong>${data.file}</strong></span>`; */
            } else {
               statusDiv.innerHTML = `<span style="color:red;">Error: ${data.error}</span>`;
            }
         {/literal}
      })
      .catch(err => {
         statusDiv.innerHTML = `<span style="color:red;">Server communication error.</span>`;
      });
   }
</script>

</body>
</html>
{/nocache}
