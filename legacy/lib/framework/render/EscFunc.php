<?php

namespace framework\render;

use Carbon\Carbon;

abstract class EscFunc
{
    protected function projektLinkEscapeFunction($id, $createdate, $name): string
    {
        $year = date('y', strtotime($createdate));

        return $this->internalHyperLinkEscapeFunction("IP-$year-$id $name", "projekt/$id");
    }

    protected function auslagenLinkEscapeFunction($projektId, $auslagenId, $name): string
    {
        return $this->internalHyperLinkEscapeFunction(
            "A$auslagenId ".$this->defaultEscapeFunction($name),
            "projekt/$projektId/auslagen/$auslagenId"
        );
    }

    protected function internalHyperLinkEscapeFunction($text, $dest): string
    {
        return "<a href='".htmlspecialchars(
            URIBASE.$dest
        )."'><i class='fa fa-fw fa-link' aria-hidden='true'></i>&nbsp;$text</a>";
    }

    protected function defaultEscapeFunction($val): string
    {
        // default escape-funktion to use if nothing is
        if ($val === 'null' || empty($val)) {
            return '<i>keine Angabe</i>';
        }

        return htmlspecialchars($val);
    }

    protected function hiddenInputEscapeFunction($name, $value): string
    {
        $name = htmlspecialchars($name);
        $value = htmlspecialchars($value);

        return "<input type='hidden' name='$name' value='$value'>";
    }

    protected function moneyEscapeFunction($money): string
    {
        return number_format($money, 2, ',', '&nbsp;').'&nbsp;€';
    }

    protected function date2relstrEscapeFunction($time): string
    {
        if ($time === '') {
            return $this->defaultEscapeFunction('');
        }

        $now = Carbon::now();
        $time = new Carbon($time);

        $diff = $now->diff($time);
        if ($diff->days > 1) {
            return ($diff->invert ? 'vor ' : 'in ').$diff->d.' Tagen';
        }
        if ($diff->h > 0) {
            return ($diff->invert ? 'vor ' : 'in ').$diff->h.' Stunden';
        }
        if ($diff->m > 0) {
            return ($diff->invert ? 'vor ' : 'in ').$diff->m.' Stunden';
        }
        if ($diff->s > 0) {
            return ($diff->invert ? 'vor ' : 'in ').$diff->s.' Sekunden';
        }
    }

    protected function textAreaEscapeFunction($name, $value, $required = false): string
    {
        $name = htmlspecialchars($name);
        $value = htmlspecialchars($value);
        $required = $required ? 'required' : '';

        return "<textarea name='$name' rows='1' class='form-control booking__text' $required>$value</textarea>";
    }

    protected function arrayToListEscapeFunction($array): string
    {
        $out = '<ul>';
        foreach ($array as $item) {
            $out .= "<li>$item</li>";
        }
        $out .= '</ul>';

        return $out;
    }
}
