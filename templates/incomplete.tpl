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
      .smaller { font-size: 85%; }
      .zebra {
         tr:nth-child(even) {
            background-color: #d2d2d2;
         }
         tr:hover { background-color: aquamarine; }
         td,th { padding-right: 0.5em;  padding-top: 0.1em;  padding-bottom: 0.1em;}
      }
      .header { font-weight: bold; }
      .char1  { width: 1em; }
      .char4  { width: 2.75em; }
      .char7  { width: 4em; }
      .char12 { width: 7em; }
      .number { text-align: right; }
      .th1 {
         position: sticky;
         top: 0.0em;
         background-color: white;
         z-index: 100;
         text-align: left;
         font-size: 120%;
         height: 2em;
      }
      .th2 {
         position: sticky;
         top: 2.6em;
         background-color: #d2d2d2;
         z-index: 100;
         text-align: left;
         font-weight: bold;
      }
      .expandable {
         transition: width 0.3s ease-in-out;
      }
      .expandable:focus {
         width: 20em;
      }
      input {
         /* background-color: inherit; border: 1px solid #000; */
         background-color: inherit; border: 0;
      }
      .button {
         background-color: #0d6dfb;
         color: white;
         border-radius: 5px;
         border: none;
         height: 1.8em;
      }
   </style>
   <script>
      function shrinkExpandLeftPanel() {
         let leftFrame = parent.document.getElementById('f1');
         let button = document.getElementById('shrinkExpand');
         if (button.src.includes("shrink")) {
            leftFrame.style.display = "none";
            button.src = "expand-10-48.png";
         }
         else {
            leftFrame.style.display = "block";
            button.src = "shrink-10-48.png";
         }
      }

      function setShrinkExpandButton() {
         let leftFrame = parent.document.getElementById('f1');
         if (leftFrame.style.display == "none") {
            let button = document.getElementById('shrinkExpand');
            button.src = "expand-10-48.png";
         }
      }

   </script>
</head>

<body style="margin-top: 0;"  onLoad="setShrinkExpandButton();">

qsOrgs={$qsOrgs}<br/>
qsDistrict={$qsDistrict}<br/>
qsShow={$qsShow}<br/>

</body>
</html>
{/nocache}
