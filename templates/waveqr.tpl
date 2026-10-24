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
Number of sessions, wave redirects, and qr redirects, per day.
<p/>
<table>
   <tr>
      <td><b>Date</b></td>
      <td><b>Sessions</b></td>
      <td>&nbsp;<b>Waves</b></td>
      <td>&nbsp;<b>QR's</b></td>
   </tr>

   {foreach from=$rows item=row}
      <tr>
         <td>{$row['date']}</td>                 <td align='right'>{$row['session']}</td>
         <td align='right'>{$row['wave']}</td>   <td align='right'>{$row['qr']}</td>
      </tr>
   {/foreach}
</table>

<p/>

<p/>
<button onClick="window.location.reload();">Refresh</button>

</body>
</html>
{/nocache}
