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
   </style>

   <script>
       function closeMe(canId, headshot) {
           parent.postMessage("closePhotoDiv:" + canId + ":" + headshot, '{$parent}');
       }
   </script>
</head>

<body>
<form action='photo.php?canId={$canId}&name={$name}&headshot={$headshot}'
      method='POST'  enctype='multipart/form-data'>

<table>
   <tr valign='top'>
      <td>
         {if $headshot == ''} <img src="IMG/noPerson2.png" width='200'/>
         {else}               <img src="PHOTOS/{$headshot}" width='200'/>
         {/if}
      </td>
      <td>&nbsp; </td>
      <td>
         {if $headshot == ''}
            There is no photo for {$name}.&nbsp;
            To upload a photo from your computer:<p/>
         {else}
            This is the current photo for {$name}.&nbsp;
            To replace it with a different photo from your computer:</p>
         {/if}

         1. Click on&nbsp; <input type='file' name='uploadphoto' id='uploadphoto' />
         <p/>
         2. Click on&nbsp; <input type='submit' onClick="return confirmFileSelected();" value='Upload photo' />

         <p/>
         When you are done, click on
         <button onClick="closeMe();">Close window</button>

         <p/>
         (Remember to click on <b>Submit</b> at the very top of the page, to save
         all of your changes!)
      </td>
   </tr>
</table>
</form>

<pre>
canId={$canId}
name ={$name}
head ={$headshot}
</pre>
</body>
</html>
{/nocache}
