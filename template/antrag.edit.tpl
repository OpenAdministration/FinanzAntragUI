<form id="editantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax">
  <input type="hidden" name="action" value="antrag.update"/>
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
  <input type="hidden" name="type" value="<?php echo $antrag["type"]; ?>"/>
  <input type="hidden" name="revision" value="<?php echo $antrag["revision"]; ?>"/>
  <input type="hidden" name="version" value="<?php echo $antrag["version"]; ?>"/>

<?php

renderForm($form, ["_values" => $antrag] );

?>

  <!-- do not name it "submit": http://stackoverflow.com/questions/3569072/jquery-cancel-form-submit-using-return-false -->
  <button type="submit" class='btn btn-success pull-right' name="absenden" id="absenden">Speichern</button>
  <span class="pull-right">&nbsp;</span>
  <a class="btn btn-default pull-right" href="<?php echo htmlspecialchars($URIBASE."/".$antrag["token"]); ?>">Abbruch</a>

</form>


<form id="editantrag" role="form" action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST"  enctype="multipart/form-data" class="ajax">
  <input type="hidden" name="action" value="antrag.delete"/>
  <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
  <input type="hidden" name="type" value="<?php echo $antrag["type"]; ?>"/>
  <input type="hidden" name="revision" value="<?php echo $antrag["revision"]; ?>"/>
  <input type="hidden" name="version" value="<?php echo $antrag["version"]; ?>"/>
  <button type="submit" class='btn btn-danger' name="delete" id="delete">Löschen</button>
</form>

<?php
# vim: set syntax=php:
