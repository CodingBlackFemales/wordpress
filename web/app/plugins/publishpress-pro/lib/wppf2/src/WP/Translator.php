<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\WP;


class Translator implements TranslatorInterface
{
    /**
     * @var string
     */
    private $textDomain;

    /**
     * @var string
     */
    private $languagesPath;

    /**
     * @var HooksHandlerInterface
     */
    private $hooksHandler;

    /**
     * Translator constructor.
     *
     * @param string                $textDomain
     * @param string                $languagesPath
     * @param HooksHandlerInterface $hooksHandler
     */
    public function __construct($textDomain, $languagesPath, HooksHandlerInterface $hooksHandler)
    {
        $this->textDomain    = $textDomain;
        $this->languagesPath = $languagesPath;
        $this->hooksHandler  = $hooksHandler;

        $this->setHooks();
    }

    private function setHooks()
    {
        $this->hooksHandler->addAction(HooksAbstract::ACTION_PLUGINS_LOADED, [$this, 'loadTextDomain']);
    }

    public function loadTextDomain()
    {
        load_plugin_textdomain($this->textDomain, false, $this->languagesPath);
    }

    /**
     * @inheritDoc
     */
    public function getText($text, $textDomain = null)
    {
        $textDomain = $this->getTextDomain($textDomain);

        return __($text, $textDomain);
    }

    private function getTextDomain($textDomain = null)
    {
        if (empty($textDomain)) {
            $textDomain = $this->textDomain;
        }

        return $textDomain;
    }

    /**
     * @inheritDoc
     */
    public function displayText($text, $textDomain = null)
    {
        $textDomain = $this->getTextDomain($textDomain);

        _e($text, $textDomain);
    }

    /**
     * @inheritDoc
     */
    public function escHtml($text)
    {
        return esc_html($text);
    }

    /**
     * @inheritDoc
     */
    public function getTextN($single, $plural, $number, $textDomain = null)
    {
        $textDomain = $this->getTextDomain($textDomain);

        return _n($single, $plural, $number, $textDomain);
    }

    /**
     * @inheritDoc
     */
    public function getTextAndEscHtml($text, $textDomain = null)
    {
        $textDomain = $this->getTextDomain($textDomain);

        return esc_html__($text, $textDomain);
    }
}
