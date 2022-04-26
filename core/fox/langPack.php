<?php namespace fox;

class langPack {
    public static function getAndReplace(string $key, array $values=[], $lang=null) {
        return static::replaceKeys(static::get($key,$lang), $values);
    }
    
    public static function replaceKeys(string $text,array $values=[]) {
        if (!array_key_exists("svcName", $values)) { $values["svcName"]=empty(config::get("svcName"))?(empty(config::get("TITLE"))?config::get("sitePrefix"):config::get("TITLE")):config::get("svcName"); }
        if (!array_key_exists("sitePrefix",$values)) { $values["sitePrefix"]=config::get("sitePrefix"); }
        return common::replaceMessageFromArray($text, $values);
    }
    
    public static function get($key, $lang=null) {
        $ref=explode(".",$key);
        if (empty($lang)) { $lang = config::get("DEFAULT_LANGUAGE"); };
        if (empty($lang)) { $lang = "ru"; }
        
        $mod=moduleInfo::getByInstance($ref[0]);
        if (array_search($lang, $mod->languages) === false ) {
            $lang = $mod->languages[0];
        }
        
        
        $langClass=$mod->namespace."\\lang\\".$lang;
        if (!class_exists($langClass)) {
            $langClass=$mod->namespace."\\lang_".$lang;
        }

        if (!class_exists($langClass)) {
            throw new foxException("Class ".$langClass." not found!");
        }
        try {
            return constant($langClass."::".$ref[1]);
        } catch (\Exception $e) {
            return null;
        }
    }
}
?>