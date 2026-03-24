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
   </style>
</head>

<body style="margin-top: 0;">
<h2>Incomplete</h2>

<p/>
The incumbent officials for this entity cannot be displayed, because<br/>
it partly falls under one or more counties that have not been fully imported yet:

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
