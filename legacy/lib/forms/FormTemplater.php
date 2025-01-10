<?php

namespace forms;

use forms\projekte\PermissionHandler;
use framework\auth\AuthHandler;
use framework\DBConnector;
use framework\Helper;

class FormTemplater
{
    private static $ID_DELIMITER = '__';

    /**
     * @var PermissionHandler
     */
    private $permissionHandler;

    private $noValueStringInReadOnly;

    /**
     * FormTemplater constructor.
     */
    public function __construct(PermissionHandler $permissionHandler, string $noValue = 'keine Angabe')
    {
        $this->permissionHandler = $permissionHandler;
        $this->noValueStringInReadOnly = $noValue;
    }

    public static function generateTitelSelectable($hhp_id): array
    {
        $all_titels = DBConnector::getInstance()->dbFetchAll(
            'haushaltsgruppen',
            [DBConnector::FETCH_ASSOC, DBConnector::FETCH_GROUPED],
            ['haushaltsgruppen.id', 'haushaltstitel.id', 'gruppen_name', 'titel_name', 'titel_nr', 'type'],
            ['haushaltsgruppen.hhp_id' => $hhp_id],
            [
                ['type' => 'left', 'table' => 'haushaltstitel', 'on' => ['haushaltsgruppen.id', 'haushaltstitel.hhpgruppen_id']],
            ],
            ['type' => false, 'haushaltsgruppen.id' => true, 'titel_nr' => true]);
        $selectable = [];
        foreach ($all_titels as /* $g_id => */ $group) {
            $ret_group = [
                'label' => ($group[0]['type'] ? 'Ausgabe' : 'Einnahme').' - '.$group[0]['gruppen_name'],
                'options' => [],
            ];
            foreach ($group as $titel) {
                $option = [];
                $option['label'] = $titel['titel_name'];
                $option['subtext'] = $titel['titel_nr'];
                $option['value'] = $titel['id'];
                // set in parent
                $ret_group['options'][] = $option;
            }
            // set in parent
            $selectable['groups'][] = $ret_group;
        }

        return $selectable;
    }

    public static function generateGremienSelectable(): array
    {
        $userGremien = AuthHandler::getInstance()->getUserGremien();
        $showAll = AuthHandler::getInstance()->hasGroup('ref-finanzen');

        $selectable = [];
        foreach (GREMIEN as $groupName => $gremien) {
            $group = [];
            $group['label'] = $groupName;

            $options = [];
            sort($gremien);
            foreach ($gremien as $gremiumName) {
                if ($showAll || in_array($gremiumName, $userGremien, true)) {
                    $options[] = ['label' => $gremiumName];
                }
            }
            $group['options'] = $options;
            $selectable['groups'][] = $group;
        }

        return $selectable;
    }

    public static function generateSelectable(array $list): array
    {
        $selectable = [];
        $options = [];
        foreach ($list as $key => $item) {
            $opt = ['label' => $item];
            if (is_string($key)) {
                $opt += ['value' => $key];
            }
            $options[] = $opt;
        }
        // only 1 group
        $selectable['groups'][0]['options'] = $options;

        return $selectable;
    }

    /**
     * generate List
     *
     * @param  array  $list
     * @param  string  $label
     * @param  bool  $wrapped
     * @param  bool  $linebreak
     * @param  string  $wrapped_class
     * @param  string  $default_tag
     * @param  string  $width_class
     */
    public static function generateListGroup($list, $label = '', $wrapped = true, $linebreak = true, $wrapped_class = 'col-xs-12 form-group', $default_tag = 'div', $width_class = 'col-xs-12'): string
    {
        if (! is_array($list)) {
            $list = [$list];
        }
        $out = '';
        if ($label) {
            $out .= '<label>'.$label.'</label>';
        }
        $out .= '<div class="input-group '.$width_class.'">';
        foreach ($list as $entry) {
            if (is_string($entry)) {
                $out .= "<$default_tag class=\"list-group-item\">".$entry."</$default_tag>";
            } else {
                $tag = $entry['tag'] ?? $default_tag;
                $text = htmlspecialchars($entry['text'] ?? '');
                $html = $entry['html'] ?? '';
                if (! isset($entry['attr']['class'])) {
                    $entry['attr']['class'] = 'list-group-item';
                }
                $attr = '';
                if (isset($entry['attr'])) {
                    foreach ($entry['attr'] as $k => $v) {
                        $attr .= ' '."{$k}=\"{$v}\"";
                    }
                }
                $out .= "<{$tag}{$attr}>".$text.$html."</$tag>";
            }
        }
        $out .= '</div>';
        if ($wrapped) {
            return '<div class="'.$wrapped_class.'">'.$out.'</div><div class="clearfix"></div>'.(($linebreak) ? '<br>' : '');
        }

        return $out.(($linebreak) ? '<br>' : '');
    }

