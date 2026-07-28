{nocache}
<!DOCTYPE html>
<html lang="en">
<head>
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://gstatic.com" crossorigin>
   <link href="https://googleapis.com" rel="stylesheet">
   <style>
      body, td, li {
        font-family: 'Roboto Flex', sans-serif;
        /* font-size: 0.8rem; */
      }
   </style>
</head>

<body style="margin-top: 0;">
<h2>Incomplete</h2>

<p/>
The incumbent officials for this entity cannot be displayed, because<br/>
it partly falls under one or more counties that have not been imported yet:

<ul>
{foreach from=$rows item=row}
   <li>{$row['name']}</li>
{/foreach}
</ul>


<!--
qsOrgs={$qsOrgs}<br/>
qsDistrict={$qsDistrict}<br/>
qsShow={$qsShow}<br/>
-->

</body>
</html>
{/nocache}
