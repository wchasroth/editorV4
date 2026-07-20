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
      .green { color: darkgreen; }
      .endorsed { color: green;  font-weight: bold; }
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

<p>&nbsp;</p>
<ul style="padding-left: 0;">
    {if $allowedState }
        <li>
            <a href="#" onClick="return loadOfficials(999, 'us,us-vp,us-sen,us-hou',        '', 'ds');" class="child">US</a>
            ({$topOffices.us[1]}/{$topOffices.us[2]}) <span class="endorsed">{$topOffices.us[0]}</span>
            {if $topOffices.us[1] == $topOffices.us[2]  &&  $topOffices.us[2] > 0} <img src="green-check.png" width="12"/> {/if}
        </li>
        <li>
            <a href="#" onClick="return loadOfficials(999, 'mi,mi-lt,mi-sos,mi-ag,crt-sup', '', 's');" class="child">MI</a>
            ({$topOffices.mi[1]}/{$topOffices.mi[2]}) <span class="endorsed">{$topOffices.mi[0]}</span>
            {if $topOffices.mi[1] == $topOffices.mi[2]  &&  $topOffices.mi[2] > 0} <img src="green-check.png" width="12"/> {/if}
        </li>
        <li>
            <a href="#" onClick="return loadOfficials(999, 'mi-sen', '', 'd');" class="child">MI Senate</a>
            ({$topOffices.mi_sen[1]}/{$topOffices.mi_sen[2]}) <span class="endorsed">{$topOffices.mi_sen[0]}</span>
            {if $topOffices.mi_sen[1] == $topOffices.mi_sen[2]  &&  $topOffices.mi_sen[2] > 0} <img src="green-check.png" width="12"/> {/if}
        </li>
        <li>
            <a href="#" onClick="return loadOfficials(999, 'mi-hou', '', 'd');" class="child">MI House</a>
            ({$topOffices.mi_hou[1]}/{$topOffices.mi_hou[2]}) <span class="endorsed">{$topOffices.mi_hou[0]}</span>
            {if $topOffices.mi_hou[1] == $topOffices.mi_hou[2]  &&  $topOffices.mi_hou[2] > 0} <img src="green-check.png" width="12"/> {/if}
        </li>
        <li>
            <a href="#" onClick="return loadOfficials(999, 'mi-boe,mi-msu,mi-um,mi-wsu', '', 's');" class="child">MI Education</a>
            ({$topOffices.mi_boe[1]}/{$topOffices.mi_boe[2]}) <span class="endorsed">{$topOffices.mi_boe[0]}</span>
            {if $topOffices.mi_boe[1] == $topOffices.mi_boe[2]  &&  $topOffices.mi_boe[2] > 0} <img src="green-check.png" width="12"/> {/if}
        </li>
    {/if}

    {foreach from=$counties item=county}
        <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}');"
            ><span id='A{$county.cnty[1]}' class="arrow">&#9654;</span> {$county.cnty[2]}</a>
            <!-- (E{$county.grd_end},R{$county.grd_rev} / {$county.grd_den}) -->
            ({$county.grd_rev}/{$county.grd_den}) <span class="endorsed">{$county.grd_end}</span>
            {if $county.grd_rev == $county.grd_den  &&  $county.grd_den > 0} <img src="green-check.png" width="12"/> {/if}
            <ul id="C{$county.cnty[1]}" style="display: none;">
                <li><span class="arrow">&nbsp;</span>
                    <a href="#" {if $county.cnty[7] == 1} class="green" {/if}
                       onClick="return loadOfficials({$county.cnty[1]}, 'cnty,cnty-com', '{$county.cnty[1]}', 'w');" class="child">County Offices</a>
                    ({$county.cnty[5]}/{$county.cnty[4]}) <span class="endorsed">{$county.cnty[6]}</span>
                    {if $county.cnty[5] == $county.cnty[4]  &&  $county.cnty[4] > 0} <img src="green-check.png" width="12"/> {/if}
                </li>

                <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}Y');"
                    ><span id='A{$county.cnty[1]}Y' class="arrow">&#9654;</span> Cities</a>
                    ({$county.city_rev}/{$county.city_den}) <span class="endorsed">{$county.city_end}</span>
                    {if $county.city_rev == $county.city_den  &&  $county.city_den > 0} <img src="green-check.png" width="12"/> {/if}
                    <ul id="C{$county.cnty[1]}Y" style="display: none;">
                        {foreach from=$county.city item=city}
                            <li>
                                <a href="#"  {if $city[7] == 1} class="green" {/if}
                                   onClick="return loadOfficials({$county.cnty[1]}, 'city,city-cou', '{$city[1]}', 'ws', 1);" class="child"     >{$city[2]}</a>
                                ({$city[5]}/{$city[4]}) <span class="endorsed">{$city[6]}</span>
                                {if $city[5] == $city[4]  &&  $city[4] > 0} <img src="green-check.png" width="12"/> {/if}
                            </li>
                        {/foreach}
                    </ul>
                </li>

                <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}P');"
                    ><span id='A{$county.cnty[1]}P' class="arrow">&#9654;</span> Townships</a>
                    ({$county.town_rev}/{$county.town_den}) <span class="endorsed">{$county.town_end}</span>
                    {if $county.town_rev == $county.town_den  &&  $county.town_den > 0} <img src="green-check.png" width="12"/> {/if}
                    <ul id="C{$county.cnty[1]}P" style="display: none;">
                        {foreach from=$county.town item=town}
                            <li>
                                <a href="#"  {if $town[7] == 1} class="green" {/if}
                                   onClick="return loadOfficials({$county.cnty[1]}, 'town,town-cou', '{$town[1]}', 'ws', 1);" class="child"     >{$town[2]}</a>
                                ({$town[5]}/{$town[4]}) <span class="endorsed">{$town[6]}</span>
                                {if $town[5] == $town[4]  &&  $town[4] > 0} <img src="green-check.png" width="12"/> {/if}
                            </li>
                        {/foreach}
                    </ul>
                </li>

                {if $county.vil|count > 0 }
                    <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}V');"
                        ><span id='A{$county.cnty[1]}V' class="arrow">&#9654;</span> Villages</a>
                        ({$county.vil_rev}/{$county.vil_den}) <span class="endorsed">{$county.vil_end}</span>
                        {if $county.vil_rev == $county.vil_den  &&  $county.vil_den > 0} <img src="green-check.png" width="12"/> {/if}
                        <ul id="C{$county.cnty[1]}V" style="display: none";>
                            {foreach from=$county.vil item=vil}
                                <li>
                                    <a href="#"  {if $vil[7] == 1} class="green" {/if}
                                       onClick="return loadOfficials({$county.cnty[1]}, 'vil,vil-cou', '{$vil[1]}', 's', 1);" class="child"     >{$vil[2]}</a>
                                    ({$vil[5]}/{$vil[4]}) <span class="endorsed">{$vil[6]}</span>
                                    {if $vil[5] == $vil[4]  &&  $vil[4] > 0} <img src="green-check.png" width="12"/> {/if}
                                </li>
                            {/foreach}
                        </ul>
                    </li>
                {/if}

                <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}S');"
                    ><span id='A{$county.cnty[1]}S' class="arrow">&#9654;</span> School Districts</a>
                    ({$county.schl_rev}/{$county.schl_den}) <span class="endorsed">{$county.schl_end}</span>
                    {if $county.schl_rev == $county.schl_den  &&  $county.schl_den > 0} <img src="green-check.png" width="12"/> {/if}
                    <ul id="C{$county.cnty[1]}S" style="display: none";>
                        {foreach from=$county.schl item=schl}
                            <li>
                                <a href="#"  {if $schl[7] == 1} class="green" {/if}
                                   onClick="return loadOfficials({$county.cnty[1]}, 'schl-cou', '{$schl[1]}', 's', 1);" class="child"     >{$schl[2]}</a>
                                ({$schl[5]}/{$schl[4]}) <span class="endorsed">{$schl[6]}</span>
                                {if $schl[5] == $schl[4]  &&  $schl[4] > 0} <img src="green-check.png" width="12"/> {/if}
                            </li>
                        {/foreach}
                    </ul>
                </li>

                <li><a href='#' class="parent" onClick="return flipArrow('{$county.cnty[1]}T');"
                    ><span id='A{$county.cnty[1]}T' class="arrow">&#9654;</span> Courts</a>
                    ({$county.crt_rev}/{$county.crt_den}) <span class="endorsed">{$county.crt_end}</span>
                    {if $county.crt_rev == $county.crt_den  &&  $county.crt_den > 0} <img src="green-check.png" width="12"/> {/if}
                    <ul id="C{$county.cnty[1]}T" style="display: none";>
                        {foreach from=$county.crt item=crt}
                            <li>
                                <a href="#" {if $crt[7] == 1} class="green" {/if}
                                   onClick="return loadOfficials({$county.cnty[1]}, '{$crt[3]}', '{$crt[1]}', 's');" class="child">{$crt[2]}</a>
                                ({$crt[5]}/{$crt[4]}) <span class="endorsed">{$crt[6]}</span>
                                {if $crt[5] == $crt[4]} <img src="green-check.png" width="12"/> {/if}
                            </li>
                        {/foreach}
                    </ul>
                </li>
            </ul>
        </li>
    {/foreach}
</ul>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>

</body>
</html>
{/nocache}
