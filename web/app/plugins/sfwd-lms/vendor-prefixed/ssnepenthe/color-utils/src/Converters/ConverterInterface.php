<?php

namespace StellarWP\Learndash\SSNepenthe\ColorUtils\Converters;

use StellarWP\Learndash\SSNepenthe\ColorUtils\Colors\ColorInterface;

/**
 * Interface ConverterInterface
 */
interface ConverterInterface
{
    /**
     * @param ColorInterface $color
     * @return ColorInterface
     */
    public function convert(ColorInterface $color) : ColorInterface;
}
