{nocache}
<!DOCTYPE html>
<html lang="en">
<head>
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://gstatic.com" crossorigin>
   <link href="https://googleapis.com" rel="stylesheet">

   <style>
      body, td, li, div {
         font-family: 'Roboto Flex', sans-serif;
         -webkit-font-smoothing: antialiased;
      }
   </style>

</head>

<body>
<b>Candidates endorsed but NOT reviewed</b>
<table>
   {$counter = 1}
   {foreach from=$rows item=row}
      <tr valign='top'>
         <td align="right">{$counter}&nbsp;&nbsp;</td>
         <td>{$row[0]}</td>
         <td>&nbsp;&nbsp;{$row[1]}</td>
         <td>&nbsp;&nbsp;{$row[2]|escape}</td>
      </tr>
      {$counter = $counter + 1}
   {/foreach}
</table>

</body>
</html>
{/nocache}