    /**
     * @param  string  $key  current    editable key
     * @param  string  $function  editable function
     * @param  string  $type  editable type
     * @param  string  $value  current value
     * @param  array  $values  value list
     * @param  null  $values_out  value - html map
     * @param  string  $title  hover title
     * @param  array  $additional_params
     * @param  string  $additional_class  class
     * @param  string  $target_prefix  target uri prefix
     * @param  string  $target  target uri
     */
    public static function jsonEditable(string $key, $function = 'edit', $type = '', $value = '', $values = ['value'], $values_out = null, $title = 'Ändern', $additional_params = [], $additional_class = '', $target_prefix = '/', $target = 'rest/forms/editable'): string
    {
        $opt = '';
        if (isset($additional_params) && is_array($additional_params)) {
            foreach ($additional_params as $k => $v) {
                $opt .= ' data-'."{$k}=\"{$v}\"";
            }
        }
        if ($type !== 'disabled') {
            return '<div	class="editable '.$additional_class.
                '" title="'.$title.
                '" data-key="'.$key.
                '" data-mfunction="'.$function.
                '" data-type="'.$type.
                '" data-value="'.$value.
                '" data-target="'.$target_prefix.$target.'" '.
                $opt.'>'.
                (($values_out) ? $values_out[$value] : $value).
                '</div>';
        }

        return '<div class="editable-disabled'.$additional_class.'">'.(($values_out) ? $values_out[$value] : $value).'</div>';
    }

    public function getWikiLinkForm($name, $value = '', $width = 12, $placeholder = '', $label_text = '', $validator = [], $linkPrefix = ''): string
    {
        $unique_id = htmlspecialchars($this->getUniqueIdFromName($name));
        $editable = $this->checkWritePermission($name);
        $out = '';

        if ($editable) {
            $additonal_array = $this->constructValidatorStrings($validator);
            $type = 'text';
            if (isset($validator['email'])) {
                $type = 'email';
            }
            $additonal_str = implode(' ', $additonal_array);

            $value = htmlspecialchars($value);
            if (isset($linkPrefix) && ! empty($linkPrefix)) {
                $out .= "<div class='input-group'>";
                $out .= "<div class='input-group-addon form-field-to-replace'>".$linkPrefix.'</div>';
            }
            $out .= "<input type='$type' class='form-control form-field-replace' id='$unique_id' name='$name' value='$value' placeholder='{$placeholder}' $additonal_str >";
            if (isset($linkPrefix) && ! empty($linkPrefix)) {
                $out .= '</div>';
            }
        } else {
            if (! empty($linkPrefix)) {
                $out .= "<div id='$unique_id'>
                            <a target='_blank' href='".htmlspecialchars($linkPrefix).$this->getReadOnlyValue($value)."'>".
                    "<i class='fa fa-fw fa-wikipedia-w'></i> ".htmlspecialchars($linkPrefix).$this->getReadOnlyValue($value).
                    '</a>
                         </div>';
            } else {
                $out .= "<div id='$unique_id'>".$this->getReadOnlyValue($value).'</div>';
            }
        }

        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }

    private function getUniqueIdFromName($name): string
    {
        return htmlspecialchars(explode('[', $name)[0].self::$ID_DELIMITER.uniqid('', true));
    }

