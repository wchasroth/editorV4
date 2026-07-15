{nocache}
<!DOCTYPE html>
<html lang="en">
<head>
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto">
   <style>
      body, td, li {
        font-family: 'Roboto';
        font-size: 0.8rem;
      }
      ul {
         list-style: none;
         padding-left: 2.0em;
      }
      .parent {
         text-decoration: none;
         color: inherit;
      }
      .child {
         color: inherit;
      }
      .incomplete {
         color: red;
      }
      .arrow {
         font-size: 80%;
         display: inline-block;
         width: 0.9em;
      }
      .green {
          color: darkgreen;
      }
   </style>
   <script>
      function flipArrow(idBase) {
         let arrow = document.getElementById("A" + idBase);  // The right/down arrow
         let ul    = document.getElementById("C" + idBase);  // the <ul> underneath it
         if (arrow.innerHTML.charCodeAt(0) == 9654) { arrow.innerHTML = '&#9660;';   ul.style.display = "block"; }
         else                                       { arrow.innerHTML = '&#9654;';   ul.style.display = "none";  }
         return false;
      }

      function loadOfficials(county, orgs, district='', show='', link=1) {
         let officialsFrame = window.parent.frames[1];
         if (officialsFrame.document != null) {
             let fc = officialsFrame.document.getElementById("fieldsChanged");
             if (fc != null  &&  fc.value != ""  &&
                 ! confirm("There are un-saved changes!\n\nPress OK if you're sure you want to proceed.\nPress Cancel to stay on the same page.")) return;
         }
         officialsFrame.location.href = "candidates.php?county="  + county + "&orgs=" + orgs + "&district=" + district + "&show=" + show;
         return false;
      }

      function forceReload() {
          document.body.innerHTML = "<p>&nbsp;</p>&nbsp;&nbsp;<i>(...working...)</i>";
          reloader = window.location.pathname + "?" + new Date().getTime();
          window.location.href = reloader;
      }
   </script>
</head>

<body style='max-width: 65em; background-color: #CCE6FF;'>
<button style="position: fixed; top: 10px; right: 20px;" onClick="forceReload();">Refresh</button>

<ul style="padding-left: 0;">
   {if $allowedState }
      <li>
          <a href="#" onClick="return loadOfficials(999, 'us,us-vp,us-sen,us-hou',        '', 'ds');" class="child">US</a>
            {$topOffices.us[0]}-({$topOffices.us[1]}/{$topOffices.us[2]})
      </li>
      <li>
          <a href="#" onClick="return loadOfficials(999, 'mi,mi-lt,mi-sos,mi-ag,crt-sup', '', 's');" class="child">MI</a>
            {$topOffices.mi[0]}-({$topOffices.mi[1]}/{$topOffices.mi[2]})
      </li>
   {/if}

</ul>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>

</body>
</html>
{/nocache}
