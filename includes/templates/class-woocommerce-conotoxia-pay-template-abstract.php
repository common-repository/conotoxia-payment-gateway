<?php

abstract class WC_Gateway_Conotoxia_Pay_Template
{
    /**
     * @param string $template
     * @return string
     */
    protected static function sanitize_template(string $template): string
    {
        $template = preg_replace('/' . PHP_EOL . '/', '', $template);
        return preg_replace('/\s{2,}/', '', $template);
    }
}