    private function checkWritePermission($name): bool
    {
        if ($this->permissionHandler->checkWritePermission() === true) {
            return true;
        }

        return $this->permissionHandler->checkWritePermissionField($name);
    }

    private function constructValidatorStrings($validatorArray): array
    {
        if (! isset($validatorArray) || empty($validatorArray)) {
            return [];
        }
        $ret = ['required'];

        if (isset($validatorArray['min-length'])) {
            $ret[] = 'data-minlength='.$validatorArray['min-length'];
        }
        if (isset($validatorArray['email'])) {
            $ret[] = "data-remote='".URIBASE.'validate.php?ajax=1&action=validate.email&nonce='.csrf_token()."'";
        }
        if (isset($validatorArray['iban'])) {
            $ret[] = "data-validateiban='1'";
        }

        return $ret;
    }

    private function getReadOnlyValue($values): string
    {
        if (is_array($values)) {
            if (! empty($values)) {
                return implode(',', array_map([$this, 'getReadOnlyValue'], $values));
            }

            return '<i>'.htmlspecialchars($this->noValueStringInReadOnly).'</i>';
        }
        if (empty($values)) {
            return '<i>'.htmlspecialchars($this->noValueStringInReadOnly).'</i>';
        }

        return htmlspecialchars($values);
    }

    private function getOutputWrapped($content, $width, $editable, $name, $unique_id, $label_text, $validator): string
    {
        $out = '';

        if ($name !== '' && $this->checkVisibility($name) === false) {
            return '';
        }

        $classes_array = $this->constructWidthClasses($width);
        $classes_array[] = 'form-group';
        if (! empty($validator) && $editable) {
            $classes_array[] = 'has-feedback';
        }
        $classes_str = implode(' ', $classes_array);
        $out .= "<div class='$classes_str'>";
        if (! empty($label_text)) {
            $out .= "<label class='control-label' for='$unique_id'>$label_text</label>";
        }
        $out .= $content;
        if (! empty($validator) && $editable) {
            // $out .= "<span class='glyphicon form-control-feedback' aria-hidden='true'></span>";
            $out .= "<div class='help-block with-errors'></div>";
        }
        $out .= '</div>';

        return $out;
    }

    private function checkVisibility($name): bool
    {
        return $this->permissionHandler->isVisibleField($name);
    }

    private function constructWidthClasses($width): array
    {
        if (! isset($width)) {
            return [];
        }
        $base_cls = ['col-xs-', 'col-xs-', 'col-md-', 'col-lg-'];
        $ret_cls = [];
        if (! is_array($width)) {
            $width = [$width];
        }
        for ($i = 0; $i < count($width) && $i < 4; $i++) {
            $ret_cls[] = $base_cls[$i].$width[$i];
        }

        return $ret_cls;
    }

    public function getMailForm($name, $value = '', $width = 12, $placeholder = '', $label_text = '', $validator = [], $domainSuffix = ''): string
    {
        $unique_id = htmlspecialchars($this->getUniqueIdFromName($name));
        $editable = $this->checkWritePermission($name);
        $out = '';

        if ($editable) {
            $additonal_array = $this->constructValidatorStrings($validator);
            $type = 'text';
            if (isset($validator['email'])) {
                $type = 'email';
            }
            $additonal_str = implode(' ', $additonal_array);

            $value = htmlspecialchars($value);
            if (isset($domainSuffix) && ! empty($domainSuffix)) {
                $out .= "<div class='input-group'>";
            }
            $out .= "<input type='$type' class='form-control form-field-replace' id='$unique_id' name='$name' value='$value' placeholder='{$placeholder}' $additonal_str >";
            if (isset($domainSuffix) && ! empty($domainSuffix)) {
                $out .= "<div class='input-group-addon form-field-to-replace'>".$domainSuffix.'</div>';
                $out .= '</div>';
            }
        } elseif (! empty($domainSuffix)) {
            $out .= "<div id='$unique_id'>
                        <a target='_blank' href='mailto:".$this->getReadOnlyValue($value).htmlspecialchars($domainSuffix)."'>".
                "<i class='fa fa-fw fa-envelope-o'></i> ".$this->getReadOnlyValue($value).htmlspecialchars($domainSuffix).
                '</a>
                     </div>';
        } else {
            $out .= "<div id='$unique_id'>".$this->getReadOnlyValue($value).'</div>';
        }

        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }

