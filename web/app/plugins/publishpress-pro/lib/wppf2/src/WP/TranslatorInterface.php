<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\WP;


interface TranslatorInterface
{
    /**
     * @return void
     */
    public function loadTextDomain();

    /**
     * @param string      $text
     * @param string|null $textDomain
     *
     * @return string
     */
    public function getText($text, $textDomain = null);

    /**
     * @param string      $text
     * @param string|null $textDomain
     *
     * @return string
     */
    public function displayText($text, $textDomain = null);

    /**
     * @param             $text
     *
     * @return string
     */
    public function escHtml($text);

    /**
     * @param string      $single
     * @param string      $plural
     * @param string      $number
     * @param string|null $textDomain
     *
     * @return string
     */
    public function getTextN($single, $plural, $number, $textDomain = null);

    /**
     * @param string      $text
     * @param string|null $textDomain
     *
     * @return string
     */
    public function getTextAndEscHtml($text, $textDomain = null);
}
