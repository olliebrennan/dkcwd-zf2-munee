<?php
/**
 * @link http://github.com/dkcwd/dkcwd-zf2-munee for the canonical source repository
 * @author Dave Clark dave@dkcwd.com.au
 * @copyright (c) Dave Clark 2012 (https://www.dkcwd.com.au)
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace DkcwdZf2Munee\View\Helpers;

use Zend\View\Helper\HeadLink;

class MuneeCss extends HeadLink
{
    /**
     * Render placeholder as string
     *
     * @param string|int $indent
     * @param boolean $minify
     * @return string
     */
    public function toString($indent = false, $minify = false)
    {
        $minify = (! $minify) ? 'false' : 'true';
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();
        $this->getContainer()->ksort();
        
        $return = array();
        $nonSheets = array();
        $stylesheets = array();
        
        try {
            foreach ($this as $item) {
                // Null check required due to ZF2 headLink bug (ref to come soon)
                if (isset($item->type) && 'text/css' == $item->type &&
                    (false === $item->conditionalStylesheet || null === $item->conditionalStylesheet)) {
                    $stylesheets[$item->media][] = $item->href;
                } else {
                    // Conditional stylesheet so minify
                    if (isset($item->type) && 'text/css' == $item->type) {
                        $item->href = $this->getMuneeHref($item->href, $minify);
                    }
    
                    $nonSheets[] = $this->itemToString($item);
                }
            }
            
            // Compile stylesheets
            if (! empty($stylesheets)) {
                foreach ($stylesheets as $media => $sheets) {
                    $item = new \stdClass();
                    $item->rel = 'stylesheet';
                    $item->type = 'text/css';
                    $item->href = $this->getMuneeHref($sheets, $minify);
                    $item->media = $media;
                    
                    $return[] = $this->itemToString($item);
                }
            }
        } catch (Exception\InvalidArgumentException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }

        return $indent . implode($this->escape($this->getSeparator()) . $indent, array_merge($return, $nonSheets));
    }
    
    /**
     * Builds the Mun.ee href content for inclusion in CSS content
     *
     * @param array|string $files
     * @param string $minify
     * @return string
     */
    protected function getMuneeHref($files, $minify)
    {
        if (is_string($files)) {
            $files = array($files);
        }

        return sprintf('/munee?files=%s&minify=%s', implode(',', $files), $minify);
    }
}