    public function getFileForm($name, $value = '', $width = 12, $placeholder = '', $label_text = '', $validator = []): string
    {
        $unique_id = htmlspecialchars($this->getUniqueIdFromName($name));

        $editable = ! $name || $this->checkWritePermission($name);
        $out = "<div class='single-file-container'>";
        $out .= "<input class='form-control single-file' type='file' name='$name' id='$unique_id'>";
        $out .= '</div>';

        /*
        $myOut = "<div class=\"single-file-container\">";
        $myOut .= "<input class=\"form-control single-file\" type=\"file\" name=\"" . htmlspecialchars($ctrl["name"]) . "\" orig-name=\"" . htmlspecialchars($ctrl["orig-name"]) . "\" id=\"" . htmlspecialchars($ctrl["id"]) . "\"/>";
        $myOut .= "</div>";
        if ($file){
            $renameFileFieldName = "formdata[{$layout["id"]}][newFileName]";
            $renameFileFieldNameOrig = $renameFileFieldName;
            foreach ($ctrl["suffix"] as $suffix){
                $renameFileFieldName .= "[{$suffix}]";
                $renameFileFieldNameOrig .= "[]";
            }

            echo "<div class=\"single-file-container\" data-display-text=\"" . newTemplatePattern($ctrl, $fileName) . "\" data-filename=\"" . newTemplatePattern($ctrl, $fileName) . "\" data-orig-filename=\"" . newTemplatePattern($ctrl, $fileName) . "\" data-old-html=\"" . htmlspecialchars($myOut) . "\">";
            echo "<span>" . $tPattern . "</span>";
            echo "<span>&nbsp;</span>";
            echo "<small><nobr class=\"show-file-size\">" . newTemplatePattern($ctrl, $file["size"]) . "</nobr></small>";
            if (!$ctrl["readonly"]){
                echo "<a href=\"#\" class=\"on-click-rename-file\"><i class=\"fa fa-fw fa-pencil\"></i></a>";
                echo "<a href=\"#\" class=\"on-click-delete-file\"><i class=\"fa fa-fw fa-trash\"></i></a>";
            }
            echo "<input type=\"hidden\" name=\"" . htmlspecialchars($renameFileFieldName) . "\" orig-name=\"" . htmlspecialchars($renameFileFieldNameOrig) . "\" id=\"" . htmlspecialchars($ctrl["id"]) . "-newFileName\" value=\"\" class=\"form-file-name\"/>";
            echo $oldFieldName;
            echo "</div>";
        }else if ($ctrl["readonly"]){
            echo "<div class=\"single-file-container\">";
            echo "</div>";
        }else{
            echo $myOut;
        }*/
        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }

    public function getTextForm($name, $value = '', $width = 12, $placeholder = '', $label_text = '', $validator = [], $textPrefix = ''): string
    {
        $unique_id = htmlspecialchars($this->getUniqueIdFromName($name));
        $editable = $this->checkWritePermission($name);
        $out = '';

        if ($editable) {
            $additonal_array = $this->constructValidatorStrings($validator);
            $type = 'text';
            if (isset($validator['email'])) {
                $type = 'email';
            }
            $additonal_str = implode(' ', $additonal_array);

            $value = htmlspecialchars($value);
            if (isset($textPrefix) && ! empty($textPrefix)) {
                $out .= "<div class='input-group'>";
                $out .= "<div class='input-group-addon'>".$textPrefix.'</div>';
            }
            $out .= "<input type='$type' class='form-control' id='$unique_id' name='$name' value='$value' placeholder='{$placeholder}' $additonal_str >";
            if (isset($textPrefix) && ! empty($textPrefix)) {
                $out .= '</div>';
            }
        } elseif (! empty($textPrefix)) {
            $out .= "<div id='$unique_id'>".($textPrefix).' - '.$this->getReadOnlyValue($value).'</div>';
        } else {
            $out .= "<div id='$unique_id'>".$this->getReadOnlyValue($value).'</div>';
        }

        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }

