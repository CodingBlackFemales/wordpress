<?php

namespace StellarWP\Learndash\SSNepenthe\ColorUtils\Transformers;

use StellarWP\Learndash\SSNepenthe\ColorUtils\Colors\Color;
use StellarWP\Learndash\SSNepenthe\ColorUtils\Exceptions\InvalidArgumentException;

/**
 * Class AdjustColor
 */
class AdjustColor implements TransformerInterface
{
    /**
     * @var array
     */
    protected $adjustments = [];

    /**
     * @var array
     */
    protected $whitelist = [
        'alpha',
        'blue',
        'green',
        'hue',
        'lightness',
        'red',
        'saturation',
    ];

    /**
     * @param array $adjustments
     * @throws InvalidArgumentException
     */
    public function __construct(array $adjustments)
    {
        // First filter out non-adjustments (0 or non-numeric adjustments).
        $adjustments = array_filter($adjustments, function ($adjustment) : bool {
            return $adjustment && is_numeric($adjustment);
        });

        foreach ($this->whitelist as $channel) {
            if (isset($adjustments[$channel])) {
                $this->adjustments[$channel] = $adjustments[$channel];
            }
        }

        if (empty($this->adjustments)) {
            throw new InvalidArgumentException(sprintf(
                'No valid adjustments provided in %s',
                __METHOD__
            ));
        }
    }

    /**
     * @param Color $color
     * @return Color
     */
    public function transform(Color $color) : Color
    {
        $channels = [];

        foreach ($this->adjustments as $channel => $adjustment) {
            $getter = 'get' . ucfirst($channel);
            $channels[$channel] = $color->{$getter}() + $adjustment;
        }

        return $color->with($channels);
    }
}
