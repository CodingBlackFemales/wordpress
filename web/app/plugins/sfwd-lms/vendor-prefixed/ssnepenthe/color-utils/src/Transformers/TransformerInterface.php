<?php

namespace StellarWP\Learndash\SSNepenthe\ColorUtils\Transformers;

use StellarWP\Learndash\SSNepenthe\ColorUtils\Colors\Color;

/**
 * Interface TransformerInterface
 */
interface TransformerInterface
{
    /**
     * @param Color $color
     * @return Color
     */
    public function transform(Color $color) : Color;
}