    public function getMoneyForm($name, $value = 0, $width = 12, $placeholder = '0.00', $label_text = '', $validator = [], $sum_id = ''): string
    {
        $out = '';
        $unique_id = $this->getUniqueIdFromName($name);
        $editable = $this->checkWritePermission($name);

        if ($editable) {
            $additonal_array = $this->constructValidatorStrings($validator);
            $additonal_str = implode(' ', $additonal_array);
            $value = number_format($value, 2, ',', '');
            $out .= "<div class='input-group'>";
            $out .= "<input id='$unique_id' name='$name' type='text' placeholder='$placeholder' class='form-control text-right' value='$value' data-addtosum='$sum_id' $additonal_str>";
            $out .= "<span class='input-group-addon'>€</span>";
            $out .= '</div>';
        } else {
            $out .= "<div class='money' id='$unique_id' data-addtosum='$sum_id'>".htmlspecialchars(number_format($value, 2, ',', '.')).'&nbsp;€</div>';
        }

        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }

    public function getDropdownForm($name, $selectable = [], $width = 12, $placeholder = '', $label_text = '', $validator = [], $searchable = true): string
    {
        $out = '';
        $editable = $this->checkWritePermission($name);
        $unique_id = $this->getUniqueIdFromName($name);

        $values = [];
        if (isset($selectable['values'])) {
            if (is_array($selectable['values'])) {
                $values = $selectable['values'];
            } else {
                $values = explode(';', $selectable['values']);
            }
        }
        // var_dump($selectable);
        if ($editable) {
            $additonal_array = $this->constructValidatorStrings($validator);
            $additonal_array[] = 'data-live-search='.($searchable ? "'true'" : "'false'");
            $additonal_array[] = "data-style=''";
            $additonal_array[] = "data-style-base='form-control'";
            $additonal_str = implode(' ', $additonal_array);
            $out .= '<div>'; // needed for new line after label
            $out .= "<select name='$name' id='$unique_id' class='selectpicker' data-width='100%' title='$placeholder' $additonal_str>";
            foreach ($selectable['groups'] as $group) {
                $group_label = $group['label'] ?? '';
                $out .= "<optgroup label='$group_label'>";
                foreach ($group['options'] as $option) {
                    if ($option['label'] === null) {
                        continue;
                    }
                    $val = $option['value'] ?? $option['label'];
                    $sub = $option['subtext'] ?? '';
                    // do not add strict parameter to in_array
                    $out .= "<option data-subtext='$sub' value='$val' ".(in_array($val, $values) ? 'selected' : '').">{$option['label']}</option>";
                }
                $out .= '</optgroup>';
            }
            $out .= '</select>';
            $out .= '</div>';
        } else {
            // re-substitute ids => names
            $tmp_vals = []; // value => [label, subtext]
            foreach ($selectable['groups'] as $group) {
                foreach ($group['options'] as $option) {
                    // do not add the strict option to in_array here!
                    if (isset($option['value']) && in_array($option['value'], $values)) {
                        $subtext = $option['subtext'] ?? '';
                        $tmp_vals[$option['value']] = ['label' => $option['label'], 'subtext' => $subtext];
                    }
                }
            }
            $values = array_merge(array_diff($values, array_keys($tmp_vals)), array_values($tmp_vals));

            // build subtext for read only
            $res = [];
            foreach ($values as $value) {
                if (is_array($value)) {
                    $res[] = $this->getReadOnlyValue($value['label'])."&nbsp;<small><span class='text-muted'>".htmlspecialchars($value['subtext']).'</span></small>';
                } else {
                    $res[] = $this->getReadOnlyValue($value);
                }
            }

            $out .= "<div data-value='".implode(';', array_keys($tmp_vals))."' data-name='$name' id='$unique_id'>";
            $out .= implode(',', $res);
            $out .= '</div>';
        }

        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }

