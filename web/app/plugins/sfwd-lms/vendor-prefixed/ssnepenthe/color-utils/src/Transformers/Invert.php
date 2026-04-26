<?php

namespace StellarWP\Learndash\SSNepenthe\ColorUtils\Transformers;

use StellarWP\Learndash\SSNepenthe\ColorUtils\Colors\Color;

/**
 * Class Invert
 */
class Invert implements TransformerInterface
{
    /**
     * @param Color $color
     * @return Color
     */
    public function transform(Color $color) : Color
    {
        return $color->with([
            'blue'  => 255 - $color->getBlue(),
            'green' => 255 - $color->getGreen(),
            'red'   => 255 - $color->getRed(),
        ]);
    }
}
