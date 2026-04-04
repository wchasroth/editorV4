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
   </style>
   <script>
      function flipArrow(idBase) {
         let arrow = document.getElementById("A" + idBase);  // The right/down arrow
         let ul    = document.getElementById("C" + idBase);  // the <ul> underneath it
         if (arrow.innerHTML.charCodeAt(0) == 9654) { arrow.innerHTML = '&#9660;';   ul.style.display = "block"; }
         else                                       { arrow.innerHTML = '&#9654;';   ul.style.display = "none";  }
         return false;
      }

      function loadOfficials(orgs, district='', show='', link=1) {
         let officialsFrame = window.parent.frames[1];
         if (officialsFrame.document != null) {
             let fc = officialsFrame.document.getElementById("fieldsChanged");
             if (fc != null  &&  fc.value != ""  &&
                 ! confirm("There are un-saved changes!\n\nPress OK if you're sure you want to proceed.\nPress Cancel to stay on the same page.")) return;
         }
         if (link == 1) officialsFrame.location.href = "officials.php?orgs="  + orgs + "&district=" + district + "&show=" + show;
         else           officialsFrame.location.href = "incomplete.php?orgs=" + orgs + "&district=" + district + "&show=" + show;
         return false;
      }
   </script>
</head>

<body style='max-width: 65em; background-color: #CCE6FF;'>
<ul style="padding-left: 0;">
   {if $allowedState }
      <li><a href="#" onClick="return loadOfficials('us,us-vp,us-sen,us-hou',        '', 'ds');" class="child">US</a></li>
      <li><a href="#" onClick="return loadOfficials('mi,mi-lt,mi-sos,mi-ag,crt-sup', '', 's');" class="child">MI</a></li>
      <li><a href="#" onClick="return loadOfficials('mi-sen', '', 'd');" class="child">MI Senate</a></li>
      <li><a href="#" onClick="return loadOfficials('mi-hou', '', 'd');" class="child">MI House</a></li>
      <li><a href="#" onClick="return loadOfficials('mi-boe,mi-msu,mi-um,mi-wsu', '', 's');" class="child">MI Education</a></li>
   {/if}

   {foreach from=$counties item=county}
      <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}');"
           ><span id='A{$county.cnty[1]}' class="arrow">&#9654;</span> {$county.cnty[2]}</a>
         <ul id="C{$county.cnty[1]}" style="display: none;">
            <li><span class="arrow">&nbsp;</span>
                <a href="#" onClick="return loadOfficials('cnty,cnty-com', '{$county.cnty[1]}', 'w');" class="child">County Offices</a>
                ({$county.cnty[5]})
                {if $county.cnty[4] == 1} <img src="green-check.png" width="12"/>{/if}
            </li>

            <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}Y');"
                   ><span id='A{$county.cnty[1]}Y' class="arrow">&#9654;</span> Cities</a>
               <ul id="C{$county.cnty[1]}Y" style="display: none;">
                  {foreach from=$county.city item=city}
                     <li>
                        {if $city[3] == 1}
                           <a href="#" onClick="return loadOfficials('city,city-cou', '{$city[1]}', 'ws', 1);" class="child"     >{$city[2]}</a>
                        {else}
                           <a href="#" onClick="return loadOfficials('city,city-cou', '{$city[1]}', 'ws', 0);" class="incomplete">{$city[2]}</a>
                        {/if}
                        ({$city[5]})
                        {if $city[4] == 1}  <img src="green-check.png" width="12"/> {/if}
                     </li>
                  {/foreach}
               </ul>
            </li>

            <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}P');"
                ><span id='A{$county.cnty[1]}P' class="arrow">&#9654;</span> Townships</a>
                <ul id="C{$county.cnty[1]}P" style="display: none;">
                    {foreach from=$county.town item=town}
                        <li>
                           {if $town[3] == 1}
                               <a href="#" onClick="return loadOfficials('town,town-cou', '{$town[1]}', 'ws', 1);" class="child"     >{$town[2]}</a>
                           {else}
                               <a href="#" onClick="return loadOfficials('town,town-cou', '{$town[1]}', 'ws', 0);" class="incomplete">{$town[2]}</a>
                           {/if}
                           ({$town[5]})
                           {if $town[4] == 1}  <img src="green-check.png" width="12"/> {/if}
                        </li>
                    {/foreach}
                </ul>
            </li>

             {if $county.vil|count > 0 }
               <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}V');"
                     ><span id='A{$county.cnty[1]}V' class="arrow">&#9654;</span> Villages</a>
                  <ul id="C{$county.cnty[1]}V" style="display: none";>
                     {foreach from=$county.vil item=vil}
                         <li>
                            {if $vil[3] == 1}
                               <a href="#" onClick="return loadOfficials('vil,vil-cou', '{$vil[1]}', 's', 1);" class="child"     >{$vil[2]}</a>
                            {else}
                               <a href="#" onClick="return loadOfficials('vil,vil-cou', '{$vil[1]}', 's', 0);" class="incomplete">{$vil[2]}</a>
                            {/if}
                            ({$vil[5]})
                            {if $vil[4] == 1}  <img src="green-check.png" width="12"/> {/if}
                         </li>
                     {/foreach}
                  </ul>
               </li>
            {/if}

            <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}S');"
                   ><span id='A{$county.cnty[1]}S' class="arrow">&#9654;</span> School Districts</a>
               <ul id="C{$county.cnty[1]}S" style="display: none";>
                  {foreach from=$county.schl item=schl}
                      <li>
                         {if $schl[3] == 1}
                            <a href="#" onClick="return loadOfficials('schl-cou', '{$schl[1]}', 's', 1);" class="child"     >{$schl[2]}</a>
                         {else}
                            <a href="#" onClick="return loadOfficials('schl-cou', '{$schl[1]}', 's', 0);" class="incomplete">{$schl[2]}</a>
                         {/if}
                         ({$schl[5]})
                         {if $schl[4] == 1}  <img src="green-check.png" width="12"/> {/if}
                      </li>
                  {/foreach}
               </ul>
            </li>

            <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}C');"
                 ><span id='A{$county.cnty[1]}C' class="arrow">&#9654;</span> Comm Colleges</a>
                 <ul id="C{$county.cnty[1]}C" style="display: none";>
                     {foreach from=$county.comcol item=col}
                         <li>
                            {if $col[3] == 1}
                                <a href="#" onClick="return loadOfficials('comcol-cou', '{$col[1]}', 's', 1);" class="child"     >{$col[2]}</a>
                            {else}
                                <a href="#" onClick="return loadOfficials('comcol-cou', '{$col[1]}', 's', 0);" class="incomplete">{$col[2]}</a>
                            {/if}
                            ({$col[5]})
                            {if $col[4] == 1}  <img src="green-check.png" width="12"/> {/if}
                         </li>
                     {/foreach}
                 </ul>
            </li>

            <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}T');"
               ><span id='A{$county.cnty[1]}T' class="arrow">&#9654;</span> Courts</a>
               <ul id="C{$county.cnty[1]}T" style="display: none";>
                  {foreach from=$county.crt item=crt}
                     <li>
                        <a href="#" onClick="return loadOfficials('{$crt[3]}', '{$crt[1]}', 's');" class="child">{$crt[2]}</a>
                         ({$crt[5]})
                         {if $crt[4] == 1}  <img src="green-check.png" width="12"/> {/if}
                     </li>
                  {/foreach}
               </ul>
            </li>
         </ul>
      </li>
   {/foreach}
</ul>

</body>
</html>
{/nocache}