    public function getTextareaForm($name, $value = '', $width = 12, $placeholder = '', $label_text = '', $validator = [], $min_rows = 5): string
    {
        $out = '';

        $editable = ! $name || $this->checkWritePermission($name);
        $unique_id = $this->getUniqueIdFromName($name);

        if ($editable) {
            $additonal_array = $this->constructValidatorStrings($validator);
            $additonal_str = implode(' ', $additonal_array);
            $out .= "<textarea id='$unique_id' placeholder='$placeholder' name='$name' class='form-control' rows='$min_rows' $additonal_str>$value</textarea>";
        } else {
            $out .= "<div id='$unique_id' class='textarea_readonly'>".
                Helper::make_links_clickable($this->getReadOnlyValue($value)).
                '</div>';
        }

        return $this->getOutputWrapped($out, $width, $editable, $name, $unique_id, $label_text, $validator);
    }

    /**
     * @param  string|array  $value
     * @param  int  $width
     * @param  string|array  $placeholder
     * @param  string  $label_text
     * @param  array  $validator
     * @param  false  $daterange
     * @param  string  $startDate
     */
    public function getDatePickerForm($names, $value = '', $width = 12, $placeholder = '', $label_text = '', $validator = [], $daterange = false, $startDate = ''): string
    {
        if (! is_array($value)) {
            $value = [$value, $value];
        }
        if (! isset($placeholder) || ! is_array($placeholder)) {
            $placeholder = [$placeholder, $placeholder];
        }
        if (! is_array($names)) {
            if ($daterange) {
                $names = [$names.'[]', $names.'[]'];
            } else {
                $names = [$names];
            }
        }
        $out = '';

        $editable = ((count($names) === 0 || (count($names) === 1 && ! $names[0])) ? true : $this->permissionHandler->isEditable($names, 'and'));
        $unique_id0 = $this->getUniqueIdFromName($names[0]);

        if ($editable) {
            $additonal_array = $this->constructValidatorStrings($validator);

            $additonal_str = implode(' ', $additonal_array);
            $out .= "<div id='$unique_id0' class='input-group ".($daterange ? 'input-daterange' : 'date')."' data-provide='datepicker' data-date-format='yyyy-mm-dd' data-date-calendar-weeks='true' data-date-language='de' ><!--data-date-start-date='$startDate'-->";
            if ($daterange) {
                $out .= "<div class='input-group-addon' style='background-color: transparent; border: none;'>von</div>";
                $out .= "<div class='input-group'>";
            }
            $out .= "    <input class='form-control' name='{$names[0]}' placeholder='{$placeholder[0]}' id='$unique_id0' $additonal_str value='{$value[0]}' type='text'>";
            $out .= "    <div class='input-group-addon'>";
            $out .= "        <span class='fa fa-fw fa-calendar'></span>";
            $out .= '    </div>';
            if ($daterange) {
                $unique_id1 = $this->getUniqueIdFromName($names[1]);
                $out .= '</div>';
                $out .= "<div class='input-group-addon' style='background-color: transparent; border: none;'>bis</div>";
                $out .= "<div class='input-group'>";
                $out .= "    <input class='form-control' id='$unique_id1' name='{$names[1]}' value='{$value[1]}' placeholder='{$placeholder[1]}' $additonal_str type='text'>";
                $out .= "    <div class='input-group-addon'>";
                $out .= "        <span class='fa fa-fw fa-calendar'></span>";
                $out .= '    </div>';
                $out .= '</div>';
            }
        } else {
            $out .= "<div id='$unique_id0'>";

            if ($daterange) {
                $out .= '<strong>von&nbsp;</strong>';
                $out .= "<span>{$this->getReadOnlyValue($value[0])}</span>";
                $out .= '<strong>&nbsp;bis&nbsp;</strong>';
                $out .= "<span>{$this->getReadOnlyValue($value[1])}</span>";
            } else {
                $out .= '<strong>am </strong>';
                $out .= "<span>{$this->getReadOnlyValue($value[0])}</span>";
            }
        }
        $out .= '</div>';

        return $this->getOutputWrapped($out, $width, $editable, $names[0], $unique_id0, $label_text, $validator);
    }

    public function getHiddenActionInput($actionName): string
    {
        return "<input type='hidden' name='action' value='$actionName'>";
    }
}
