<?php


namespace As247\Flysystem\DriveSupport\Support;


class Path
{
	public static function clean($path,$return='string'){
		if(!is_array($path)){
			$path=str_replace('\\','/',$path);
			$path = explode('/',$path);
		}
		$path=array_filter($path,function($v){
			if(strlen($v)===0 || $v=='.' || $v=='..' || $v=='/'){
				return false;
			}
			return true;
		});
		if($return==='string'){
			$path='/'.join('/',$path);
		}elseif($return==='count'){
			return count($path);
		}else{
			array_unshift($path,'/');
		}
		return $path;
	}
	public static function replace($search, $replace, $subject){
		$pos = strpos($subject, $search);
		if ($pos === 0) {
			$subject = substr_replace($subject, $replace, $pos, strlen($search));
		}
		return $subject;
	}
	public static function countSegments($path){
		return static::clean($path,'count');
	}
}
