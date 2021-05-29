<?php

namespace framework\render\html;

class FA extends Html
{
    /**
     * @param string $faName can but has not to start with 'fa-'
     * @return FA
     */
    public static function make(string $faName) : self
    {
        if(strpos($faName, 'fa-') !== 0){
            $faName = 'fa-' . $faName;
        }
        return new self(['aria-hidden' => true], ['fa', $faName], []);
    }

    protected function __construct(array $attributes, array $classes, array $dataAttributes)
    {
        parent::__construct('i', $attributes, $classes, $dataAttributes);
    }

    /**
     * @param int $degree 90, 180 or 270  are valid
     * @return $this
     */
    public function rotate(int $degree) : self
    {
        return $this->addClasses(['fa-rotate-' . $degree]);
    }

    /**
     * @param int $size between 2 and 5
     * @return $this
     */
    public function size(int $size) : self
    {
        return $this->addClasses(['fa-' . $size . 'x']);
    }

    public function spin() : self {
        $this->addClasses(['icon-spin']);
        return $this;
    }

}