{nocache}
<!DOCTYPE html>
<html lang="en">
<head>
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://gstatic.com" crossorigin>
   <link href="https://googleapis.com" rel="stylesheet">

   <link rel="stylesheet" href="photo4.css">
   <link rel="stylesheet" href="editor.css">
</head>

<body style="margin-top: 0;"/>

error={$error}
<p/>

{foreach from=$rows item=row}
   {$row['id']}<br/>
   {$row['tbefore']|escape}<br/>
   <span style="color: blue;">{$row['tafter']|escape}</span><br/>
   <hr/>
{/foreach}

</body>
</html>
{/nocache}
