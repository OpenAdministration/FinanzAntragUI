<table class="table table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Bezeichnung</th>
      <th>Ersteller</th>
      <th>Status</th>
      <th>letztes Update</th>
    </tr>
  </thead>
  <tbody>
    <?php

foreach ($antraege as $type => $l0) {
  $classConfig = getFormClass($type);
  if ($classConfig === false) continue;

  $classTitle = "{$type}";
  if (isset($classConfig["title"]))
    $classTitle = "[{$type}] {$classConfig["title"]}";

  $title = "{$classTitle}";
  echo "<tr><th colspan=\"5\">".htmlspecialchars($title)."</th></tr>\n";

  foreach ($l0 as $revision => $l1) {
    $revConfig = getFormConfig($type, $revision);
    if ($revConfig === false) continue;

    $classTitle = "{$type}";
    if (isset($classConfig["title"]))
      $classTitle = "[{$type}] {$classConfig["title"]}";

#    $revTitle = "{$revision}";
#    if (isset($revConfig["revisionTitle"]))
#      $revTitle = "[{$revision}] {$revConfig["revisionTitle"]}";

#    $title = "{$classTitle} - {$revTitle}";
    $title = "{$classTitle}";

    if (!isset($revConfig["captionField"]))
      $revConfig["captionField"] = [];
    if (!is_array($revConfig["captionField"]))
      $revConfig["captionField"] = [ $revConfig["captionField"] ];

#    echo "<tr><th colspan=\"5\">".htmlspecialchars($title)."</th></tr>\n";

    foreach ($l1 as $i => $antrag) {
      echo "<tr>";
      echo "<td>".htmlspecialchars($antrag["id"])."</td>";
      $caption = getAntragDisplayTitle($antrag, $revConfig);
      $caption = trim(implode(" ", $caption));
      $url = str_replace("//","/", $URIBASE."/".$antrag["token"]);
      echo "<td><a href=\"".htmlspecialchars($url)."\">".$caption."</a></td>";
      echo "<td>";
       if (($antrag["creator"] == $antrag["creatorFullName"]) || empty($antrag["creatorFullName"])) {
         echo htmlspecialchars($antrag["creator"]);
       } else {
         echo "<span title=\"";
         echo htmlspecialchars($antrag["creator"]);
         echo "\">";
         echo htmlspecialchars($antrag["creatorFullName"]);
         echo "</span>";
       }
      echo "</td>";
      echo "<td>";
       $txt = $antrag["state"];
       if (isset($classConfig["state"]) && isset($classConfig["state"][$antrag["state"]]))
         $txt = $classConfig["state"][$antrag["state"]][0];
       $txt .= " (".$antrag["stateCreator"].")";
       echo htmlspecialchars($txt);
      echo "</td>";
      echo "<td>".htmlspecialchars($antrag["lastupdated"])."</td>";
      echo "</tr>";
    }
  }
}
?>
  </tbody>
</table>
<?php

# vim:syntax=php
