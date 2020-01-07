<?php

namespace Xanax/Classes/Regex;

class Executor
{
  public static function Match(string $pattern, string $subject)
  {
    $bool = @preg_match($pattern, $subject, $matches);
    
    return [$bool, $pattern, $subject, $matches];
  }
  
  public static function matchAll(string $pattern, string $subject)
  {
    $bool = @preg_match_all($pattern, $subject, $matches);
    
    return [$bool, $pattern, $subject, $matches];
  }
}
