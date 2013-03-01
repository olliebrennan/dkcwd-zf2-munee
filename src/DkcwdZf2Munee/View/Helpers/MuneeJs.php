<?php
/**
 * @link http://github.com/dkcwd/dkcwd-zf2-munee for the canonical source repository
 * @author Dave Clark dave@dkcwd.com.au
 * @copyright (c) Dave Clark 2012 (https://www.dkcwd.com.au)
 * @license http://opensource.org/licenses/mit-license.php
 */

namespace DkcwdZf2Munee\View\Helpers;

use Zend\Uri\Http;

use Zend\View\Helper\HeadScript;

class MuneeJs extends HeadScript
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
        
        if ($this->view) {
            $useCdata = $this->view->plugin('doctype')->isXhtml() ? true : false;
        } else {
            $useCdata = $this->useCdata ? true : false;
        }
        
        $escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
        $escapeEnd   = ($useCdata) ? '//]]>' : '//-->';
        
        $scripts = array();
        $nonGroupedScripts = array();
        $inlineScript = array();
        $uriValidator = new Http();
    
        // First step is to categories each item in scripts, inline and external
        foreach ($this as $item) {
            // Not a javascript file so skip
            if ('text/javascript' != $item->type) {
                continue;
            }
            
            if (! empty($item->attributes) && ! empty($item->attributes['src'])) {
                if ($uriValidator->isValid($item->attributes['src']) || ! empty($item->attributes['conditional'])) {
                    $nonGroupedScripts[] = $item;
                } else {
                    $scripts[] = $item->attributes['src'];
                }
            } elseif (! empty($item->source)) {
                $inlineScript[] = $item;
            }
        }
        
        // Put all scripts together and send back
        $return = '';
        foreach ($nonGroupedScripts as $script) {
            $return .= $this->itemToString($script, $indent, $escapeStart, $escapeEnd);
        }
        
        foreach ($inlineScript as $script) {
            $return .= $this->itemToString($script, $indent, $escapeStart, $escapeEnd);
        }
        
        if (! empty($scripts)) {
            $item = new \stdClass();
            $item->type = 'text/javascript';
            $item->attributes = array('src' => $this->getMuneeHref($scripts, $minify));
            $return .= $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
        }
        
        return $return;
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