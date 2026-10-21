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
Count of distinct voter addresses (last 24 hours) = {$count24}

<p/>
(Data from server imported every hour on the half-hour.)

<p/>
<button onClick="window.location.reload();">Refresh</button>

</body>
</html>
{/nocache}
