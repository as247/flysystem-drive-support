<?php


namespace As247\Flysystem\DriveSupport\Support;


use As247\Flysystem\DriveSupport\Exception\PathOutsideRootException;
use LogicException;

class Path
{
	public static function clean($path,$return='string'){
        if(is_array($path)){
            $path=join('/',$path);
        }
		$path=static::normalizeRelativePath($path);
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

    /**
     * Normalize relative directories in a path.
     *
     * @param string $path
     *
     * @throws LogicException
     *
     * @return array
     */
    protected static function normalizeRelativePath(string $path)
    {
        $path = str_replace('\\', '/', $path);
        $path = static::removeFunkyWhiteSpace($path);

        $parts = [];

        foreach (explode('/', $path) as $part) {
            switch ($part) {
                case '':
                case '.':
                    break;

                case '..':
                    if (empty($parts)) {
                        throw PathOutsideRootException::atLocation($path);
                    }
                    array_pop($parts);
                    break;

                default:
                    $parts[] = $part;
                    break;
            }
        }

        return $parts;
    }

    /**
     * Removes unprintable characters and invalid unicode characters.
     *
     * @param string $path
     *
     * @return string $path
     */
    protected static function removeFunkyWhiteSpace($path)
    {
        // We do this check in a loop, since removing invalid unicode characters
        // can lead to new characters being created.
        while (preg_match('#\p{C}+|^\./#u', $path)) {
            $path = preg_replace('#\p{C}+|^\./#u', '', $path);
        }

        return $path;
    }
}
