<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gribaa;

/**
 * Description of KeywordsTool
 *
 * @author Administrator
 */
class KeywordsTool {
    
    public static function scrap($url)
    {
        $ch = curl_init(utf8_encode($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_COOKIESESSION, true );
        curl_setopt($ch, CURLOPT_COOKIEJAR, PROJECT_DIR.DS.'res'.DS.'res.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, PROJECT_DIR.DS.'res'.DS.'res.txt');
        $res = curl_exec($ch);
        curl_close($ch);
        if(!$res)
        {
            return;
        }
        return $res;
    }
    
    
    
    public static function keywordsFromUrl($url)
    {
        $source = self::scrap(trim($url));
        if(!$source)
        {
            return [];
        }
        libxml_use_internal_errors(TRUE);
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($source, 'HTML-ENTITIES', 'UTF-8'));
        $text ='';
        $dom->getElementsByTagName('title')->length? $text.=$dom->getElementsByTagName('title')->item(0)->textContent.PHP_EOL:'';
        $h1 = $dom->getElementsByTagName('h1');
        foreach ($h1 as $v)
        {
            $text.=$v->textContent.PHP_EOL;
        }
        $h2 = $dom->getElementsByTagName('h2');
        foreach ($h2 as $v)
        {
            $text.=$v->textContent.PHP_EOL;
        }
        $p = $dom->getElementsByTagName('p');
        $i = 0;
        foreach ($p as $v)
        {
            if($i>10)
            {
                break;
            }
            $text.=$v->textContent.PHP_EOL;
            $i++;
        }
        if(!$text)
        {
            return [];
        }
        return self::keywordFromText($text);
    }
    
    public static function detectLanguage($text)
    {
        $detect = \LanguageDetector\Detect::initByPath(PROJECT_DIR.DS.'vendor'.DS.'LanguageDetector'.DS.'langs.php');
        $lang = $detect->detect($text);
        if(is_string($lang))
        {
            return $lang;
        }
        return $lang[0]['lang'];
    }
    
    public static function keywordFromText($text,$count = 100)
    {
        $config = new \crodas\TextRank\Config();
        $config->addListener(new \crodas\TextRank\Stopword());
        $k = new \crodas\TextRank\TextRank($config);
        $keywords =  array_keys($k->getAllKeywordsSorted($text));
        $keywords = array_slice($keywords, 0, $count);
        return array_map(function($index){ return $index;}, $keywords);
    }
}
